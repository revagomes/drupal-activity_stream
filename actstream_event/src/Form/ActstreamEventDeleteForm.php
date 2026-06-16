<?php

namespace Drupal\actstream_event\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Form for deleting an Activity Stream Event entity.
 */
class ActstreamEventDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.actstream_event.collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl(): Url {
    return new Url('entity.actstream_event.collection');
  }

}
