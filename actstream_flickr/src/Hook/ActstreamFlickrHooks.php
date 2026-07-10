<?php

namespace Drupal\actstream_flickr\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the actstream_flickr module.
 */
class ActstreamFlickrHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ActstreamFlickrHooks instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly AccountProxyInterface $currentUser,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Implements hook_actstream_services().
   */
  #[Hook('actstream_services')]
  public function actstreamServices(): array {
    return [
      'flickr' => [
        'type' => 'flickr',
        'name' => $this->t('Flickr'),
        'verb' => $this->t('photographed'),
        'icon' => 'https://www.flickr.com/favicon.ico',
      ],
    ];
  }

  /**
   * Implements hook_actstream_flickr_items_fetch().
   */
  #[Hook('actstream_flickr_items_fetch')]
  public function fetchItems(int $uid, mixed $data): array {
    $api_key = $this->configFactory->get('actstream_flickr.settings')->get('api_key');
    if (empty($api_key) || empty($data['username'])) {
      return [];
    }

    $logger = $this->loggerFactory->get('actstream_flickr');

    try {
      $flickr = new \Samwilson\PhpFlickr\PhpFlickr($api_key);

      $person = $flickr->people()->findByUsername($data['username']);
      if (empty($person)) {
        return [];
      }
      $nsid = $person['id'] ?? $person['nsid'] ?? NULL;
      if (!$nsid) {
        return [];
      }

      $result = $flickr->people()->getPublicPhotos($nsid, NULL, 'date_upload', 20);
      if (empty($result['photo'])) {
        return [];
      }

      $items = [];
      foreach ($result['photo'] as $photo) {
        $page_url = 'https://www.flickr.com/photos/' . rawurlencode($photo['owner']) . '/' . $photo['id'] . '/';
        $img_url = 'https://farm' . $photo['farm'] . '.staticflickr.com/' . $photo['server'] . '/' . $photo['id'] . '_' . $photo['secret'] . '.jpg';
        $items[] = [
          'title' => $photo['title'] ?: $this->t('Untitled photo'),
          'body' => $img_url,
          'link' => $page_url,
          'timestamp' => isset($photo['dateupload']) ? (int) $photo['dateupload'] : time(),
          'guid' => 'flickr:' . $photo['id'],
          'raw' => json_encode($photo),
        ];
      }
      return $items;
    }
    catch (\Exception $e) {
      $logger->error('Flickr API error for uid @uid: @msg', [
        '@uid' => $uid,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Implements hook_form_actstream_accounts_form_alter().
   */
  #[Hook('form_actstream_accounts_form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : $this->currentUser->id();
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

    $form['#submit'][] = [$this, 'formSubmit'];
  }

  /**
   * Form submit handler for the Flickr account configuration.
   */
  public function formSubmit(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : $this->currentUser->id();
    $username = trim($form_state->getValue('actstream_flickr_username') ?? '');

    if ($username !== '') {
      actstream_account_save('flickr', ['username' => $username], $uid);
    }
    else {
      actstream_account_delete('flickr', $uid);
    }
  }

}
