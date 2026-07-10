<?php

namespace Drupal\actstream_wall\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the Activity Stream social wall.
 */
class WallController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Renders the social wall page.
   */
  public function page(): array {
    $config = $this->config('actstream_wall.settings');
    $count = $config->get('items_per_page') ?: 20;
    $items = $this->loadWallItems($count);

    $view_builder = $this->entityTypeManager()
      ->getViewBuilder('actstream_item');
    $rendered = [];
    $last_timestamp = 0;
    foreach ($items as $item) {
      $rendered[] = $view_builder->view($item);
      $ts = (int) $item->get('created')->value;
      if ($ts > $last_timestamp) {
        $last_timestamp = $ts;
      }
    }

    return [
      '#theme' => 'actstream_wall',
      '#items' => $rendered,
      '#attached' => [
        'library' => ['actstream_wall/actstream_wall'],
        'drupalSettings' => [
          'actstream_wall' => [
            'refresh_url' => '/actstream/wall/refresh',
            'refresh_interval' => $config->get('refresh_interval') ?: 30000,
            'last_timestamp' => $last_timestamp,
          ],
        ],
      ],
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Returns JSON with items created after $since.
   */
  public function refresh(Request $request): JsonResponse {
    $since = (int) $request->query->get('since', 0);

    $ids = $this->entityTypeManager()
      ->getStorage('actstream_item')
      ->getQuery()
      ->condition('status', 1)
      ->condition('actstream_event_id', '', '!=')
      ->condition('created', $since, '>')
      ->sort('created', 'DESC')
      ->range(0, 50)
      ->accessCheck(FALSE)
      ->execute();

    $items = $ids
      ? $this->entityTypeManager()
        ->getStorage('actstream_item')
        ->loadMultiple($ids)
      : [];

    $view_builder = $this->entityTypeManager()->getViewBuilder('actstream_item');
    $result = [];
    $last_ts = $since;

    foreach ($items as $item) {
      $ts = (int) $item->get('created')->value;
      if ($ts > $last_ts) {
        $last_ts = $ts;
      }
      $html = (string) $this->renderer->renderPlain($view_builder->view($item));
      $result[] = ['id' => $item->id(), 'created' => $ts, 'html' => $html];
    }

    return new JsonResponse(
      ['items' => $result, 'last_timestamp' => $last_ts]
    );
  }

  /**
   * Loads approved event wall items, newest first.
   *
   * @return \Drupal\actstream\Entity\ActivityStreamItem[]
   */
  protected function loadWallItems(int $count): array {
    $ids = $this->entityTypeManager()
      ->getStorage('actstream_item')
      ->getQuery()
      ->condition('status', 1)
      ->condition('actstream_event_id', '', '!=')
      ->sort('created', 'DESC')
      ->range(0, $count)
      ->accessCheck(FALSE)
      ->execute();

    return $ids
      ? $this->entityTypeManager()
        ->getStorage('actstream_item')
        ->loadMultiple($ids)
      : [];
  }

}
