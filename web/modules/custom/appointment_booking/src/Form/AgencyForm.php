<?php

namespace Drupal\appointment_booking\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for adding and editing Agency entities.
 */
class AgencyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $agency = $this->entity;

    // Save the entity.
    $status = $agency->save();

    // Show a message based on the operation.
    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The agency %name has been created.', [
        '%name' => $agency->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The agency %name has been updated.', [
        '%name' => $agency->label(),
      ]));
    }

    // Redirect to the agency list page.
    $form_state->setRedirect('entity.agency.collection');
  }
}