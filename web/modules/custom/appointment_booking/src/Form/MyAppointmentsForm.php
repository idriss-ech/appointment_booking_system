<?php

namespace Drupal\appointment_booking\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\appointment_booking\Service\AppointmentService;
use Drupal\views\Plugin\views\field\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to search appointments by phone number.
 */
class MyAppointmentsForm extends FormBase {

  /**
   * The appointment service.
   *
   * @var \Drupal\appointment_booking\Service\AppointmentService
   */
  protected $appointmentService;

  /**
   * Constructs a new MyAppointmentsForm.
   */
  public function __construct(AppointmentService $appointment_service) {
    $this->appointmentService = $appointment_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('appointment_booking.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'my_appointments_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'appointment_booking/global';

    $form['search'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['search-container']],
    ];

    $form['search']['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Enter your phone number'),
        'class' => ['phone-input'],
      ],
      '#default_value' => $form_state->getValue('phone', ''),
    ];

    $form['search']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['actions-container']],
    ];

    $form['search']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    ];

    $form['search']['actions']['reset'] = [
      '#type' => 'button',
      '#value' => $this->t('Reset'),
      '#attributes' => ['class' => ['reset-button']],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::resetForm',
        'wrapper' => 'my-appointments-form',
      ],
     
    ];

    // Display results if we have them
if ($form_state->get('submitted')) {
  $phone = $form_state->getValue('phone');
  $appointments = $this->appointmentService->findAppointmentsByPhone($phone);
  $form['results'] = [
    '#type' => 'container',
    '#attributes' => ['id' => 'appointment-results'],
  ];

  if (!empty($appointments)) {
    foreach ($appointments as $appointment) {
      $appointment_id = $appointment->id();
      
      // Get the raw string date value exactly as stored
      $date_string = $appointment->get('date')->value;
      
      // Get other field values exactly as defined in baseFieldDefinitions
      $title = $appointment->get('title')->value;
      $adviser = $appointment->get('adviser')->entity ? $appointment->get('adviser')->entity->label() : '';
      $agency = $appointment->get('agency')->entity ? $appointment->get('agency')->entity->label() : '';
      $appointment_type = $appointment->get('title')->value; // Using title as type per your template
      $form['results']['appointment_' . $appointment_id] = [
        '#theme' => 'appointment_item',
        '#date_string' => $date_string, // Raw string value
        '#title' => $title,
        '#adviser' => $adviser,
        '#agency' => $agency,
        '#appointment_type' => $appointment_type,
        '#edit_url' => \Drupal\Core\Url::fromRoute('entity.appointment.edit_form', ['appointment' => $appointment_id]),
        '#delete_url' => \Drupal\Core\Url::fromRoute('entity.appointment.delete_form', ['appointment' => $appointment_id]),
      ];
    }
  } else {
    $form['results']['no_results'] = [
      '#markup' => '<div class="no-results">' . $this->t('No appointments found for this phone number.') . '</div>',
    ];
  }
}

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $phone = $form_state->getValue('phone');
    if (empty($phone) || !preg_match('/^[0-9]{10,15}$/', $phone)) {
      $form_state->setErrorByName('phone', $this->t('Please enter a valid phone number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('submitted', TRUE);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback to reset the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
   
   
   // Recréer le formulaire depuis zéro
   $form = $this->buildForm($form, $form_state);
   
   // Effacer les résultats
   unset($form['results']);
   
   
   return $form;
  }
}