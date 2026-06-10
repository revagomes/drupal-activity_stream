<?php

namespace Drupal\activitystream\Controller;

use Drupal\activitystream\Entity\ActivityStreamItem;
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
    $items = activitystream_items_load(NULL);
    $services = activitystream_services_load();
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
    $items = activitystream_items_load((int) $user->id());
    $services = activitystream_services_load();
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
   * Returns a render array for a single activitystream_item entity.
   *
   * @param \Drupal\activitystream\Entity\ActivityStreamItem $activitystream_item
   *   The activity stream item entity.
   *
   * @return array
   *   A render array.
   */
  public function view(ActivityStreamItem $activitystream_item): array {
    return $this->entityTypeManager()
      ->getViewBuilder('activitystream_item')
      ->view($activitystream_item);
  }

  /**
   * Builds a render array for a list of activitystream_item entities.
   *
   * @param \Drupal\activitystream\Entity\ActivityStreamItem[] $items
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
      '#prefix' => '<div id="activitystream-items">',
      '#suffix' => '</div>',
    ];
    $build['items']['#attached']['library'][] = 'activitystream/activitystream';

    foreach ($items as $item) {
      $service_id = $item->get('service')->value;
      $build['items'][$item->id()] = [
        '#theme' => 'activitystream_item__' . $service_id,
        '#activitystream_item' => $item,
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
