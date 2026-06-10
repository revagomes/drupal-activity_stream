<?php

namespace Drupal\activitystream\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * Per-user activity stream accounts configuration form.
 *
 * Sub-modules add their fields via hook_form_activitystream_accounts_form_alter().
 * The $form['#user'] property is set here so those implementations can access it.
 */
class ActivityStreamAccountsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'activitystream_accounts_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL): array {
    // Store user on form so hook_form_alter implementations can access it
    // via $form['#user'] — matching the D7 API contract.
    $form['#user'] = $user;

    $form['#attached']['library'][] = 'activitystream/activitystream';

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t('The changes have been saved.'));
  }

}
