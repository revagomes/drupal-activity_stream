<?php

namespace Drupal\actstream_event\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the actstream_event module.
 */
class ActstreamEventHooks {

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    actstream_event_cron();
  }

}
