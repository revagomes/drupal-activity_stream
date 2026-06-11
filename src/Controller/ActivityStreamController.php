<?php

namespace Drupal\activity_stream\Controller;

use Drupal\activity_stream\Entity\ActivityStreamItem;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;

/**
 * Returns responses for Activity Stream routes.
 */
class ActivityStreamController extends ControllerBase {

  /**
   * Returns a render array for the site-wide activity stream page.
   *
   * @return array
   *   A render array.
   */
  public function defaultPage(): array {
    $items = activity_stream_items_load(NULL);
    $services = activity_stream_services_load();
    return $this->buildItemList($items, $services);
  }

  /**
   * Returns a render array for a per-user activity stream page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user whose stream to display.
   *
   * @return array
   *   A render array.
   */
  public function userPage(UserInterface $user): array {
    $items = activity_stream_items_load((int) $user->id());
    $services = activity_stream_services_load();
    return $this->buildItemList($items, $services);
  }

  /**
   * Returns the title for the user activity stream page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user whose stream is being displayed.
   *
   * @return string
   *   The page title.
   */
  public function userPageTitle(UserInterface $user): string {
    return $user->getDisplayName() . "'s Activity Stream";
  }

  /**
   * Returns a render array for a single activity_stream_item entity.
   *
   * @param \Drupal\activity_stream\Entity\ActivityStreamItem $activity_stream_item
   *   The activity stream item entity.
   *
   * @return array
   *   A render array.
   */
  public function view(ActivityStreamItem $activity_stream_item): array {
    return $this->entityTypeManager()
      ->getViewBuilder('activity_stream_item')
      ->view($activity_stream_item);
  }

  /**
   * Builds a render array for a list of activity_stream_item entities.
   *
   * @param \Drupal\activity_stream\Entity\ActivityStreamItem[] $items
   *   The items to render.
   * @param array $services
   *   The registered service definitions.
   *
   * @return array
   *   A render array.
   */
  protected function buildItemList(array $items, array $services): array {
    $build = [];

    $build['items'] = [
      '#prefix' => '<div id="activity-stream-items">',
      '#suffix' => '</div>',
    ];
    $build['items']['#attached']['library'][] = 'activity_stream/activity_stream';

    foreach ($items as $item) {
      $service_id = $item->get('service')->value;
      $build['items'][$item->id()] = [
        '#theme' => 'activity_stream_item__' . $service_id,
        '#activity_stream_item' => $item,
        '#service' => $services[$service_id] ?? [],
      ];
    }

    if (empty($items)) {
      $build['empty'] = [
        '#markup' => $this->t('No activity items have been saved for this stream yet.'),
      ];
    }

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

}
