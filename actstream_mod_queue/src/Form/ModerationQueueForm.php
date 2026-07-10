<?php

namespace Drupal\actstream_mod_queue\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Bulk moderation queue for pending Activity Stream wall items.
 */
class ModerationQueueForm extends FormBase {

  /**
   * Constructs a new ModerationQueueForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
    );
  }

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
    $ids = $this->entityTypeManager
      ->getStorage('actstream_item')
      ->getQuery()
      ->condition('actstream_event_id', '', '!=')
      ->condition('status', 0)
      ->sort('created', 'DESC')
      ->range(0, 200)
      ->accessCheck(FALSE)
      ->execute();

    $items = $ids
      ? $this->entityTypeManager
        ->getStorage('actstream_item')
        ->loadMultiple($ids)
      : [];

    $options = [];
    foreach ($items as $item) {
      $options[$item->id()] = [
        'event' => $item->get('actstream_event_id')->value,
        'service' => $item->get('service')->value,
        'title' => $item->label(),
        'created' => $this->dateFormatter->format(
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

    $storage = $this->entityTypeManager->getStorage('actstream_item');
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
