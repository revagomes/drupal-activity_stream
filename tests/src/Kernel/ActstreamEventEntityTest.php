<?php

namespace Drupal\Tests\actstream\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the actstream_event config entity CRUD.
 *
 * @group actstream
 */
class ActstreamEventEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'actstream',
    'actstream_event',
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
    $this->installEntitySchema('actstream_item');
    $this->installSchema('actstream', ['actstream_account']);
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Tests creating, loading, and deleting an actstream_event entity.
   */
  public function testEventCrud(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('actstream_event');

    $event = $storage->create([
      'id' => 'dc2026',
      'label' => 'DrupalCon 2026',
      'hashtag' => 'drupalcon2026',
      'active' => TRUE,
      'services' => ['twitter_search', 'instagram_search'],
      'start_date' => '2026-09-01',
      'end_date' => '2026-09-05',
    ]);
    $event->save();

    $loaded = $storage->load('dc2026');
    $this->assertNotNull($loaded);
    $this->assertSame('DrupalCon 2026', $loaded->label());
    $this->assertSame('drupalcon2026', $loaded->getHashtag());
    $this->assertTrue($loaded->isActive());
    $expected_services = ['twitter_search', 'instagram_search'];
    $this->assertSame($expected_services, $loaded->getServices());

    $loaded->delete();
    $this->assertNull($storage->load('dc2026'));
  }

  /**
   * Tests inactive events are excluded from a loadByProperties active query.
   */
  public function testInactiveEventNotLoaded(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('actstream_event');

    $storage->create([
      'id' => 'inactive_event',
      'label' => 'Inactive Event',
      'hashtag' => 'old',
      'active' => FALSE,
      'services' => [],
    ])->save();

    $active = $storage->loadByProperties(['active' => TRUE]);
    $this->assertArrayNotHasKey('inactive_event', $active);
  }

}
