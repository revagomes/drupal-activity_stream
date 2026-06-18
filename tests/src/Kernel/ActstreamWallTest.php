<?php

namespace Drupal\Tests\actstream\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the actstream_wall item queries.
 *
 * @group actstream
 */
class ActstreamWallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'actstream',
    'actstream_event',
    'actstream_wall',
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
    $this->installConfig(['actstream_wall']);

    User::create(['uid' => 1, 'name' => 'admin', 'status' => 1])->save();
  }

  /**
   * Tests that only status=1 event items appear on the wall.
   */
  public function testOnlyPublishedEventItemsAppear(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('actstream_item');

    // Pending event item — should NOT appear on the wall.
    $pending = $storage->create([
      'service' => 'twitter_search',
      'uid' => 1,
      'status' => 0,
      'actstream_event_id' => 'dc2026',
      'title' => 'Pending post',
      'actstream_guid' => 'twitter_search:pending1',
      'actstream_link' => 'https://x.com/user/status/pending1',
    ]);
    $pending->save();

    // Approved event item — SHOULD appear on the wall.
    $approved = $storage->create([
      'service' => 'twitter_search',
      'uid' => 1,
      'status' => 1,
      'actstream_event_id' => 'dc2026',
      'title' => 'Approved post',
      'actstream_guid' => 'twitter_search:approved1',
      'actstream_link' => 'https://x.com/user/status/approved1',
    ]);
    $approved->save();

    // User stream item (no event ID) — should NOT appear on the wall.
    $user_item = $storage->create([
      'service' => 'twitter',
      'uid' => 1,
      'status' => 1,
      'actstream_event_id' => '',
      'title' => 'User stream post',
      'actstream_guid' => 'twitter:user1',
      'actstream_link' => 'https://x.com/user/status/user1',
    ]);
    $user_item->save();

    $wall_ids = \Drupal::entityQuery('actstream_item')
      ->condition('status', 1)
      ->condition('actstream_event_id', '', '!=')
      ->accessCheck(FALSE)
      ->execute();

    $this->assertContains($approved->id(), array_values($wall_ids));
    $this->assertNotContains($pending->id(), array_values($wall_ids));
    $this->assertNotContains($user_item->id(), array_values($wall_ids));
  }

  /**
   * Tests items since a timestamp are correctly filtered.
   */
  public function testItemsSinceTimestamp(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('actstream_item');

    $old = $storage->create([
      'service' => 'twitter_search',
      'uid' => 1,
      'status' => 1,
      'actstream_event_id' => 'dc2026',
      'title' => 'Old post',
      'actstream_guid' => 'twitter_search:old1',
      'actstream_link' => 'https://x.com/user/status/old1',
      'created' => 1000,
    ]);
    $old->save();

    $new = $storage->create([
      'service' => 'twitter_search',
      'uid' => 1,
      'status' => 1,
      'actstream_event_id' => 'dc2026',
      'title' => 'New post',
      'actstream_guid' => 'twitter_search:new1',
      'actstream_link' => 'https://x.com/user/status/new1',
      'created' => 2000,
    ]);
    $new->save();

    $since_ids = \Drupal::entityQuery('actstream_item')
      ->condition('status', 1)
      ->condition('actstream_event_id', '', '!=')
      ->condition('created', 1500, '>')
      ->accessCheck(FALSE)
      ->execute();

    $this->assertContains($new->id(), array_values($since_ids));
    $this->assertNotContains($old->id(), array_values($since_ids));
  }

}
