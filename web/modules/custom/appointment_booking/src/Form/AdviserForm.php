<?php

namespace Drupal\appointment_booking\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for adding and editing Adviser entities.
 */
class AdviserForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $adviser = $this->entity;

    // Save the entity.
    $status = $adviser->save();

    // Show a message based on the operation.
    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The adviser %name has been created.', [
        '%name' => $adviser->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The adviser %name has been updated.', [
        '%name' => $adviser->label(),
      ]));
    }

    // Redirect to the adviser list page.
    $form_state->setRedirect('entity.adviser.collection');
  }
}