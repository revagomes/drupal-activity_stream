<?php

namespace Drupal\Tests\actstream\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the actstream_event cron fetch and pending-save logic.
 *
 * @group actstream
 */
class ActstreamEventCronTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'actstream',
    'actstream_event',
    'actstream_twitter_search',
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
    $this->installConfig(['actstream_twitter_search']);

    // Create uid=1 so event items can be saved with uid=1.
    User::create(['uid' => 1, 'name' => 'admin', 'status' => 1])->save();
  }

  /**
   * Tests _actstream_event_save_pending() saves items with status=0 and event_id.
   */
  public function testSavePendingCreatesStatusZeroItems(): void {
    $items = [
      [
        'title' => 'Hello #drupalcon2026',
        'body' => 'Hello #drupalcon2026',
        'link' => 'https://x.com/user/status/1001',
        'timestamp' => 1718400000,
        'guid' => 'twitter_search:1001',
        'raw' => '{}',
      ],
    ];

    _actstream_event_save_pending('twitter_search', 'dc2026', $items);

    $ids = \Drupal::entityQuery('actstream_item')
      ->condition('actstream_guid', 'twitter_search:1001')
      ->accessCheck(FALSE)
      ->execute();

    $this->assertCount(1, $ids);

    $entity = \Drupal::entityTypeManager()->getStorage('actstream_item')->load(reset($ids));
    $this->assertSame(0, (int) $entity->get('status')->value);
    $this->assertSame('dc2026', $entity->get('actstream_event_id')->value);
    $this->assertSame('twitter_search', $entity->get('service')->value);
  }

  /**
   * Tests that duplicate GUIDs are not saved twice.
   */
  public function testSavePendingDeduplicatesByGuid(): void {
    $items = [
      [
        'title' => 'Post one',
        'body' => 'Post one',
        'link' => 'https://x.com/user/status/2001',
        'timestamp' => 1718400100,
        'guid' => 'twitter_search:2001',
        'raw' => '{}',
      ],
    ];

    _actstream_event_save_pending('twitter_search', 'dc2026', $items);
    _actstream_event_save_pending('twitter_search', 'dc2026', $items);

    $ids = \Drupal::entityQuery('actstream_item')
      ->condition('actstream_guid', 'twitter_search:2001')
      ->accessCheck(FALSE)
      ->execute();

    $this->assertCount(1, $ids);
  }

  /**
   * Tests that actstream_event_cron() runs without error with no active events.
   */
  public function testCronWithNoActiveEvents(): void {
    actstream_event_cron();
    // No exception thrown = pass.
    $this->assertTrue(TRUE);
  }

}
