<?php

namespace Drupal\actstream_facebook_page\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;

/**
 * Hook implementations for the actstream_facebook_page module.
 */
class ActstreamFacebookPageHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ActstreamFacebookPageHooks instance.
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
      'facebook_page' => [
        'type' => 'facebook_page',
        'name' => $this->t('Facebook Page'),
        'verb' => $this->t('posted'),
        'icon' => 'https://www.facebook.com/favicon.ico',
      ],
    ];
  }

  /**
   * Implements hook_actstream_facebook_page_items_fetch().
   *
   * The page_access_token and page_id are global site-level settings, not
   * per-user. The $data argument is intentionally ignored.
   */
  #[Hook('actstream_facebook_page_items_fetch')]
  public function fetchItems(int $uid, mixed $data): array {
    $config = $this->configFactory->get('actstream_facebook_page.settings');
    $page_access_token = $config->get('page_access_token');
    $page_id = $config->get('page_id');

    if (empty($page_access_token) || empty($page_id)) {
      return [];
    }

    $logger = $this->loggerFactory->get('actstream_facebook_page');

    try {
      $response = $this->httpClient->get(
        'https://graph.facebook.com/v19.0/' . rawurlencode($page_id) . '/posts',
        [
          'headers' => ['Authorization' => 'Bearer ' . $page_access_token],
          'query' => [
            'fields' => 'id,message,story,permalink_url,created_time',
            'limit' => 20,
          ],
        ]
      );
      $payload = json_decode((string) $response->getBody(), TRUE);

      if (empty($payload['data'])) {
        return [];
      }

      $items = [];
      foreach ($payload['data'] as $post) {
        $text = $post['message'] ?? ($post['story'] ?? '');
        if (empty($text)) {
          continue;
        }
        $timestamp = isset($post['created_time'])
          ? (int) (new \DateTimeImmutable($post['created_time']))->getTimestamp()
          : time();

        $items[] = [
          'title' => mb_substr($text, 0, 255),
          'body' => $text,
          'link' => $post['permalink_url'] ?? '',
          'timestamp' => $timestamp,
          'guid' => 'facebook_page:' . $post['id'],
          'raw' => json_encode($post),
        ];
      }
      return $items;
    }
    catch (\Exception $e) {
      $logger->error('Facebook Page API error: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

}
