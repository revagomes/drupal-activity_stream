<?php

namespace Drupal\Tests\actstream\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that each sub-module registers its service and returns items safely.
 *
 * @group actstream
 */
class ActstreamSubmoduleServicesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'actstream',
    'actstream_feed',
    'actstream_flickr',
    'actstream_lastfm',
    'actstream_twitter',
    'field',
    'filter',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('actstream_item');
    $this->installSchema('actstream', ['actstream_account']);
    $this->installConfig(['actstream_flickr', 'actstream_lastfm']);
  }

  /**
   * Tests that actstream_feed registers the feed service.
   */
  public function testFeedServiceRegistration(): void {
    $services = actstream_services_load();

    $this->assertArrayHasKey('feed', $services);
    $this->assertSame('feed', $services['feed']['type']);
    $this->assertArrayHasKey('name', $services['feed']);
    $this->assertArrayHasKey('verb', $services['feed']);
  }

  /**
   * Tests that actstream_flickr registers the flickr service.
   */
  public function testFlickrServiceRegistration(): void {
    $services = actstream_services_load();

    $this->assertArrayHasKey('flickr', $services);
    $this->assertSame('flickr', $services['flickr']['type']);
    $this->assertArrayHasKey('name', $services['flickr']);
    $this->assertArrayHasKey('verb', $services['flickr']);
  }

  /**
   * Tests that actstream_lastfm registers the lastfm service.
   */
  public function testLastfmServiceRegistration(): void {
    $services = actstream_services_load();

    $this->assertArrayHasKey('lastfm', $services);
    $this->assertSame('lastfm', $services['lastfm']['type']);
    $this->assertArrayHasKey('name', $services['lastfm']);
    $this->assertArrayHasKey('verb', $services['lastfm']);
  }

  /**
   * Tests that actstream_twitter registers the twitter service.
   */
  public function testTwitterServiceRegistration(): void {
    $services = actstream_services_load();

    $this->assertArrayHasKey('twitter', $services);
    $this->assertSame('twitter', $services['twitter']['type']);
    $this->assertArrayHasKey('name', $services['twitter']);
    $this->assertArrayHasKey('verb', $services['twitter']);
  }

  /**
   * Tests that flickr fetch returns empty array when no API key is set.
   */
  public function testFlickrFetchWithNoApiKey(): void {
    $items = actstream_flickr_actstream_flickr_items_fetch(1, ['username' => 'testuser']);
    $this->assertSame([], $items);
  }

  /**
   * Tests that flickr fetch returns empty array when no username is set.
   */
  public function testFlickrFetchWithNoUsername(): void {
    \Drupal::configFactory()
      ->getEditable('actstream_flickr.settings')
      ->set('api_key', 'somekey')
      ->save();

    $items = actstream_flickr_actstream_flickr_items_fetch(1, []);
    $this->assertSame([], $items);
  }

  /**
   * Tests that lastfm fetch returns empty array when no API key is set.
   */
  public function testLastfmFetchWithNoApiKey(): void {
    $items = actstream_lastfm_actstream_lastfm_items_fetch(1, ['username' => 'testuser']);
    $this->assertSame([], $items);
  }

  /**
   * Tests that lastfm fetch returns empty array when no username is set.
   */
  public function testLastfmFetchWithNoUsername(): void {
    \Drupal::configFactory()
      ->getEditable('actstream_lastfm.settings')
      ->set('api_key', 'somekey')
      ->save();

    $items = actstream_lastfm_actstream_lastfm_items_fetch(1, []);
    $this->assertSame([], $items);
  }

  /**
   * Tests that twitter fetch always returns an empty array (stub).
   */
  public function testTwitterFetchReturnsEmpty(): void {
    $items = actstream_twitter_actstream_twitter_items_fetch(1, 'twitteruser');
    $this->assertSame([], $items);
  }

  /**
   * Tests actstream_services_load() returns all four registered services.
   */
  public function testAllServicesRegistered(): void {
    $services = actstream_services_load();

    $this->assertArrayHasKey('feed', $services);
    $this->assertArrayHasKey('flickr', $services);
    $this->assertArrayHasKey('lastfm', $services);
    $this->assertArrayHasKey('twitter', $services);
  }

}
