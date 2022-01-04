<?php

namespace Drupal\izertis_usuarios\Repositorio;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Locale\CountryManager;
use Drupal\taxonomy\Entity\Term;

/**
 * Class RepositorioService
 *
 * @package Drupal\izertis_usuarios\Repositorio
 */
class RepositorioService {

  protected $base_datos;

  protected $manejador_entidades;

  /**
   * RepositorioService constructor.
   *
   * @param \Drupal\Core\Database\Connection $base_datos
   * @param \Drupal\Core\Entity\EntityTypeManager $manejador_entidades
   */
  public function __construct(Connection $base_datos, EntityTypeManager $manejador_entidades) {
    $this->base_datos = $base_datos;
    $this->manejador_entidades = $manejador_entidades;
  }

  /**
   * Crear término de taxonomía por tipo de taxonomía.
   *
   * @param $tipo
   * @param $nombre
   *
   * @return bool
   */
  public function crearTermino($tipo, $nombre) {

    $pais_termino = Term::create([
      'name' => $nombre,
      'vid' => $tipo,
    ]);

    try {
      $pais_termino->save();
      return TRUE;
    } catch (\Exception $exception) {
      return FALSE;
    }

  }

  /**
   * Obtener la Cantidad de Usuarios Por Rango.
   *
   * @param $min
   * @param $max
   *
   * @return int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function obtenerCantidadUsuarioPorRango($min, $max) {
    $consulta = $this->manejador_entidades->getStorage('user')
      ->getQuery()
      ->condition('status', 1);

    if (is_null($max)) {
      $consulta = $consulta->condition('field_edad', $min, "<");
    }
    else if (is_null($min) ) {
      $consulta = $consulta->condition('field_edad', $max, ">");
    }
    else {
      $consulta = $consulta->condition('field_edad', [$min, $max], "BETWEEN");
    }

    try {
      $consulta = $consulta->execute();
    } catch (\Exception $e) {
      $consulta = [];
    }

    return count($consulta);
  }

}
