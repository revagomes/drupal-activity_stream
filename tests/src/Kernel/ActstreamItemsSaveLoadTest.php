<?php

namespace Drupal\Tests\actstream\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests actstream_items_save() deduplication and actstream_items_load().
 *
 * @group actstream
 */
class ActstreamItemsSaveLoadTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'actstream',
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
  }

  /**
   * Tests that actstream_items_save() creates new entities.
   */
  public function testSaveCreatesEntities(): void {
    $user = User::create(['name' => 'saver', 'status' => 1]);
    $user->save();
    $uid = (int) $user->id();

    $items = [
      [
        'title' => 'Item one',
        'body' => 'Body one',
        'link' => 'https://example.com/1',
        'timestamp' => 1700000001,
        'guid' => 'test:1',
        'raw' => '',
      ],
      [
        'title' => 'Item two',
        'body' => 'Body two',
        'link' => 'https://example.com/2',
        'timestamp' => 1700000002,
        'guid' => 'test:2',
        'raw' => '',
      ],
    ];

    actstream_items_save($uid, 'test', $items);

    $ids = \Drupal::entityQuery('actstream_item')
      ->condition('uid', $uid)
      ->condition('service', 'test')
      ->accessCheck(FALSE)
      ->execute();

    $this->assertCount(2, $ids);
  }

  /**
   * Tests that actstream_items_save() deduplicates by GUID.
   */
  public function testSaveDeduplicatesByGuid(): void {
    $user = User::create(['name' => 'dedup', 'status' => 1]);
    $user->save();
    $uid = (int) $user->id();

    $item = [
      'title' => 'Dedup item',
      'body' => 'Same body',
      'link' => 'https://example.com/dedup',
      'timestamp' => 1700000010,
      'guid' => 'dedup:guid:1',
      'raw' => '',
    ];

    actstream_items_save($uid, 'test', [$item]);
    actstream_items_save($uid, 'test', [$item]);

    $ids = \Drupal::entityQuery('actstream_item')
      ->condition('actstream_guid', 'dedup:guid:1')
      ->condition('uid', $uid)
      ->accessCheck(FALSE)
      ->execute();

    $this->assertCount(1, $ids);
  }

  /**
   * Tests that actstream_items_save() updates changed items.
   */
  public function testSaveUpdatesChangedItems(): void {
    $user = User::create(['name' => 'updater', 'status' => 1]);
    $user->save();
    $uid = (int) $user->id();

    $item = [
      'title' => 'Original title',
      'body' => 'Original body',
      'link' => 'https://example.com/update',
      'timestamp' => 1700000020,
      'guid' => 'update:guid:1',
      'raw' => '',
    ];
    actstream_items_save($uid, 'test', [$item]);

    $item['title'] = 'Updated title';
    $item['body'] = 'Updated body';
    actstream_items_save($uid, 'test', [$item]);

    $ids = \Drupal::entityQuery('actstream_item')
      ->condition('actstream_guid', 'update:guid:1')
      ->condition('uid', $uid)
      ->accessCheck(FALSE)
      ->execute();

    $this->assertCount(1, $ids);

    $entity = \Drupal::entityTypeManager()
      ->getStorage('actstream_item')
      ->load(reset($ids));

    $this->assertSame('Updated title', $entity->label());
    $this->assertSame('Updated body', $entity->get('actstream_body')->value);
  }

  /**
   * Tests actstream_items_load() returns published items sorted by date.
   */
  public function testItemsLoad(): void {
    $user = User::create(['name' => 'loader', 'status' => 1]);
    $user->save();
    $uid = (int) $user->id();

    $items = [
      [
        'title' => 'Older item',
        'body' => '',
        'link' => 'https://example.com/older',
        'timestamp' => 1700000100,
        'guid' => 'load:1',
        'raw' => '',
      ],
      [
        'title' => 'Newer item',
        'body' => '',
        'link' => 'https://example.com/newer',
        'timestamp' => 1700000200,
        'guid' => 'load:2',
        'raw' => '',
      ],
    ];
    actstream_items_save($uid, 'test', $items);

    $loaded = actstream_items_load($uid);
    $this->assertCount(2, $loaded);

    $titles = array_map(fn($e) => $e->label(), $loaded);
    $this->assertSame('Newer item', reset($titles));
    $this->assertSame('Older item', end($titles));
  }

  /**
   * Tests actstream_items_load() filters by user.
   */
  public function testItemsLoadFiltersByUser(): void {
    $user1 = User::create(['name' => 'u1', 'status' => 1]);
    $user1->save();
    $user2 = User::create(['name' => 'u2', 'status' => 1]);
    $user2->save();

    actstream_items_save((int) $user1->id(), 'test', [[
      'title' => 'U1 item',
      'body' => '',
      'link' => 'https://example.com/u1',
      'timestamp' => 1700000300,
      'guid' => 'filter:u1:1',
      'raw' => '',
    ]]);
    actstream_items_save((int) $user2->id(), 'test', [[
      'title' => 'U2 item',
      'body' => '',
      'link' => 'https://example.com/u2',
      'timestamp' => 1700000300,
      'guid' => 'filter:u2:1',
      'raw' => '',
    ]]);

    $u1Items = actstream_items_load((int) $user1->id());
    $this->assertCount(1, $u1Items);
    $this->assertSame('U1 item', reset($u1Items)->label());

    $allItems = actstream_items_load();
    $this->assertCount(2, $allItems);
  }

}
