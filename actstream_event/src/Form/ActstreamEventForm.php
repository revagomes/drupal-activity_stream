<?php

namespace Drupal\actstream_event\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for adding and editing Activity Stream Event entities.
 */
class ActstreamEventForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\actstream_event\Entity\ActstreamEventInterface $event */
    $event = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $event->label(),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $event->id(),
      '#machine_name' => [
        'exists' => '\Drupal\actstream_event\Entity\ActstreamEvent::load',
        'source' => ['label'],
      ],
      '#disabled' => !$event->isNew(),
    ];

    $form['hashtag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hashtag'),
      '#field_prefix' => '#',
      '#default_value' => $event->getHashtag(),
      '#required' => TRUE,
      '#description' => $this->t('Hashtag without the # symbol (e.g. drupalcon2026).'),
      '#maxlength' => 255,
    ];

    $form['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active — include this event in cron aggregation'),
      '#default_value' => $event->isActive(),
    ];

    $service_options = [];
    foreach (actstream_services_load() as $service_id => $info) {
      $service_options[$service_id] = $info['name'];
    }

    $form['services'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Services to aggregate'),
      '#options' => $service_options,
      '#default_value' => $event->getServices(),
      '#description' => $this->t('Select which services will be searched for posts matching the hashtag.'),
    ];

    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start date'),
      '#default_value' => $event->getStartDate(),
      '#description' => $this->t('Optional. Aggregation begins on this date.'),
    ];

    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End date'),
      '#default_value' => $event->getEndDate(),
      '#description' => $this->t('Optional. Aggregation stops after this date.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\actstream_event\Entity\ActstreamEventInterface $event */
    $event = $this->entity;

    $services = array_values(array_filter($form_state->getValue('services')));
    $event->set('services', $services);

    $status = $event->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Event %label created.', ['%label' => $event->label()]));
    }
    else {
      $this->messenger()->addMessage($this->t('Event %label updated.', ['%label' => $event->label()]));
    }

    $form_state->setRedirectUrl($event->toUrl('collection'));
    return $status;
  }

}
