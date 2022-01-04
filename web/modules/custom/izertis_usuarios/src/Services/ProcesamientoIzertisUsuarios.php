<?php

namespace Drupal\izertis_usuarios\Services;

use Drupal\izertis_usuarios\Repositorio\RepositorioService;

/**
 * Class ProcesamientoIzertisUsuarios.
 */
class ProcesamientoIzertisUsuarios {

  protected $repositorio;

  /**
   * ProcesamientoIzertisUsuarios constructor.
   *
   * @param \Drupal\izertis_usuarios\Repositorio\RepositorioService $repositorio
   */
  public function __construct(RepositorioService $repositorio) {
    $this->repositorio = $repositorio;
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
    $lista = array_diff($lista, array("",null));

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

}
