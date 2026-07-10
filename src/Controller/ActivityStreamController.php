<?php

namespace Drupal\actstream\Controller;

use Drupal\actstream\Entity\ActivityStreamItem;
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
    $items = actstream_items_load(NULL);
    $services = actstream_services_load();
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
    $items = actstream_items_load((int) $user->id());
    $services = actstream_services_load();
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
    return $this->t("@name's Activity Stream", ['@name' => $user->getDisplayName()]);
  }

  /**
   * Returns a render array for a single actstream_item entity.
   *
   * @param \Drupal\actstream\Entity\ActivityStreamItem $actstream_item
   *   The activity stream item entity.
   *
   * @return array
   *   A render array.
   */
  public function view(ActivityStreamItem $actstream_item): array {
    return $this->entityTypeManager()
      ->getViewBuilder('actstream_item')
      ->view($actstream_item);
  }

  /**
   * Builds a render array for a list of actstream_item entities.
   *
   * @param \Drupal\actstream\Entity\ActivityStreamItem[] $items
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
      '#prefix' => '<div id="actstream-items">',
      '#suffix' => '</div>',
    ];
    $build['items']['#attached']['library'][] = 'actstream/actstream';

    foreach ($items as $item) {
      $service_id = $item->get('service')->value;
      $build['items'][$item->id()] = [
        '#theme' => 'actstream_item__' . $service_id,
        '#actstream_item' => $item,
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
