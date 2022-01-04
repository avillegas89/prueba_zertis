<?php

namespace Drupal\izertis_usuarios\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'RangosEdadBlock' block.
 *
 * @Block(
 *  id = "rangos_edad_block",
 *  admin_label = @Translation("Bloque para mostrar Rangos de edad"),
 * )
 */
class RangosEdadBlock extends BlockBase {

  /**
   * Construir el formulario de confiiguración del bloque.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['secciones'] = [
      '#type' => 'textarea',
      '#title' => t('Configuración de Secciones'),
      '#description' => t('Configurar cada sección a vizualizar. Cada línea se tomará en cuenta como una sección. Ej: 18-35 años | 18-35 (Etiqueta visual | min-max)'),
      '#default_value' => $this->configuration['secciones'] ?? "18-35 años|18-35\n35-50 años|35-50\nMás de 50 años|-50\n",
    ];

    return $form;
  }

  /**
   * Guardar el formulario de configuración del bloque.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['secciones'] = $form_state->getValue('secciones');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $izsertis_proc_servicio = \Drupal::service("izertis_usuarios.procesamiento");

    $secciones = $this->configuration["secciones"];
    if (empty($this->configuration["secciones"])) {
      $secciones = "18-35 años|18-35\r\n35-50 años|35-50\r\nMás de 50 años|-50\r\n";
    }

    $build = [];
    $build['#theme'] = 'rangos-edad';
    $build['#configuracion'] = $this->configuration;
    $build['#datos'] = $izsertis_proc_servicio->obtenerRangosUsuarioPorSecciones($secciones);

    $build['#cache']['max-age'] = 0;

    return $build;
  }

}
