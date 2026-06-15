<?php

namespace Drupal\actstream_instagram_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Instagram Hashtag Search settings for Activity Stream.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['actstream_instagram_search.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'actstream_instagram_search_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('actstream_instagram_search.settings');

    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#default_value' => $config->get('access_token'),
      '#description' => $this->t('Long-lived User Access Token or System User Token from the <a href=":url">Meta Developer Portal</a>.', [':url' => 'https://developers.facebook.com/apps/']),
      '#required' => TRUE,
    ];

    $form['user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instagram Business user ID'),
      '#default_value' => $config->get('user_id'),
      '#description' => $this->t('The numeric ID of the Instagram Business account connected to your Meta app (used for hashtag search authorization).'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('actstream_instagram_search.settings')
      ->set('access_token', $form_state->getValue('access_token'))
      ->set('user_id', $form_state->getValue('user_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
