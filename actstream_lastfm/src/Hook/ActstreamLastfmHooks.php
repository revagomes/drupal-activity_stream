<?php

namespace Drupal\actstream_lastfm\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;

/**
 * Hook implementations for the actstream_lastfm module.
 */
class ActstreamLastfmHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ActstreamLastfmHooks instance.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
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
      'lastfm' => [
        'type' => 'lastfm',
        'name' => $this->t('Last.fm'),
        'verb' => $this->t('listened to'),
        'icon' => 'https://www.last.fm/static/images/favicon.702b239b6194.ico',
      ],
    ];
  }

  /**
   * Implements hook_actstream_lastfm_items_fetch().
   */
  #[Hook('actstream_lastfm_items_fetch')]
  public function fetchItems(int $uid, mixed $data): array {
    $api_key = $this->configFactory->get('actstream_lastfm.settings')->get('api_key');
    if (empty($api_key) || empty($data['username'])) {
      return [];
    }

    $logger = $this->loggerFactory->get('actstream_lastfm');

    try {
      $url = 'https://ws.audioscrobbler.com/2.0/?' . http_build_query([
        'method' => 'user.getRecentTracks',
        'user' => $data['username'],
        'api_key' => $api_key,
        'format' => 'json',
        'limit' => 20,
      ]);

      $response = $this->httpClient->get($url);
      $payload = json_decode((string) $response->getBody(), TRUE);

      if (empty($payload['recenttracks']['track'])) {
        return [];
      }

      $items = [];
      foreach ($payload['recenttracks']['track'] as $track) {
        // Skip "now playing" entries — they have no timestamp.
        if (!empty($track['@attr']['nowplaying'])) {
          continue;
        }
        $timestamp = isset($track['date']['uts']) ? (int) $track['date']['uts'] : 0;
        $artist = $track['artist']['#text'] ?? '';
        $name = $track['name'] ?? '';
        $link = $track['url'] ?? '';
        $guid = $link ?: ('lastfm:' . $data['username'] . ':' . $timestamp . ':' . $name);

        $items[] = [
          'title' => $name,
          'body' => $artist,
          'link' => $link,
          'timestamp' => $timestamp,
          'guid' => $guid,
          'raw' => json_encode($track),
        ];
      }
      return $items;
    }
    catch (\Exception $e) {
      $logger->error('Last.fm API error for uid @uid: @msg', [
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

    $form['#submit'][] = [$this, 'formSubmit'];
  }

  /**
   * Form submit handler for the Last.fm account configuration.
   */
  public function formSubmit(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : $this->currentUser->id();
    $username = trim($form_state->getValue('actstream_lastfm_username') ?? '');

    if ($username !== '') {
      actstream_account_save('lastfm', ['username' => $username], $uid);
    }
    else {
      actstream_account_delete('lastfm', $uid);
    }
  }

}
