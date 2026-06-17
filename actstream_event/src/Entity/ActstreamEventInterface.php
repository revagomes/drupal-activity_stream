<?php

namespace Drupal\actstream_event\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for the Activity Stream Event config entity.
 */
interface ActstreamEventInterface extends ConfigEntityInterface {

  /**
   * Returns the hashtag without the # symbol.
   */
  public function getHashtag(): string;

  /**
   * Returns the list of service IDs to aggregate for this event.
   *
   * @return string[]
   */
  public function getServices(): array;

  /**
   * Returns TRUE if this event is currently active.
   */
  public function isActive(): bool;

  /**
   * Returns the optional start date (YYYY-MM-DD), or empty string.
   */
  public function getStartDate(): string;

  /**
   * Returns the optional end date (YYYY-MM-DD), or empty string.
   */
  public function getEndDate(): string;

}
