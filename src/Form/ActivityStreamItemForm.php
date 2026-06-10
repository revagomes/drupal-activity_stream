<?php

namespace Drupal\activitystream\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the activitystream_item entity edit form.
 */
class ActivityStreamItemForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);
    $this->messenger()->addStatus($this->t('@title has been saved.', [
      '@title' => $entity->label(),
    ]));
    $form_state->setRedirectUrl($entity->toUrl());
    return $status;
  }

}
