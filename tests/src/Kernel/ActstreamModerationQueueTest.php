<?php

namespace Drupal\Tests\actstream\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the moderation queue form logic.
 *
 * @group actstream
 */
class ActstreamModerationQueueTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'actstream',
    'actstream_event',
    'actstream_mod_queue',
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

    User::create(['uid' => 1, 'name' => 'admin', 'status' => 1])
      ->save();
  }

  /**
   * Tests that pending event items can be queried and approved.
   */
  public function testApproveItem(): void {
    $storage = \Drupal::entityTypeManager()
      ->getStorage('actstream_item');

    // Create a pending event item (mirrors what cron saves).
    $item = $storage->create([
      'service' => 'twitter_search',
      'uid' => 1,
      'status' => 0,
      'actstream_event_id' => 'dc2026',
      'title' => 'Test tweet',
      'actstream_guid' => 'twitter_search:9999',
      'actstream_link' => 'https://x.com/user/status/9999',
    ]);
    $item->save();
    $item_id = $item->id();

    // Verify it appears as pending.
    $pending = \Drupal::entityQuery('actstream_item')
      ->condition('actstream_event_id', '', '!=')
      ->condition('status', 0)
      ->accessCheck(FALSE)
      ->execute();
    $this->assertContains($item_id, array_values($pending));

    // Approve it.
    $item->set('status', 1)->save();

    $pending_after = \Drupal::entityQuery('actstream_item')
      ->condition('actstream_event_id', '', '!=')
      ->condition('status', 0)
      ->accessCheck(FALSE)
      ->execute();
    $this->assertNotContains(
      $item_id,
      array_values($pending_after)
    );

    // Confirm it now appears as published.
    $published = \Drupal::entityQuery('actstream_item')
      ->condition('actstream_event_id', '', '!=')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();
    $this->assertContains($item_id, array_values($published));
  }

  /**
   * Tests that user stream items do not appear in the queue.
   */
  public function testUserStreamItemsExcludedFromQueue(): void {
    $storage = \Drupal::entityTypeManager()
      ->getStorage('actstream_item');

    // Create a regular user stream item (no event ID).
    $item = $storage->create([
      'service' => 'twitter',
      'uid' => 1,
      'status' => 0,
      'actstream_event_id' => '',
      'title' => 'User stream tweet',
      'actstream_guid' => 'twitter:8888',
      'actstream_link' => 'https://x.com/user/status/8888',
    ]);
    $item->save();

    $pending = \Drupal::entityQuery('actstream_item')
      ->condition('actstream_event_id', '', '!=')
      ->condition('status', 0)
      ->accessCheck(FALSE)
      ->execute();

    $this->assertNotContains($item->id(), array_values($pending));
  }

}
