<?php

namespace Drupal\actstream_twitter\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the actstream_twitter module.
 */
class ActstreamTwitterHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_form_actstream_accounts_form_alter().
   */
  #[Hook('form_actstream_accounts_form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : \Drupal::currentUser()->id();
    $data = actstream_account_load('twitter', $uid);

    // Normalise legacy storage (bare string) to array.
    if (is_string($data)) {
      $data = ['username' => $data];
    }

    $form['actstream_twitter'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('X (Twitter)'),
    ];
    $form['actstream_twitter']['actstream_twitter_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('X (Twitter) username'),
      '#default_value' => $data['username'] ?? '',
      '#description' => $this->t('Your X username without the @ sign (e.g. drupal).'),
      '#field_prefix' => '@',
    ];

    $form['#submit'][] = 'actstream_twitter_form_actstream_accounts_form_submit';
  }

}
