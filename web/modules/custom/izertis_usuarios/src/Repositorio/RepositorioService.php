<?php

namespace Drupal\izertis_usuarios\Repositorio;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Locale\CountryManager;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

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
    else {
      if (is_null($min)) {
        $consulta = $consulta->condition('field_edad', $max, ">");
      }
      else {
        $consulta = $consulta->condition('field_edad', [$min, $max], "BETWEEN");
      }
    }

    try {
      $consulta = $consulta->execute();
    } catch (\Exception $e) {
      $consulta = [];
    }

    return count($consulta);
  }

  /**
   * Obtener la informacion de los usuarios por filtros.
   *
   * @param $lista_filtros
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function obtenerDatosUsuarioPorFiltros($lista_filtros) {

    $usuario_consulta = $this->manejador_entidades->getStorage('user')
      ->getQuery();

    $propiedades = [];


    if (!empty($lista_filtros["status"])) {
      $propiedades["status"] = [
        'operador' => NULL,
        'valor' => $lista_filtros["status"],
      ];
    }

    if (!empty($lista_filtros["field_nacionalidad"])) {
      $propiedades["field_nacionalidad"] = [
        'operador' => NULL,
        'valor' => $lista_filtros["field_nacionalidad"],
      ];
    }

    // Filtrar por rol.
    if (!empty($lista_filtros["role"])) {
      $usuario_consulta = $usuario_consulta->condition('roles', $lista_filtros["role"]);
    }

    // Filtrar por edad.
    if (!empty($lista_filtros["field_edad"])) {
      $min = 18;
      $max = 35;

      if ($lista_filtros["field_edad"] == 1) {
        $usuario_consulta = $usuario_consulta->condition('field_edad', [
          $min,
          $max,
        ], "BETWEEN");
      }

      if ($lista_filtros["field_edad"] == 2) {
        $min = 35;
        $max = 50;
        $usuario_consulta = $usuario_consulta->condition('field_edad', [
          $min,
          $max,
        ], "BETWEEN");
      }

      if ($lista_filtros["field_edad"] == 3) {
        $max = 50;
        $usuario_consulta = $usuario_consulta->condition('field_edad', $max, ">");
      }
    }

    // Filtrar por user.
    if (!empty($lista_filtros["user"])) {
      $plano = $lista_filtros["user"];
      $grupo = $usuario_consulta->orConditionGroup()
        ->condition('name', "%$plano%", "LIKE")
        ->condition('mail', "%$plano%", "LIKE");
      $usuario_consulta = $usuario_consulta->condition($grupo);
    }

    // Filtrar por propiedades generales.
    foreach ($propiedades as $clave => $valor) {
      if ($valor['operador'] == NULL) {
        $usuario_consulta = $usuario_consulta->condition($clave, $valor['valor']);
      }
      else {
        if ($valor['operador'] == 'LIKE') {
          $plano = $valor['valor'];
          $usuario_consulta = $usuario_consulta->condition($clave, "%$plano%", $valor['operador']);
        }
        else {
          $usuario_consulta = $usuario_consulta->condition($clave, $valor['valor'], $valor['operador']);
        }
      }
    }

    // Ejecutar consulta.
    $usuarios = $usuario_consulta->execute();

    $entidades = [];

    if (count($usuarios) > 0) {

      $todas = User::loadMultiple($usuarios);
      foreach ($todas as $entidad) {
        if (!empty($entidad->get('name')->value)) {
          $entidad_guardar = [
            'name' => $entidad->get('name')->value,
            'mail' => $entidad->get('mail')->value,
            'status' => $entidad->get('status')->value == 1 ? t("Activo")->render() : t("Inactivo")->render(),
            'roles' => $entidad->get('roles')->referencedEntities(),
            'field_edad' => $entidad->get('field_edad')->value,
            'field_nacionalidad' => $entidad->get('field_nacionalidad')
              ->referencedEntities(),
          ];

          // Filtrar por permisos.
          if (!empty($lista_filtros["permisos"])) {
            if ($entidad->hasPermission($lista_filtros["permisos"])) {
              $entidades[] = $entidad_guardar;
            }
          }
          else {
            $entidades[] = $entidad_guardar;
          }
        }
      }
    }

    return $entidades;
  }

}
