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
    'actstream_twitter_search',
    'actstream_instagram_search',
    'actstream_facebook_page',
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
    $this->installConfig([
      'actstream_flickr',
      'actstream_lastfm',
      'actstream_twitter_search',
      'actstream_instagram_search',
      'actstream_facebook_page',
    ]);
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
   * Tests actstream_services_load() returns all registered services.
   */
  public function testAllServicesRegistered(): void {
    drupal_static_reset('actstream_services_load');
    $services = actstream_services_load();

    $this->assertArrayHasKey('feed', $services);
    $this->assertArrayHasKey('flickr', $services);
    $this->assertArrayHasKey('lastfm', $services);
    $this->assertArrayHasKey('twitter', $services);
    $this->assertArrayHasKey('twitter_search', $services);
    $this->assertArrayHasKey('instagram_search', $services);
    $this->assertArrayHasKey('facebook_page', $services);
  }

  /**
   * Tests that actstream_twitter_search registers the twitter_search service.
   */
  public function testTwitterSearchServiceRegistration(): void {
    drupal_static_reset('actstream_services_load');
    $services = actstream_services_load();

    $this->assertArrayHasKey('twitter_search', $services);
    $this->assertSame('twitter_search', $services['twitter_search']['type']);
    $this->assertArrayHasKey('name', $services['twitter_search']);
    $this->assertArrayHasKey('verb', $services['twitter_search']);
  }

  /**
   * Tests that actstream_instagram_search registers the instagram_search service.
   */
  public function testInstagramSearchServiceRegistration(): void {
    drupal_static_reset('actstream_services_load');
    $services = actstream_services_load();

    $this->assertArrayHasKey('instagram_search', $services);
    $this->assertSame('instagram_search', $services['instagram_search']['type']);
  }

  /**
   * Tests that actstream_facebook_page registers the facebook_page service.
   */
  public function testFacebookPageServiceRegistration(): void {
    drupal_static_reset('actstream_services_load');
    $services = actstream_services_load();

    $this->assertArrayHasKey('facebook_page', $services);
    $this->assertSame('facebook_page', $services['facebook_page']['type']);
  }

  /**
   * Tests twitter_search fetch returns [] with no token.
   */
  public function testTwitterSearchFetchNoToken(): void {
    $items = actstream_twitter_search_actstream_twitter_search_items_fetch(0, ['hashtag' => 'drupalcon']);
    $this->assertSame([], $items);
  }

  /**
   * Tests twitter_search fetch returns [] with no hashtag.
   */
  public function testTwitterSearchFetchNoHashtag(): void {
    \Drupal::configFactory()
      ->getEditable('actstream_twitter_search.settings')
      ->set('bearer_token', 'tok')
      ->save();

    $items = actstream_twitter_search_actstream_twitter_search_items_fetch(0, []);
    $this->assertSame([], $items);
  }

  /**
   * Tests instagram_search fetch returns [] with no credentials.
   */
  public function testInstagramSearchFetchNoCredentials(): void {
    $items = actstream_instagram_search_actstream_instagram_search_items_fetch(0, ['hashtag' => 'drupalcon']);
    $this->assertSame([], $items);
  }

  /**
   * Tests instagram_search fetch returns [] with no hashtag.
   */
  public function testInstagramSearchFetchNoHashtag(): void {
    \Drupal::configFactory()
      ->getEditable('actstream_instagram_search.settings')
      ->set('access_token', 'tok')
      ->set('user_id', '12345')
      ->save();

    $items = actstream_instagram_search_actstream_instagram_search_items_fetch(0, []);
    $this->assertSame([], $items);
  }

  /**
   * Tests facebook_page fetch returns [] with no credentials.
   */
  public function testFacebookPageFetchNoCredentials(): void {
    $items = actstream_facebook_page_actstream_facebook_page_items_fetch(0, []);
    $this->assertSame([], $items);
  }

}
