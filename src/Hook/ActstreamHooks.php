<?php

namespace Drupal\actstream\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the actstream module.
 */
class ActstreamHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'actstream_item' => [
        'variables' => [
          'actstream_item' => NULL,
          'service' => NULL,
          'statement' => [],
          'service_icon' => [],
          'permalink_icon' => [],
          'external_icon' => [],
          'time_ago' => [],
        ],
        'template' => 'actstream-item',
      ],
      'actstream_items_wrapper' => [
        'variables' => ['items' => []],
        'template' => 'actstream-items-wrapper',
      ],
    ];
  }

  /**
   * Implements hook_permission().
   */
  #[Hook('permission')]
  public function permission(): array {
    $permissions = [];
    foreach (actstream_services_load() as $type => $info) {
      $type_name = $info['name'];
      $permissions["edit any $type actstream"] = [
        'title' => $this->t('%type_name: Edit any Activity Stream', ['%type_name' => $type_name]),
      ];
      $permissions["view any $type actstream"] = [
        'title' => $this->t('%type_name: View any Activity Stream', ['%type_name' => $type_name]),
      ];
    }
    return $permissions;
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $results = \Drupal::database()
      ->select('actstream_account', 'a')
      ->fields('a', ['uid', 'service', 'data'])
      ->execute()
      ->fetchAll();
    foreach ($results as $result) {
      $items = actstream_items_fetch((int) $result->uid, $result->service, unserialize($result->data));
      actstream_items_save((int) $result->uid, $result->service, $items);
    }
  }

}
