<?php

namespace Drupal\actstream_wall\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the actstream_wall module.
 */
class ActstreamWallHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'actstream_wall' => [
        'variables' => ['items' => []],
        'template' => 'actstream-wall',
      ],
    ];
  }

}
