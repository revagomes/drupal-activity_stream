<?php

namespace Drupal\actstream_wall\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Social Wall display settings for Activity Stream.
 */
class WallSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['actstream_wall.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'actstream_wall_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('actstream_wall.settings');

    $form['refresh_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Auto-refresh interval (milliseconds)'),
      '#description' => $this->t('How often the social wall polls for new items. Set to 0 to disable auto-refresh.'),
      '#default_value' => $config->get('refresh_interval') ?? 30000,
      '#min' => 0,
      '#step' => 1000,
      '#required' => TRUE,
    ];

    $form['items_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Items per page'),
      '#description' => $this->t('Maximum number of activity items displayed on the social wall.'),
      '#default_value' => $config->get('items_per_page') ?? 20,
      '#min' => 1,
      '#max' => 200,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('actstream_wall.settings')
      ->set('refresh_interval', (int) $form_state->getValue('refresh_interval'))
      ->set('items_per_page', (int) $form_state->getValue('items_per_page'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
