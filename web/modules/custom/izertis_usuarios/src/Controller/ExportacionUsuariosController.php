<?php

namespace Drupal\izertis_usuarios\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class ExportacionUsuariosController
 *
 * @package Drupal\izertis_usuarios\Controller
 */
class ExportacionUsuariosController extends ControllerBase {

  /**
   * Metodo exportar usuarios.
   *
   * @return array|\Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportar() {
    $repositorio = \Drupal::service("izertis_usuarios.repositorio");
    $proceso = \Drupal::service("izertis_usuarios.procesamiento");

    $parametros = \Drupal::request()->request->all();
    $tipo = (isset($parametros["select-export-usuarios"]))
      ? $parametros["select-export-usuarios"]
      : 'csv';
    $url = $parametros['url_usuario'];
    $filtros = $this->getUrlParams($url);

    $lista_filtros = [
      "user" => $filtros['user'] == ''
        ? NULL
        : $filtros['user'],
      "status" => $filtros['status'] == '' || $filtros['status'] == 'All'
        ? NULL
        : intval($filtros['status']),
      "role" => $filtros['role'] == '' || $filtros['role'] == 'All'
        ? NULL
        : $filtros['role'],
      "field_edad" => $filtros['field_edad_value'] == '' || $filtros['field_edad_value'] == 'All'
        ? NULL
        : $filtros['field_edad_value'],
      "field_nacionalidad" => $filtros['field_nacionalidad_target_id'] == '' || $filtros['field_nacionalidad_target_id'] == 'All'
        ? NULL
        : intval($filtros['field_nacionalidad_target_id']),
      "permisos" => $filtros['permission'] == '' || $filtros['permission'] == 'All'
        ? NULL
        : $filtros['permission'],
    ];

    $datos = $repositorio->obtenerDatosUsuarioPorFiltros($lista_filtros);

    $cabecera = [
      'cabecera' => [
        'NOMBRE DE USUARIO',
        'EMAIL',
        'ESTADO',
        'ROLES',
        'EDAD',
        'NACIONALIDAD',
      ],
      'tipo' => $tipo,
      'nombre_archivo' => 'izertis_usuarios_exportar',
    ];

    $archivo = $proceso->procesoExportarUsuarios($cabecera, $datos);

    $response = new BinaryFileResponse($archivo['camino_archivo']);
    $response->headers->set('Content-Type', $archivo["archivo_cabecera"]);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $archivo['nombre_archivo']);
    $response->deleteFileAfterSend();

    return $response;
  }

  /**
   * Get Params URL.
   *
   * @param string $url
   *   Url string.
   *
   * @return mixed
   *   Return.
   */
  public function getUrlParams($url) {
    $partes = parse_url($url);
    parse_str($partes['query'], $vars);
    return $vars;
  }

}
