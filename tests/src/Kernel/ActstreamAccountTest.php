<?php

namespace Drupal\Tests\actstream\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests actstream_account_save(), actstream_account_load(), actstream_account_delete().
 *
 * @group actstream
 */
class ActstreamAccountTest extends KernelTestBase {

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
   * Tests saving and loading account data.
   */
  public function testSaveAndLoad(): void {
    $user = User::create(['name' => 'tester', 'status' => 1]);
    $user->save();
    $uid = (int) $user->id();

    $data = ['username' => 'flickruser123'];
    actstream_account_save('flickr', $data, $uid);

    $loaded = actstream_account_load('flickr', $uid);

    $this->assertIsArray($loaded);
    $this->assertSame('flickruser123', $loaded['username']);
  }

  /**
   * Tests that loading a non-existent account returns NULL.
   */
  public function testLoadNonExistent(): void {
    $result = actstream_account_load('nonexistent', 9999);
    $this->assertNull($result);
  }

  /**
   * Tests overwriting existing account data on save.
   */
  public function testOverwrite(): void {
    $user = User::create(['name' => 'overwriter', 'status' => 1]);
    $user->save();
    $uid = (int) $user->id();

    actstream_account_save('lastfm', ['username' => 'old'], $uid);
    actstream_account_save('lastfm', ['username' => 'new'], $uid);

    $loaded = actstream_account_load('lastfm', $uid);
    $this->assertSame('new', $loaded['username']);
  }

  /**
   * Tests deleting account data.
   */
  public function testDelete(): void {
    $user = User::create(['name' => 'deleter', 'status' => 1]);
    $user->save();
    $uid = (int) $user->id();

    actstream_account_save('feed', ['url' => 'https://example.com/feed'], $uid);
    $this->assertNotNull(actstream_account_load('feed', $uid));

    actstream_account_delete('feed', $uid);
    $this->assertNull(actstream_account_load('feed', $uid));
  }

  /**
   * Tests that account data for different services is stored separately.
   */
  public function testServicesAreIsolated(): void {
    $user = User::create(['name' => 'isolated', 'status' => 1]);
    $user->save();
    $uid = (int) $user->id();

    actstream_account_save('flickr', ['username' => 'flickr_user'], $uid);
    actstream_account_save('lastfm', ['username' => 'lastfm_user'], $uid);

    $flickr = actstream_account_load('flickr', $uid);
    $lastfm = actstream_account_load('lastfm', $uid);

    $this->assertSame('flickr_user', $flickr['username']);
    $this->assertSame('lastfm_user', $lastfm['username']);
  }

  /**
   * Tests that account data for different users is stored separately.
   */
  public function testUsersAreIsolated(): void {
    $user1 = User::create(['name' => 'user1', 'status' => 1]);
    $user1->save();
    $user2 = User::create(['name' => 'user2', 'status' => 1]);
    $user2->save();

    actstream_account_save('feed', ['url' => 'https://user1.example.com/feed'], (int) $user1->id());
    actstream_account_save('feed', ['url' => 'https://user2.example.com/feed'], (int) $user2->id());

    $data1 = actstream_account_load('feed', (int) $user1->id());
    $data2 = actstream_account_load('feed', (int) $user2->id());

    $this->assertSame('https://user1.example.com/feed', $data1['url']);
    $this->assertSame('https://user2.example.com/feed', $data2['url']);
  }

}
