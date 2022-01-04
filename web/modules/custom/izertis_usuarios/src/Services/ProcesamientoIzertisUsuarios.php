<?php

namespace Drupal\izertis_usuarios\Services;

use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Dompdf\Dompdf;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Render\Renderer;
use Drupal\izertis_usuarios\Repositorio\RepositorioService;

/**
 * Class ProcesamientoIzertisUsuarios.
 */
class ProcesamientoIzertisUsuarios {

  protected $repositorio;

  protected $renderizador;

  /**
   * ProcesamientoIzertisUsuarios constructor.
   *
   * @param \Drupal\izertis_usuarios\Repositorio\RepositorioService $repositorio
   * @param \Drupal\Core\Render\Renderer $renderizador
   */
  public function __construct(RepositorioService $repositorio, Renderer $renderizador) {
    $this->repositorio = $repositorio;
    $this->renderizador = $renderizador;
  }

  /**
   * Obtener rangos de usuario por secciones.
   *
   * @param $secciones
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function obtenerRangosUsuarioPorSecciones($secciones) {

    // Convertir cada linea del textarea en una lista.
    $lista_rangos = $this->convertirConfiguracionEnLista($secciones);
    foreach ($lista_rangos as &$rango) {
      $rango['cantidad'] = $this->repositorio->obtenerCantidadUsuarioPorRango($rango["min"], $rango["max"]);
    }

    return $lista_rangos;
  }

  /**
   * Convertir string del textarea en una lista.
   *
   * @param $secciones
   *
   * @return array
   */
  public function convertirConfiguracionEnLista($secciones) {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $lista = explode(PHP_EOL, $secciones);
    $lista = array_diff($lista, ["", NULL]);

    $secciones = [];
    foreach ($lista as $seccion) {
      $temp = explode('|', $seccion);
      $rangos = explode("-", $temp[1]);
      $min = !empty($rangos[0]) ? intval($rangos[0]) : NULL;
      $max = !empty($rangos[1]) ? intval($rangos[1]) : NULL;

      $secciones[] = [
        'etiqueta' => $temp[0],
        'min' => $min,
        'max' => $max,
      ];
    }

    return $secciones;
  }

  /**
   * Procesar exportacion de usuarios.
   *
   * @param $cabecera
   * @param $datos
   *
   * @return array
   * @throws \Box\Spout\Common\Exception\IOException
   * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
   */
  public function procesoExportarUsuarios($cabecera, $datos) {

    $time = time();
    $tipo = $cabecera['tipo'];
    $datos_exportar = $this->estandarizarDatos($datos);
    $columnas = $cabecera['cabecera'];
    $directorio = 'public://private/exportar_usuarios';
    \Drupal::service('file_system')
      ->prepareDirectory($directorio, FileSystem::CREATE_DIRECTORY);
    $dir = \Drupal::service('stream_wrapper_manager')
      ->getViaUri($directorio)
      ->realpath();
    $nombre_archivo = $cabecera['nombre_archivo'] . '-' . $time . '.' . $tipo;
    $camino_archivo = $dir . '/' . $nombre_archivo;
    $cabecera_archivo = 'text/csv';


    if ($tipo == 'csv') {

      $writer = WriterEntityFactory::createCSVWriter();
      $writer->openToFile($camino_archivo);
      $writer->setFieldDelimiter(';');

      // Adicionar fila de cabeceras.
      $columna_fila = WriterEntityFactory::createRowFromArray($columnas);
      $writer->addRow($columna_fila);

      foreach ($datos_exportar as $fila) {
        // Insertar cada fila.
        $fila_interna = WriterEntityFactory::createRowFromArray($fila);
        $writer->addRow($fila_interna);
      }

      $writer->close();
    }

    if ($tipo == 'pdf') {
      $dompdf = new Dompdf();
      $plantila = $this->renderizarPlantillaUsuarios("exportar-usuarios-pdf", $datos_exportar);
      $dompdf->loadHtml($plantila);
      $dompdf->setPaper('A4', 'landscape');
      $dompdf->render();
      $dompdf->stream($nombre_archivo);
    }

    return [
      'camino_archivo' => $camino_archivo,
      'nombre_archivo' => $nombre_archivo,
      'archivo_cabecera' => $cabecera_archivo,
    ];
  }

  /**
   * Renderizar plantilla para PDF.
   *
   * @param $nombre
   * @param $datos
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   * @throws \Exception
   */
  public function renderizarPlantillaUsuarios($nombre, $datos) {

    $theme = ['#theme' => $nombre, '#datos' => $datos];
    return $this->renderizador->render($theme);
  }

  /**
   * Estandarizar los datos de los usuarios.
   *
   * @param $datos
   *
   * @return mixed
   */
  public function estandarizarDatos($datos) {
    foreach ($datos as &$fila) {
      $roles = [];
      // Proceso de roles.
      if (count($fila['roles']) > 0) {
        foreach ($fila['roles'] as $rol) {
          $roles[] = $rol->get('label');
        }
      }
      $cadena_roles = implode("-", $roles);

      // Proceso de nacionalidad.
      $nacionalidad = '';
      if (count($fila['field_nacionalidad']) > 0) {
        $nacionalidad = $fila['field_nacionalidad'][0]->getName();
      }
      $fila['roles'] = $cadena_roles;
      $fila['field_nacionalidad'] = $nacionalidad;
    }

    return $datos;
  }


}
