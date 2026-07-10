<?php

namespace Drupal\actstream\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Activity Stream.
 */
class ActstreamCommands extends DrushCommands {

  /**
   * Constructs a new ActstreamCommands instance.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    private readonly Connection $database,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * Fetch activity stream items for one user or all users.
   */
  #[CLI\Command(name: 'actstream:fetch', aliases: ['asf'])]
  #[CLI\Argument(name: 'uid', description: 'User ID to fetch for. Omit to fetch for all users.')]
  #[CLI\Usage(name: 'drush actstream:fetch', description: 'Fetch items for all registered users.')]
  #[CLI\Usage(name: 'drush actstream:fetch 42', description: 'Fetch items for user 42 only.')]
  public function fetch(?int $uid = NULL): void {
    $query = $this->database
      ->select('actstream_account', 'a')
      ->fields('a', ['uid', 'service', 'data']);
    if ($uid !== NULL) {
      $query->condition('uid', $uid);
    }
    $results = $query->execute()->fetchAll();

    if (empty($results)) {
      $this->logger()->warning(dt('No actstream accounts found.'));
      return;
    }

    foreach ($results as $result) {
      $items = actstream_items_fetch((int) $result->uid, $result->service, unserialize($result->data));
      actstream_items_save((int) $result->uid, $result->service, $items);
      $this->logger()->success(dt('Fetched @count item(s) for uid=@uid service=@service.', [
        '@count' => count($items),
        '@uid' => $result->uid,
        '@service' => $result->service,
      ]));
    }
  }

  /**
   * Delete activity stream items for one user or all users.
   */
  #[CLI\Command(name: 'actstream:clear', aliases: ['asc'])]
  #[CLI\Argument(name: 'uid', description: 'User ID whose items to delete. Omit to delete all items.')]
  #[CLI\Usage(name: 'drush actstream:clear', description: 'Delete all activity stream items.')]
  #[CLI\Usage(name: 'drush actstream:clear 42', description: 'Delete items for user 42 only.')]
  public function clear(?int $uid = NULL): void {
    $storage = $this->entityTypeManager->getStorage('actstream_item');
    $query = $storage->getQuery()->accessCheck(FALSE);
    if ($uid !== NULL) {
      $query->condition('uid', $uid);
    }
    $ids = $query->execute();

    if (empty($ids)) {
      $this->logger()->notice(dt('No activity stream items to delete.'));
      return;
    }

    $storage->delete($storage->loadMultiple($ids));
    $this->logger()->success(dt('Deleted @count activity stream item(s).', ['@count' => count($ids)]));
  }

}
