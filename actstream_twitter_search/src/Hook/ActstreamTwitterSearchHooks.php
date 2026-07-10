<?php

namespace Drupal\actstream_twitter_search\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;

/**
 * Hook implementations for the actstream_twitter_search module.
 */
class ActstreamTwitterSearchHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ActstreamTwitterSearchHooks instance.
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
      'twitter_search' => [
        'type' => 'twitter_search',
        'name' => $this->t('X (Twitter) Search'),
        'verb' => $this->t('posted'),
        'icon' => 'https://x.com/favicon.ico',
      ],
    ];
  }

  /**
   * Implements hook_actstream_twitter_search_items_fetch().
   *
   * @param int $uid
   *   The user ID (unused — search is not per-user).
   * @param mixed $data
   *   An array with key 'query' or a bare query string.
   */
  #[Hook('actstream_twitter_search_items_fetch')]
  public function fetchItems(int $uid, mixed $data): array {
    $bearer_token = $this->configFactory
      ->get('actstream_twitter.settings')
      ->get('bearer_token');

    $query = is_array($data) ? ($data['query'] ?? '') : (string) $data;
    if (empty($bearer_token) || empty($query)) {
      return [];
    }

    $logger = $this->loggerFactory->get('actstream_twitter_search');

    try {
      $response = $this->httpClient->get(
        'https://api.twitter.com/2/tweets/search/recent',
        [
          'headers' => ['Authorization' => 'Bearer ' . $bearer_token],
          'query' => [
            'query' => $query . ' -is:retweet',
            'tweet.fields' => 'created_at,author_id',
            'expansions' => 'author_id',
            'user.fields' => 'username',
            'max_results' => 20,
          ],
        ]
      );

      $payload = json_decode((string) $response->getBody(), TRUE);
      if (empty($payload['data'])) {
        return [];
      }

      // Build a username lookup from the expansions block.
      $usernames = [];
      foreach ($payload['includes']['users'] ?? [] as $u) {
        $usernames[$u['id']] = $u['username'];
      }

      $items = [];
      foreach ($payload['data'] as $tweet) {
        $tweet_id = $tweet['id'];
        $author_id = $tweet['author_id'] ?? '';
        $username = $usernames[$author_id] ?? $author_id;
        $post_url = 'https://x.com/' . rawurlencode($username) . '/status/' . $tweet_id;
        $timestamp = isset($tweet['created_at'])
          ? (int) (new \DateTimeImmutable($tweet['created_at']))->getTimestamp()
          : time();

        $items[] = [
          'title' => mb_substr($tweet['text'], 0, 255),
          'body' => $tweet['text'],
          'link' => $post_url,
          'timestamp' => $timestamp,
          'guid' => 'twitter:' . $tweet_id,
          'raw' => json_encode($tweet),
        ];
      }
      return $items;
    }
    catch (\Exception $e) {
      $logger->error('X Search API error: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

}
