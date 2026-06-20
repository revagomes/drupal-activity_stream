<?php

namespace Drupal\actstream_feed_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Activity Stream Feed Search.
 */
class FeedSearchSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['actstream_feed_search.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'actstream_feed_search_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $urls = $this->config('actstream_feed_search.settings')
      ->get('feed_urls') ?: [];

    $form['feed_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('RSS/Atom feed URLs'),
      '#description' => $this->t(
        'One URL per line. Items whose title or body contain the event hashtag will be imported.'
      ),
      '#default_value' => implode("\n", $urls),
      '#rows' => 8,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $raw = $form_state->getValue('feed_urls', '');
    $urls = array_values(array_filter(
      array_map('trim', explode("\n", $raw))
    ));

    $this->config('actstream_feed_search.settings')
      ->set('feed_urls', $urls)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
