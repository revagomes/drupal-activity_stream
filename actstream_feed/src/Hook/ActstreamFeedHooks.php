<?php

namespace Drupal\actstream_feed\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the actstream_feed module.
 */
class ActstreamFeedHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_form_actstream_accounts_form_alter().
   */
  #[Hook('form_actstream_accounts_form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : \Drupal::currentUser()->id();
    $feed_urls = actstream_account_load('feed', $uid);
    $feed_urls_string = is_array($feed_urls) ? implode("\n", $feed_urls) : '';

    $form['actstream_feed'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Feed'),
    ];
    $form['actstream_feed']['actstream_feed_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Feed URLs'),
      '#description' => $this->t('Multiple RSS or Atom feeds are allowed; place each on a separate line.'),
      '#default_value' => $feed_urls_string,
    ];

    $form['#submit'][] = 'actstream_feed_form_actstream_accounts_form_submit';
  }

}
