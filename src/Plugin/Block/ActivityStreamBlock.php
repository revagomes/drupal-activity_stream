<?php

namespace Drupal\actstream\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a site-wide Activity Stream block.
 */
#[Block(
  id: 'actstream_site_wide_block',
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
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $items = actstream_items_load(NULL, 10);
    $services = actstream_services_load();

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

    $build['more'] = [
      '#type' => 'link',
      '#title' => $this->t('See more'),
      '#url' => Url::fromRoute('actstream.default_page'),
      '#attributes' => ['class' => ['actstream-more-link']],
    ];

    $build['#cache']['tags'] = ['actstream_item_list'];

    return $build;
  }

}
