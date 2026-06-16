<?php

namespace Drupal\actstream_event;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds the list of Activity Stream Event entities.
 */
class ActstreamEventListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'label' => $this->t('Event'),
      'hashtag' => $this->t('Hashtag'),
      'active' => $this->t('Active'),
      'services' => $this->t('Services'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\actstream_event\Entity\ActstreamEventInterface $entity */
    return [
      'label' => $entity->label(),
      'hashtag' => '#' . $entity->getHashtag(),
      'active' => $entity->isActive() ? $this->t('Yes') : $this->t('No'),
      'services' => implode(', ', $entity->getServices()),
    ] + parent::buildRow($entity);
  }

}
