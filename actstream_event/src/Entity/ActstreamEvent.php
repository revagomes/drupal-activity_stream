<?php

namespace Drupal\actstream_event\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\actstream_event\ActstreamEventListBuilder;
use Drupal\actstream_event\Form\ActstreamEventForm;
use Drupal\actstream_event\Form\ActstreamEventDeleteForm;

/**
 * Defines the Activity Stream Event config entity.
 */
#[ConfigEntityType(
  id: 'actstream_event',
  label: new TranslatableMarkup('Activity Stream Event'),
  handlers: [
    'list_builder' => ActstreamEventListBuilder::class,
    'form' => [
      'add' => ActstreamEventForm::class,
      'edit' => ActstreamEventForm::class,
      'delete' => ActstreamEventDeleteForm::class,
    ],
  ],
  config_prefix: 'actstream_event',
  admin_permission: 'administer actstream events',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  links: [
    'collection' => '/admin/config/actstream/events',
    'add-form' => '/admin/config/actstream/events/add',
    'edit-form' => '/admin/config/actstream/events/{actstream_event}/edit',
    'delete-form' => '/admin/config/actstream/events/{actstream_event}/delete',
  ],
  config_export: [
    'id',
    'label',
    'hashtag',
    'active',
    'services',
    'start_date',
    'end_date',
  ],
)]
class ActstreamEvent extends ConfigEntityBase implements ActstreamEventInterface {

  /**
   * The event machine name.
   */
  protected string $id = '';

  /**
   * The event label.
   */
  protected string $label = '';

  /**
   * The hashtag to search (without #).
   */
  protected string $hashtag = '';

  /**
   * Whether the event is active.
   */
  protected bool $active = FALSE;

  /**
   * Service IDs to aggregate for this event.
   *
   * @var string[]
   */
  protected array $services = [];

  /**
   * Optional start date (YYYY-MM-DD).
   */
  protected string $start_date = '';

  /**
   * Optional end date (YYYY-MM-DD).
   */
  protected string $end_date = '';

  /**
   * {@inheritdoc}
   */
  public function getHashtag(): string {
    return $this->hashtag;
  }

  /**
   * {@inheritdoc}
   */
  public function getServices(): array {
    return $this->services;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return $this->active;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate(): string {
    return $this->start_date;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate(): string {
    return $this->end_date;
  }

}
