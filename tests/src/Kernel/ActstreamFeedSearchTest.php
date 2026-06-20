<?php

namespace Drupal\Tests\actstream\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the actstream_feed_search hook and hashtag filtering.
 *
 * @group actstream
 */
#[RunTestsInSeparateProcesses]
class ActstreamFeedSearchTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'actstream',
    'actstream_event',
    'actstream_feed',
    'actstream_feed_search',
    'field',
    'filter',
    'system',
    'text',
    'user',
  ];

  /**
   * Path to the temporary RSS fixture file.
   */
  private string $fixturePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['actstream_feed_search']);

    // Write a minimal RSS 2.0 fixture with two items: one mentioning #drupal,
    // one that does not. Use sys_get_temp_dir() for an absolute path that
    // simplexml_load_file() can reach via a file:// URL.
    $this->fixturePath = sys_get_temp_dir() . '/actstream_feed_search_fixture.xml';
    file_put_contents($this->fixturePath, <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Test Feed</title>
    <link>https://example.com</link>
    <item>
      <title>Hello #drupal community</title>
      <description>A post about #drupal things.</description>
      <link>https://example.com/item/1</link>
      <guid>guid-drupal-1</guid>
      <pubDate>Fri, 20 Jun 2026 10:00:00 +0000</pubDate>
    </item>
    <item>
      <title>Unrelated post about cats</title>
      <description>Nothing relevant here.</description>
      <link>https://example.com/item/2</link>
      <guid>guid-cats-2</guid>
      <pubDate>Fri, 20 Jun 2026 11:00:00 +0000</pubDate>
    </item>
  </channel>
</rss>
XML);
  }

  /**
   * Tests that feed_search registers itself via hook_actstream_services().
   */
  public function testServiceRegistration(): void {
    drupal_static_reset('actstream_services_load');
    $services = actstream_services_load();

    $this->assertArrayHasKey('feed_search', $services);
    $this->assertSame('feed_search', $services['feed_search']['type']);
    $this->assertArrayHasKey('name', $services['feed_search']);
    $this->assertArrayHasKey('verb', $services['feed_search']);
  }

  /**
   * Tests that fetch returns [] when no feed URLs are configured.
   */
  public function testFetchWithNoUrls(): void {
    $items = actstream_feed_search_actstream_feed_search_items_fetch(
      1,
      ['hashtag' => 'drupal']
    );
    $this->assertSame([], $items);
  }

  /**
   * Tests that fetch returns [] when hashtag is empty.
   */
  public function testFetchWithEmptyHashtag(): void {
    \Drupal::configFactory()
      ->getEditable('actstream_feed_search.settings')
      ->set('feed_urls', ['file://' . $this->fixturePath])
      ->save();

    $items = actstream_feed_search_actstream_feed_search_items_fetch(1, []);
    $this->assertSame([], $items);
  }

  /**
   * Tests that matching items are returned when hashtag is found.
   */
  public function testFetchFiltersMatchingHashtag(): void {
    \Drupal::configFactory()
      ->getEditable('actstream_feed_search.settings')
      ->set('feed_urls', ['file://' . $this->fixturePath])
      ->save();

    $items = actstream_feed_search_actstream_feed_search_items_fetch(
      1,
      ['hashtag' => 'drupal']
    );

    $this->assertCount(1, $items);
    $this->assertStringContainsStringIgnoringCase('drupal', $items[0]['title']);
    $this->assertSame('guid-drupal-1', $items[0]['guid']);
    $this->assertSame('https://example.com/item/1', $items[0]['link']);
    $this->assertNotEmpty($items[0]['timestamp']);
  }

  /**
   * Tests that non-matching hashtag returns no items.
   */
  public function testFetchReturnsEmptyForNonMatchingHashtag(): void {
    \Drupal::configFactory()
      ->getEditable('actstream_feed_search.settings')
      ->set('feed_urls', ['file://' . $this->fixturePath])
      ->save();

    $items = actstream_feed_search_actstream_feed_search_items_fetch(
      1,
      ['hashtag' => 'symfony']
    );

    $this->assertSame([], $items);
  }

  /**
   * Tests that hashtag matching is case-insensitive and strips leading #.
   */
  public function testFetchHashtagCaseInsensitiveAndStripsHash(): void {
    \Drupal::configFactory()
      ->getEditable('actstream_feed_search.settings')
      ->set('feed_urls', ['file://' . $this->fixturePath])
      ->save();

    // Pass without leading # and in uppercase.
    $items = actstream_feed_search_actstream_feed_search_items_fetch(
      1,
      ['hashtag' => 'DRUPAL']
    );

    $this->assertCount(1, $items);
  }

  /**
   * Tests that string data (not array) is accepted as hashtag.
   */
  public function testFetchAcceptsStringData(): void {
    \Drupal::configFactory()
      ->getEditable('actstream_feed_search.settings')
      ->set('feed_urls', ['file://' . $this->fixturePath])
      ->save();

    $items = actstream_feed_search_actstream_feed_search_items_fetch(
      1,
      '#drupal'
    );

    $this->assertCount(1, $items);
  }

}
