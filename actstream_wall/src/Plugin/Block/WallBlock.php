<?php

namespace Drupal\actstream_wall\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides the Activity Stream Social Wall block.
 */
#[Block(
  id: 'actstream_wall_block',
  admin_label: new TranslatableMarkup('Activity Stream Social Wall'),
  category: new TranslatableMarkup('Activity Stream'),
)]
class WallBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = \Drupal::config('actstream_wall.settings');
    $count = $config->get('items_per_page') ?: 20;

    $ids = \Drupal::entityQuery('actstream_item')
      ->condition('status', 1)
      ->condition('actstream_event_id', '', '!=')
      ->sort('created', 'DESC')
      ->range(0, $count)
      ->accessCheck(FALSE)
      ->execute();

    $items = $ids
      ? \Drupal::entityTypeManager()
        ->getStorage('actstream_item')
        ->loadMultiple($ids)
      : [];

    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('actstream_item');
    $rendered = [];
    $last_ts = 0;
    foreach ($items as $item) {
      $rendered[] = $view_builder->view($item);
      $ts = (int) $item->get('created')->value;
      if ($ts > $last_ts) {
        $last_ts = $ts;
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
            'last_timestamp' => $last_ts,
          ],
        ],
      ],
      '#cache' => ['max-age' => 0],
    ];
  }

}
