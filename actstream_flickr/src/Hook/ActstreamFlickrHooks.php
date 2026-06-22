<?php

namespace Drupal\actstream_flickr\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the actstream_flickr module.
 */
class ActstreamFlickrHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_form_actstream_accounts_form_alter().
   */
  #[Hook('form_actstream_accounts_form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : \Drupal::currentUser()->id();
    $data = actstream_account_load('flickr', $uid) ?: [];

    $form['actstream_flickr'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Flickr'),
    ];
    $form['actstream_flickr']['actstream_flickr_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flickr username or NSID'),
      '#default_value' => $data['username'] ?? '',
      '#description' => $this->t('Your Flickr username or NSID (e.g. 12345678@N00).'),
    ];

    $form['#submit'][] = 'actstream_flickr_form_actstream_accounts_form_submit';
  }

}
