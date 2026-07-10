<?php

namespace Drupal\actstream_instagram_search\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;

/**
 * Hook implementations for the actstream_instagram_search module.
 */
class ActstreamInstagramSearchHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ActstreamInstagramSearchHooks instance.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Implements hook_actstream_services().
   */
  #[Hook('actstream_services')]
  public function actstreamServices(): array {
    return [
      'instagram_search' => [
        'type' => 'instagram_search',
        'name' => $this->t('Instagram Hashtag Search'),
        'verb' => $this->t('posted'),
        'icon' => 'https://www.instagram.com/favicon.ico',
      ],
    ];
  }

  /**
   * Implements hook_actstream_instagram_search_items_fetch().
   *
   * Uses the Instagram Basic Display API or the Graph API hashtag search
   * endpoint (requires a business/creator account and page token).
   *
   * @param int $uid
   *   The user ID (unused — search is not per-user).
   * @param mixed $data
   *   An array with key 'hashtag' or a bare hashtag string (without #).
   */
  #[Hook('actstream_instagram_search_items_fetch')]
  public function fetchItems(int $uid, mixed $data): array {
    $config = $this->configFactory->get('actstream_instagram_search.settings');
    $access_token = $config->get('access_token');
    $user_id = $config->get('user_id');
    $hashtag = is_array($data) ? ($data['hashtag'] ?? '') : ltrim((string) $data, '#');

    if (empty($access_token) || empty($user_id) || empty($hashtag)) {
      return [];
    }

    $logger = $this->loggerFactory->get('actstream_instagram_search');

    try {
      // Step 1: resolve hashtag to a hashtag_id.
      $tag_response = $this->httpClient->get(
        'https://graph.facebook.com/v19.0/ig_hashtag_search',
        [
          'query' => [
            'user_id' => $user_id,
            'q' => $hashtag,
            'access_token' => $access_token,
          ],
        ]
      );
      $tag_payload = json_decode((string) $tag_response->getBody(), TRUE);
      if (empty($tag_payload['data'][0]['id'])) {
        return [];
      }
      $hashtag_id = $tag_payload['data'][0]['id'];

      // Step 2: fetch recent media for the hashtag.
      $media_response = $this->httpClient->get(
        'https://graph.facebook.com/v19.0/' . $hashtag_id . '/recent_media',
        [
          'query' => [
            'user_id' => $user_id,
            'fields' => 'id,caption,permalink,timestamp',
            'limit' => 20,
            'access_token' => $access_token,
          ],
        ]
      );
      $media_payload = json_decode((string) $media_response->getBody(), TRUE);
      if (empty($media_payload['data'])) {
        return [];
      }

      $items = [];
      foreach ($media_payload['data'] as $post) {
        $caption = $post['caption'] ?? '';
        $timestamp = isset($post['timestamp'])
          ? (int) (new \DateTimeImmutable($post['timestamp']))->getTimestamp()
          : time();

        $items[] = [
          'title' => $caption !== '' ? mb_substr($caption, 0, 200) : (string) $this->t('Instagram post'),
          'body' => $caption,
          'link' => $post['permalink'] ?? '',
          'timestamp' => $timestamp,
          'guid' => 'instagram_search:' . $post['id'],
          'raw' => json_encode($post),
        ];
      }
      return $items;
    }
    catch (\Exception $e) {
      $logger->error('Instagram Search API error: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

}
