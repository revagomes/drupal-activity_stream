<?php

namespace Drupal\actstream_twitter_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure X (Twitter) Hashtag Search settings for Activity Stream.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['actstream_twitter_search.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'actstream_twitter_search_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('actstream_twitter_search.settings');

    $form['bearer_token'] = [
      '#type' => 'password',
      '#title' => $this->t('X (Twitter) Bearer token'),
      '#description' => $this->t('App-only Bearer token from the <a href=":url">X Developer Portal</a>. Grants read access to public posts via the search endpoint. Leave blank to keep the existing token.', [':url' => 'https://developer.x.com/en/portal/dashboard'])
        . ($config->get('bearer_token') ? ' ' . $this->t('(Currently saved.)') : ''),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('actstream_twitter_search.settings');
    $bearer_token = $form_state->getValue('bearer_token');
    if (!empty($bearer_token)) {
      $config->set('bearer_token', $bearer_token);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
