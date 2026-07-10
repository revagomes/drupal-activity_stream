<?php

namespace Drupal\actstream_wall\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Activity Stream Social Wall block.
 */
#[Block(
  id: 'actstream_wall_block',
  admin_label: new TranslatableMarkup('Activity Stream Social Wall'),
  category: new TranslatableMarkup('Activity Stream'),
)]
class WallBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new WallBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->configFactory->get('actstream_wall.settings');
    $count = $config->get('items_per_page') ?: 20;

    $ids = $this->entityTypeManager
      ->getStorage('actstream_item')
      ->getQuery()
      ->condition('status', 1)
      ->condition('actstream_event_id', '', '!=')
      ->sort('created', 'DESC')
      ->range(0, $count)
      ->accessCheck(FALSE)
      ->execute();

    $items = $ids
      ? $this->entityTypeManager
        ->getStorage('actstream_item')
        ->loadMultiple($ids)
      : [];

    $view_builder = $this->entityTypeManager->getViewBuilder('actstream_item');
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
