<?php

namespace Drupal\actstream_facebook_page\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Facebook Page settings for Activity Stream.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['actstream_facebook_page.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'actstream_facebook_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('actstream_facebook_page.settings');

    $form['page_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Page ID'),
      '#default_value' => $config->get('page_id'),
      '#description' => $this->t('The numeric ID of the Facebook Page whose posts will be aggregated.'),
      '#required' => TRUE,
    ];

    $form['page_access_token'] = [
      '#type' => 'password',
      '#title' => $this->t('Page access token'),
      '#description' => $this->t('Page Access Token from the <a href=":url">Meta Developer Portal</a>. Leave blank to keep the existing token.', [':url' => 'https://developers.facebook.com/apps/'])
        . ($config->get('page_access_token') ? ' ' . $this->t('(Currently saved.)') : ''),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('actstream_facebook_page.settings');
    $page_access_token = $form_state->getValue('page_access_token');
    if (!empty($page_access_token)) {
      $config->set('page_access_token', $page_access_token);
    }
    $config->set('page_id', $form_state->getValue('page_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
