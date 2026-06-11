<?php

namespace Drupal\activity_stream\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for Activity Stream item entities.
 */
interface ActivityStreamItemInterface extends ContentEntityInterface {

  /**
   * Gets the service identifier (e.g. 'feed', 'twitter').
   */
  public function getService(): string;

  /**
   * Gets the external link URL.
   */
  public function getLink(): string;

  /**
   * Gets the GUID.
   */
  public function getGuid(): string;

  /**
   * Gets the published status.
   */
  public function isPublished(): bool;

}
