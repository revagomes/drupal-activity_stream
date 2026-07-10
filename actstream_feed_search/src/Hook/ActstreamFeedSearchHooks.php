<?php

namespace Drupal\actstream_feed_search\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the actstream_feed_search module.
 */
class ActstreamFeedSearchHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ActstreamFeedSearchHooks instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly FileSystemInterface $fileSystem,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Implements hook_actstream_services().
   */
  #[Hook('actstream_services')]
  public function actstreamServices(): array {
    return [
      'feed_search' => [
        'type' => 'feed_search',
        'name' => $this->t('Feed Search'),
        'verb' => $this->t('posted'),
        'icon' => 'https://www.drupal.org/favicon.ico',
      ],
    ];
  }

  /**
   * Implements hook_actstream_feed_search_items_fetch().
   *
   * Fetches all feed URLs from actstream_feed_search.settings and returns
   * items whose title or body contains the requested hashtag. The $data
   * argument is expected to carry ['hashtag' => 'drupalcon'] as set by
   * actstream_event cron; a bare hashtag string is also accepted.
   *
   * @param int $uid
   *   Unused — feed search is a site-level operation.
   * @param mixed $data
   *   Array with key 'hashtag', or a bare hashtag string (without #).
   */
  #[Hook('actstream_feed_search_items_fetch')]
  public function fetchItems(int $uid, mixed $data): array {
    $hashtag = is_array($data) ? ($data['hashtag'] ?? '') : ltrim((string) $data, '#');
    $feed_urls = $this->configFactory
      ->get('actstream_feed_search.settings')
      ->get('feed_urls') ?: [];

    if (empty($feed_urls) || empty($hashtag)) {
      return [];
    }

    $needle = mb_strtolower('#' . ltrim($hashtag, '#'));
    $logger = $this->loggerFactory->get('actstream_feed_search');
    $temp_dir = $this->fileSystem->getTempDirectory();
    $items = [];

    foreach ($feed_urls as $url) {
      $url = trim($url);
      if (empty($url)) {
        continue;
      }

      $feed = new \SimplePie();
      $feed->set_cache_location($temp_dir);
      $feed->set_cache_duration(300);
      $feed->set_useragent('Activity Stream for Drupal');
      $feed->set_feed_url($url);

      if (!$feed->init()) {
        $logger->error('Feed search fetch error for @url: @error', [
          '@url' => $url,
          '@error' => $feed->error(),
        ]);
        continue;
      }

      foreach ($feed->get_items() as $feed_item) {
        $title = $feed_item->get_title() ?? '';
        $body = $feed_item->get_description() ?? '';
        $haystack = mb_strtolower($title . ' ' . $body);

        if (!str_contains($haystack, $needle)) {
          continue;
        }

        $items[] = [
          'title' => $title,
          'body' => $body,
          'timestamp' => $feed_item->get_date('U') ?: $this->time->getRequestTime(),
          'guid' => $feed_item->get_id(FALSE),
          'raw' => $feed->get_raw_data(),
          'link' => Html::decodeEntities($feed_item->get_permalink()),
        ];
      }
    }

    return $items;
  }

}
