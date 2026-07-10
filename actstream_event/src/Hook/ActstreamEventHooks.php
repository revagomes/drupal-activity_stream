<?php

namespace Drupal\actstream_event\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Hook implementations for the actstream_event module.
 */
class ActstreamEventHooks {

  /**
   * Constructs a new ActstreamEventHooks instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $storage = $this->entityTypeManager->getStorage('actstream_event');
    $events = $storage->loadByProperties(['active' => TRUE]);
    $logger = $this->loggerFactory->get('actstream_event');

    foreach ($events as $event) {
      $hashtag = $event->getHashtag();
      $event_id = $event->id();

      foreach ($event->getServices() as $service_id) {
        try {
          $items = actstream_items_fetch(1, $service_id, ['hashtag' => $hashtag]);
          $this->savePendingItems($service_id, $event_id, $items);
        }
        catch (\Exception $e) {
          $logger->error($e->getMessage());
        }
      }
    }
  }

  /**
   * Saves fetched items as pending (status=0) event items.
   *
   * Skips any item whose GUID already exists for this service + event pair.
   *
   * @param string $service
   *   The service machine name.
   * @param string $event_id
   *   The actstream_event machine name.
   * @param array $items
   *   Items returned by actstream_items_fetch().
   */
  private function savePendingItems(string $service, string $event_id, array $items): void {
    if (empty($items)) {
      return;
    }

    $item_storage = $this->entityTypeManager->getStorage('actstream_item');

    foreach ($items as $item) {
      $guid = $item['guid'] ?? ($item['link'] ?? '');
      if (empty($guid)) {
        continue;
      }

      $existing = $item_storage
        ->getQuery()
        ->condition('actstream_guid', $guid)
        ->condition('service', $service)
        ->condition('actstream_event_id', $event_id)
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();

      if (!empty($existing)) {
        continue;
      }

      $entity = $item_storage->create([
        'service' => $service,
        // uid=1 (site admin) owns event-aggregated items since they are not
        // associated with any specific user account.
        'uid' => 1,
        'status' => 0,
        'actstream_event_id' => $event_id,
      ]);
      $entity->set('title', $item['title'] ?? '');
      $entity->set('created', $item['timestamp'] ?? $this->time->getRequestTime());
      $entity->set('actstream_link', $item['link'] ?? '');
      $entity->set('actstream_guid', $guid);
      $entity->set('actstream_raw', $item['raw'] ?? '');
      $entity->set('actstream_body', $item['body'] ?? '');
      $entity->save();
    }
  }

}
