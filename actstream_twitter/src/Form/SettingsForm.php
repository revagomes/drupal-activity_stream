<?php

namespace Drupal\actstream_twitter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure X (Twitter) API settings for Activity Stream.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['actstream_twitter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'actstream_twitter_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('actstream_twitter.settings');

    $form['api_tier_notice'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--warning">' . $this->t(
        'The X (Twitter) v2 API requires a <strong>paid Basic tier</strong> or higher. Free API access does not include user timelines — fetch calls will silently return zero items without a paid subscription. See the <a href=":url" target="_blank" rel="noopener noreferrer">X API pricing page</a>.',
        [':url' => 'https://developer.x.com/en/portal/products']
      ) . '</div>',
    ];

    $form['bearer_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('X (Twitter) Bearer token'),
      '#default_value' => $config->get('bearer_token'),
      '#description' => $this->t('App-only Bearer token from the <a href=":url">X Developer Portal</a>. Grants read access to public posts.', [':url' => 'https://developer.x.com/en/portal/dashboard']),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('actstream_twitter.settings')
      ->set('bearer_token', $form_state->getValue('bearer_token'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
