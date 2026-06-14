<?php

namespace Drupal\Tests\actstream\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\actstream\Entity\ActivityStreamItem;
use Drupal\user\Entity\User;

/**
 * Tests ActivityStreamItem entity CRUD and base fields.
 *
 * @group actstream
 */
class ActstreamItemEntityTest extends KernelTestBase {

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
   * Tests creating and loading an actstream_item entity.
   */
  public function testCreateAndLoad(): void {
    $user = User::create(['name' => 'tester', 'status' => 1]);
    $user->save();

    $entity = ActivityStreamItem::create([
      'title' => 'Test item',
      'service' => 'feed',
      'uid' => $user->id(),
      'status' => 1,
      'actstream_link' => 'https://example.com/item/1',
      'actstream_guid' => 'feed:example:1',
      'actstream_body' => 'Body text',
      'actstream_raw' => '{"raw":true}',
    ]);
    $entity->save();

    $loaded = \Drupal::entityTypeManager()
      ->getStorage('actstream_item')
      ->load($entity->id());

    $this->assertNotNull($loaded);
    $this->assertSame('Test item', $loaded->label());
    $this->assertSame('feed', $loaded->getService());
    $this->assertSame('https://example.com/item/1', $loaded->getLink());
    $this->assertSame('feed:example:1', $loaded->getGuid());
    $this->assertTrue($loaded->isPublished());
    $this->assertSame('Body text', $loaded->get('actstream_body')->value);
    $this->assertSame('{"raw":true}', $loaded->get('actstream_raw')->value);
    $this->assertSame((int) $user->id(), (int) $loaded->get('uid')->target_id);
  }

  /**
   * Tests updating an actstream_item entity.
   */
  public function testUpdate(): void {
    $entity = ActivityStreamItem::create([
      'title' => 'Original title',
      'service' => 'feed',
      'status' => 1,
      'actstream_guid' => 'guid:update:1',
      'actstream_link' => 'https://example.com/update',
    ]);
    $entity->save();
    $id = $entity->id();

    $entity->set('title', 'Updated title');
    $entity->set('status', 0);
    $entity->save();

    $loaded = \Drupal::entityTypeManager()
      ->getStorage('actstream_item')
      ->load($id);

    $this->assertSame('Updated title', $loaded->label());
    $this->assertFalse($loaded->isPublished());
  }

  /**
   * Tests deleting an actstream_item entity.
   */
  public function testDelete(): void {
    $entity = ActivityStreamItem::create([
      'title' => 'To delete',
      'service' => 'feed',
      'status' => 1,
      'actstream_guid' => 'guid:delete:1',
      'actstream_link' => 'https://example.com/delete',
    ]);
    $entity->save();
    $id = $entity->id();

    $entity->delete();

    $loaded = \Drupal::entityTypeManager()
      ->getStorage('actstream_item')
      ->load($id);

    $this->assertNull($loaded);
  }

  /**
   * Tests that uuid is auto-populated on save.
   */
  public function testUuidIsSet(): void {
    $entity = ActivityStreamItem::create([
      'title' => 'UUID test',
      'service' => 'feed',
      'status' => 1,
      'actstream_guid' => 'guid:uuid:1',
      'actstream_link' => 'https://example.com/uuid',
    ]);
    $entity->save();

    $this->assertNotEmpty($entity->uuid());
    $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $entity->uuid());
  }

}
