<?php

namespace Drupal\appointment_booking\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\appointment_booking\Service\AgencyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding and editing Appointment entities.
 */
class AppointmentForm extends ContentEntityForm {

  /**
   * The agency service.
   *
   * @var \Drupal\appointment_booking\Service\AgencyService
   */
  protected $agencyService;

  /**
   * Constructs a new AppointmentForm.
   *
   * @param \Drupal\appointment_booking\Service\AgencyService $agencyService
   *   The agency service.
   */
  public function __construct(AgencyService $agencyService) {
    $this->agencyService = $agencyService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('appointment_booking.agency_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $appointment = $this->entity;

    // Save the entity.
    $status = $appointment->save();

    // Show a message based on the operation.
    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The appointment %title has been created.', [
        '%title' => $appointment->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The appointment %title has been updated.', [
        '%title' => $appointment->label(),
      ]));
    }

    // Redirect to the appointment list page.
    $form_state->setRedirect('entity.appointment.collection');
  }
}