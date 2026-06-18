<?php

namespace Drupal\actstream_mod_queue\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Bulk moderation queue for pending Activity Stream wall items.
 */
class ModerationQueueForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'actstream_mod_queue';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state
  ): array {
    $ids = \Drupal::entityQuery('actstream_item')
      ->condition('actstream_event_id', '', '!=')
      ->condition('status', 0)
      ->sort('created', 'DESC')
      ->range(0, 200)
      ->accessCheck(FALSE)
      ->execute();

    $items = $ids
      ? \Drupal::entityTypeManager()
        ->getStorage('actstream_item')
        ->loadMultiple($ids)
      : [];

    $options = [];
    $date_formatter = \Drupal::service('date.formatter');
    foreach ($items as $item) {
      $options[$item->id()] = [
        'event' => $item->get('actstream_event_id')->value,
        'service' => $item->get('service')->value,
        'title' => $item->label(),
        'created' => $date_formatter->format(
          $item->get('created')->value,
          'short'
        ),
      ];
    }

    $form['items'] = [
      '#type' => 'tableselect',
      '#header' => [
        'event' => $this->t('Event'),
        'service' => $this->t('Service'),
        'title' => $this->t('Title'),
        'created' => $this->t('Created'),
      ],
      '#options' => $options,
      '#empty' => $this->t('No items pending moderation.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['approve'] = [
      '#type' => 'submit',
      '#value' => $this->t('Approve selected'),
      '#name' => 'approve',
    ];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected'),
      '#name' => 'delete',
      '#button_type' => 'danger',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    $selected = array_keys(
      array_filter($form_state->getValue('items', []))
    );
    if (empty($selected)) {
      $this->messenger()->addWarning($this->t('No items selected.'));
      return;
    }

    $storage = \Drupal::entityTypeManager()
      ->getStorage('actstream_item');
    $action = $form_state->getTriggeringElement()['#name'] ?? '';

    if ($action === 'approve') {
      foreach ($storage->loadMultiple($selected) as $item) {
        $item->set('status', 1)->save();
      }
      $this->messenger()->addMessage(
        $this->t(
          'Approved @count item(s).',
          ['@count' => count($selected)]
        )
      );
    }
    elseif ($action === 'delete') {
      $storage->delete($storage->loadMultiple($selected));
      $this->messenger()->addMessage(
        $this->t(
          'Deleted @count item(s).',
          ['@count' => count($selected)]
        )
      );
    }
  }

}
