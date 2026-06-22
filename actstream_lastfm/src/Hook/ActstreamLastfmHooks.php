<?php

namespace Drupal\actstream_lastfm\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the actstream_lastfm module.
 */
class ActstreamLastfmHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_form_actstream_accounts_form_alter().
   */
  #[Hook('form_actstream_accounts_form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : \Drupal::currentUser()->id();
    $data = actstream_account_load('lastfm', $uid) ?: [];

    $form['actstream_lastfm'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Last.fm'),
    ];
    $form['actstream_lastfm']['actstream_lastfm_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last.fm username'),
      '#default_value' => $data['username'] ?? '',
      '#description' => $this->t('Your Last.fm username.'),
    ];

    $form['#submit'][] = 'actstream_lastfm_form_actstream_accounts_form_submit';
  }

}
