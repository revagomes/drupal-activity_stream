<?php

namespace Drupal\activity_stream\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a site-wide Activity Stream block.
 */
#[Block(
  id: 'activity_stream_site_wide_block',
  admin_label: new TranslatableMarkup('Activity Stream (Site-wide)'),
  category: new TranslatableMarkup('Activity Stream'),
)]
class ActivityStreamBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs a new ActivityStreamBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $items = activity_stream_items_load(NULL, 10);
    $services = activity_stream_services_load();

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

    $build['more'] = [
      '#type' => 'link',
      '#title' => $this->t('See more'),
      '#url' => Url::fromRoute('activity_stream.default_page'),
      '#attributes' => ['class' => ['activity-stream-more-link']],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

}
