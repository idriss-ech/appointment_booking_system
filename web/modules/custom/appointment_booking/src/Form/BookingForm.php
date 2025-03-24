<?php

namespace Drupal\appointment_booking\Form;

use DateTime;
use DateTimeZone;
use Drupal\appointment_booking\Service\AppointmentService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\appointment_booking\Service\AgencyService;
use Drupal\appointment_booking\Service\AdviserService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a multi-step booking form.
 */
class BookingForm extends FormBase {

  protected $tempStore;
  protected $agencyService;
  protected $adviserService;
  protected $appointmentService;

  public function __construct(PrivateTempStoreFactory $temp_store_factory, AgencyService $agency_service, AdviserService $adviser_service, AppointmentService $appointmentService) {
    $this->tempStore = $temp_store_factory->get('appointment_booking');
    $this->agencyService = $agency_service;
    $this->adviserService = $adviser_service;
    $this->appointmentService = $appointmentService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('appointment_booking.agency_service'),
      $container->get('appointment_booking.adviser_service'),
      $container->get('appointment_booking.service')
    );
  }

  public function getFormId() {
    return 'appointment_booking_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $this->tempStore->get('step') ?? 1;

    $form['#prefix'] = '<div id="booking-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['step'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'stepField'],
      '#value' => $step,
    ];

    // Retrieve stored values or default to empty.
    $agency = $this->tempStore->get('agency') ?? '';
    $appointment_type = $this->tempStore->get('appointment_type') ?? '';
    $adviser = $this->tempStore->get('adviser') ?? '';
    $form['agency'] = [
      '#type' => 'hidden',
      '#default_value' => $agency,
      '#attributes' => [
        'id' => ['agency'],  
      ],
    ];

    $form['appointment_type'] = [
      '#type' => 'hidden',
      '#default_value' => $appointment_type,
      '#attributes' => [
        'id' => ['appointment_type'], 
      ],
    ];

    $form['adviser'] = [
      '#type' => 'hidden',
      '#default_value' => $adviser,
      '#attributes' => [
        'id' => ['adviser'],  
      ],
    ];

    // Step 1: Agency Selection
    if ($step === 1) {
      $agencies = $this->agencyService->getAgencies();
      $form['agency_selection'] = [
        '#theme' => 'agency_selection',
        '#agencies' => $agencies,
        '#selected_agency' => $agency,
      ];
    }

    // Step 2: Appointment Type Selection
    if ($step === 2) {
      $appointment_types = $this->appointmentService->getAppointmentTypes();
      $form['appointment_type_selection'] = [
        '#theme' => 'appointment_type_selection',
        '#terms' => $appointment_types,
        '#selected_type' => $appointment_type,
      ];
    }

    // Step 3: Adviser Selection
    if ($step === 3) {
      $advisers = $this->adviserService->queryAdvisersByAgency($agency);
      $form['adviser_selection'] = [
        '#theme' => 'adviser_selection',
        '#advisers' => $advisers,
        '#selected_adviser' => $adviser,
      ];
    }

    // Step 4: Date and Time Selection
    if ($step === 4) {
      $form['#attached']['library'][] = 'appointment_booking/fullcalendar';

      $appointments = $this->appointmentService->getAllAppointments();
      $existingAppointments = $this->prepareAppointmentData($appointments);

      $form['#attached']['drupalSettings']['existingAppointments'] = $existingAppointments;

      $selectedStartDate = $this->tempStore->get('selected_start_date');
      $selectedEndDate = $this->tempStore->get('selected_end_date');
      $userSlotEvent = $this->prepareUserSlotEvent($selectedStartDate, $selectedEndDate);

      $form['#attached']['drupalSettings']['userSlotEvent'] = $userSlotEvent;

      $adviser_data = $this->adviserService->loadAdviser($adviser);
      $working_hours = $adviser_data->get('working_hours')->getValue();
      list($business_hours, $unavailable_slots) = $this->prepareWorkingHours($working_hours);

      $form['#attached']['drupalSettings']['adviserWorkingHours'] = $business_hours;
      $form['#attached']['drupalSettings']['adviserUnavailableSlots'] = $unavailable_slots;

      $form['calendar'] = [
        '#markup' => '<h3 class="step-title">Choose the day and time of your appointment.</h3><div id="fullcalendar"></div>',
      ];

      $form['user_timezone'] = [
        '#type' => 'hidden',
        '#default_value' => $this->tempStore->get('user_timezone') ?? '',
      ];

      $form['selected_start_date'] = [
        '#type' => 'hidden',
        '#attributes' => ['id' => 'selected-start-date'],
        '#default_value' => $this->tempStore->get('selected_start_date') ?? '',
      ];

      $form['selected_end_date'] = [
        '#type' => 'hidden',
        '#attributes' => ['id' => 'selected-end-date'],
        '#default_value' => $this->tempStore->get('selected_end_date') ?? '',
      ];
    }

    // Step 5: Personal Information
    if ($step === 5) {
      $form['appointment_and_personal_information'] = [
        '#prefix' => '<h3 class="step-title">Appointment Information</h3><div class="appointment-personal-info-container">',
        '#suffix' => '</div>',
      ];

      $form['appointment_and_personal_information']['appointment_information'] = [
        '#theme' => 'appointment_information',
        '#date' => $this->tempStore->get('date'),
        '#start_time' => $this->tempStore->get('start_time'),
        '#end_time' => $this->tempStore->get('end_time'),
      ];

      $form['appointment_and_personal_information']['personal_information'] = [
        '#prefix' => '<div class="personal-info-container">',
        '#suffix' => '</div>',
      ];

      $form['appointment_and_personal_information']['personal_information']['first_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('First Name'),
        '#required' => TRUE,
        '#default_value' => $this->tempStore->get('first_name') ?? '',
      ];

      $form['appointment_and_personal_information']['personal_information']['last_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Last Name'),
        '#required' => TRUE,
        '#default_value' => $this->tempStore->get('last_name') ?? '',
      ];

      $form['appointment_and_personal_information']['personal_information']['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email Address'),
        '#required' => TRUE,
        '#default_value' => $this->tempStore->get('email') ?? '',
      ];

      $form['appointment_and_personal_information']['personal_information']['phone'] = [
        '#type' => 'tel',
        '#title' => $this->t('Phone Number'),
        '#required' => TRUE,
        '#default_value' => $this->tempStore->get('phone') ?? '',
      ];

      $form['appointment_and_personal_information']['personal_information']['terms'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('I accept the terms and conditions'),
        '#required' => TRUE,
        '#default_value' => $this->tempStore->get('terms_accepted') ?? 0,
      ];

      $form['appointment_and_personal_information']['personal_information']['terms_link'] = [
        '#type' => 'markup',
        '#markup' => '<a href="/terms-and-conditions" target="_blank">Read Terms and Conditions</a>',
      ];
    }

    // Step 6: Confirmation
    if ($step === 6) {
      $confirmation_data = $this->prepareConfirmationData();
      $form['confirmation'] = $this->buildConfirmationSection($confirmation_data);
    }

    // Step 7: Success Message
    if ($step === 7) {
      $form['success_message'] = [
        '#theme' => 'appointment_success_message',
      ];

      $form['button_container'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['button-container']],
      ];

      $form['button_container']['modify'] = [
        '#type' => 'submit',
        '#value' => $this->t('Modify Appointment'),
        '#attributes' => ['class' => ['modify-button']],
        '#ajax' => [
          'callback' => '::ajaxUpdateStep',
          'wrapper' => 'booking-form-wrapper',
          'event' => 'click',
        ],
      ];

      $form['button_container']['home'] = [
        '#type' => 'link',
        '#title' => $this->t('Return to Homepage'),
        '#url' => \Drupal\Core\Url::fromRoute('<front>'),
        '#attributes' => ['class' => ['home-button']],
      ];
    }

    // Step 8: Phone Verification for Modification
    if ($step === 8) {
      $form['phone_verification'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['phone-verification-container']],
      ];

      $form['phone_verification']['#prefix'] = '<h3>' . $this->t('Verify Your Phone Number to Modify Appointment') . '</h3>';

      $form['phone_verification']['phone'] = [
        '#type' => 'tel',
        '#required' => TRUE,
        '#attributes' => [
          'placeholder' => $this->t('0689543212'),
          'class' => ['phone-input'],
        ],
      ];

      $form['phone_verification']['actions'] = [
        '#type' => 'actions',
      ];

      $form['phone_verification']['actions']['verify'] = [
        '#type' => 'submit',
        '#value' => $this->t('Verify'),
        '#submit' => ['::verifyPhoneNumberForModification'],
        '#ajax' => [
          'callback' => '::ajaxUpdateStep',
          'wrapper' => 'booking-form-wrapper',
          'event' => 'click',
        ],
        '#attributes' => ['class' => ['verify-button']],
      ];
    }

    // Step 9: Modify User Information
    if ($step === 9) {
      $appointment = $this->tempStore->get('appointment');

      if (!$appointment) {
        $this->messenger()->addError($this->t('No appointment found.'));
        $this->tempStore->set('step', 1);
        $form_state->setRebuild();
        return;
      }

      $form['modify_user_info'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Modify Your Information'),
      ];

      $form['modify_user_info']['customer_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Your Name'),
        '#default_value' => $appointment->get('customer_name')->value,
        '#required' => TRUE,
      ];

      $form['modify_user_info']['customer_email'] = [
        '#type' => 'email',
        '#title' => $this->t('Your Email'),
        '#default_value' => $appointment->get('customer_email')->value,
        '#required' => TRUE,
      ];

      $form['modify_user_info']['customer_phone'] = [
        '#type' => 'tel',
        '#title' => $this->t('Your Phone Number'),
        '#default_value' => $appointment->get('customer_phone')->value,
        '#required' => TRUE,
      ];

      $form['modify_user_info']['actions'] = [
        '#type' => 'actions',
      ];

      $form['modify_user_info']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update Information'),
        '#submit' => ['::updateUserInformation'],
        '#ajax' => [
          'callback' => '::ajaxUpdateStep',
          'wrapper' => 'booking-form-wrapper',
          'event' => 'click',
        ],
      ];
    }

    // Navigation buttons
    $form['actions'] = [
      '#type' => 'actions',
    ];

    if ($step > 1 && $step < 6) {
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => ['::goBack'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::ajaxUpdateStep',
          'wrapper' => 'booking-form-wrapper',
          'event' => 'click',
        ],
      ];
    }

    if ($step === 6) {
      $form['actions']['confirm'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm Appointment'),
        '#submit' => ['::submitForm'],
        '#ajax' => [
          'callback' => '::ajaxUpdateStep',
          'wrapper' => 'booking-form-wrapper',
          'event' => 'click',
        ],
      ];
    }

    if ($step < 6) {
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#submit' => ['::submitForm'],
        '#ajax' => [
          'callback' => '::ajaxUpdateStep',
          'wrapper' => 'booking-form-wrapper',
          'event' => 'click',
        ],
      ];
    }

    $form['#attached']['library'][] = 'appointment_booking/global';

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    $step = $form_state->getValue('step');

    switch ($step) {
      case 1:
        if (empty($form_state->getValue('agency'))) {
          $form_state->setErrorByName('agency', $this->t('Please select an agency.'));
        }
        break;

      case 2:
        if (empty($form_state->getValue('appointment_type'))) {
          $form_state->setErrorByName('appointment_type', $this->t('Please select an appointment type.'));
        }
        break;

      case 3:
        if (empty($form_state->getValue('adviser'))) {
          $form_state->setErrorByName('adviser', $this->t('Please select an adviser.'));
        }
        break;

      case 4:
        if (empty($form_state->getValue('selected_start_date')) || empty($form_state->getValue('selected_end_date'))) {
          $form_state->setErrorByName('selected_start_date', $this->t('Please select a valid date and time.'));
        }
        break;

      case 5:
        if (empty($form_state->getValue('first_name'))) {
          $form_state->setErrorByName('first_name', $this->t('Please enter your first name.'));
        }
        if (empty($form_state->getValue('last_name'))) {
          $form_state->setErrorByName('last_name', $this->t('Please enter your last name.'));
        }
        if (empty($form_state->getValue('email')) || !\Drupal::service('email.validator')->isValid($form_state->getValue('email'))) {
          $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
        }
        if (empty($form_state->getValue('phone'))) {
          $form_state->setErrorByName('phone', $this->t('Please enter your phone number.'));
        }
        if (empty($form_state->getValue('terms'))) {
          $form_state->setErrorByName('terms', $this->t('You must accept the terms and conditions.'));
        }
        break;
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $step = $form_state->getValue('step');
    $this->tempStore->set('step', $step + 1);
    $triggering_element = $form_state->getTriggeringElement();

    if ($triggering_element['#name'] === 'edit_user_profile') {
      $this->tempStore->set('step', 5);
      $form_state->setRebuild();
      return;
    }

    if ($triggering_element['#name'] === 'edit_appointment_date') {
      $this->tempStore->set('step', 4);
      $form_state->setRebuild();
      return;
    }

    $this->saveStepData($form_state, $step);
    $form_state->setRebuild();
  }

  protected function saveStepData(FormStateInterface $form_state, $step) {

    switch ($step) {
      case 1:
        $this->tempStore->set('agency', $form_state->getValue('agency'));
        break;

      case 2:
        $this->tempStore->set('appointment_type', $form_state->getValue('appointment_type'));
        break;

      case 3:
        $this->tempStore->set('adviser', $form_state->getValue('adviser'));
        break;

      case 4:
         // Get the user's time zone from tempStore
         $userTimeZone = $form_state->getValue('user_timezone'); // e.g., 'Africa/Casablanca'

         // Create DateTime objects for start and end dates (assuming they are in UTC)
         $startDate = new DateTime($form_state->getValue('selected_start_date'), new DateTimeZone('UTC'));
         $endDate = new DateTime($form_state->getValue('selected_end_date'), new DateTimeZone('UTC'));

         // Convert dates to the user's time zone
         $startDate->setTimezone(new DateTimeZone($userTimeZone));
         $endDate->setTimezone(new DateTimeZone($userTimeZone));

         // Format the dates as strings
         $formattedStartDate = $startDate->format('l, d F Y'); // e.g., "Saturday, 12 April 2025"
         $formattedStartTime = $startDate->format('H:i'); // e.g., "00:00"
         $formattedEndTime = $endDate->format('H:i'); // e.g., "01:00"

         $this->tempStore->set('selected_start_date', $form_state->getValue('selected_start_date'));
         $this->tempStore->set('selected_end_date', $form_state->getValue('selected_end_date'));
         $this->tempStore->set('user_timezone', $form_state->getValue('user_timezone'));

         $this->tempStore->set('date', $formattedStartDate);
         $this->tempStore->set('start_time', $formattedStartTime);
         $this->tempStore->set('end_time', $formattedEndTime);
        break;

      case 5:
        $this->tempStore->set('first_name', $form_state->getValue('first_name'));
        $this->tempStore->set('last_name', $form_state->getValue('last_name'));
        $this->tempStore->set('email', $form_state->getValue('email'));
        $this->tempStore->set('phone', $form_state->getValue('phone'));
        $this->tempStore->set('terms_accepted', $form_state->getValue('terms'));
        break;

      case 6:
        $this->saveAppointment();
        break;
    }
  }

  protected function saveAppointment() {
    $agency_id = $this->tempStore->get('agency');
    $appointment_type = $this->tempStore->get('appointment_type');
    $adviser_id = $this->tempStore->get('adviser');
    $date = $this->tempStore->get('date');
    $start_time = $this->tempStore->get('start_time');
    $end_time = $this->tempStore->get('end_time');
    $name = $this->tempStore->get('first_name') . " " . $this->tempStore->get('last_name');
    $email = $this->tempStore->get('email');
    $phone = $this->tempStore->get('phone');

    $agency = $this->agencyService->loadAgency($agency_id);
    $adviser = $this->adviserService->loadAdviser($adviser_id);
    $title = $this->appointmentService->getAppointmentTypeById($appointment_type);

    if (!$agency || !$adviser) {
      throw new \Exception('Invalid agency or adviser.');
    }

    $values = [
      'agency' => $agency,
      'title' => $title['name'],
      'adviser' => $adviser,
      'date' => $date . " : " . $start_time . ' - ' . $end_time,
      'customer_name' => $name,
      'customer_email' => $email,
      'customer_phone' => $phone,
      'status' => 'pending',
    ];

    $this->appointmentService->createAppointment($values);

    // Clear tempStore
    $this->tempStore->delete('agency');
    $this->tempStore->delete('appointment_type');
    $this->tempStore->delete('adviser');
    $this->tempStore->delete('date');
    $this->tempStore->delete('selected_start_date');
    $this->tempStore->delete('selected_end_date');
    $this->tempStore->delete('start_time');
    $this->tempStore->delete('end_time');
    $this->tempStore->delete('first_name');
    $this->tempStore->delete('last_name');
    $this->tempStore->delete('email');
    $this->tempStore->delete('phone');
  }

  public function ajaxUpdateStep(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
     

    $response->addCommand(new ReplaceCommand('#booking-form-wrapper', $form));
    return $form;
  }

  public function goBack(array &$form, FormStateInterface $form_state) {

    $step = $this->tempStore->get('step') ?? 1;
    $this->tempStore->set('step', $step - 1);
    $form_state->setRebuild();
  }

  public function verifyPhoneNumberForModification(array &$form, FormStateInterface $form_state) {

    $phone = $form_state->getValue('phone');
    $appointment = $this->appointmentService->findAppointmentByPhone($phone);

    if ($appointment) {
      $this->tempStore->set('appointment', $appointment);
      $this->tempStore->set('step', 9);
    } else {
      $this->messenger()->addError($this->t('No appointment found with the provided phone number.'));
    }

    $form_state->setRebuild();
  }

  public function updateUserInformation(array &$form, FormStateInterface $form_state) {

    $appointment = $this->tempStore->get('appointment');

    if (!$appointment) {
      $this->messenger()->addError($this->t('No appointment found.'));
      $this->tempStore->set('step', 1);
      $form_state->setRebuild();
      return;
    }

    $appointment->set('customer_name', $form_state->getValue('customer_name'));
    $appointment->set('customer_email', $form_state->getValue('customer_email'));
    $appointment->set('customer_phone', $form_state->getValue('customer_phone'));
    $appointment->save();

    $this->tempStore->delete('appointment');
    $this->tempStore->set('step', 1);
    
    $form_state->setRebuild();
    $this->messenger()->addMessage($this->t('Your information has been updated.'));
  }

  protected function prepareAppointmentData($appointments) {
    $existingAppointments = [];
    foreach ($appointments as $appointment) {
      $appointmentDate = $appointment->get('date')->value;
      if (preg_match('/^(.*) : (\d{2}:\d{2}) - (\d{2}:\d{2})$/', $appointmentDate, $matches)) {
        $datePart = trim($matches[1]);
        $startTime = trim($matches[2]);
        $endTime = trim($matches[3]);

        $datePart = preg_replace('/^[^,]+, /', '', $datePart);
        $startDateTime = DateTime::createFromFormat('d F Y', $datePart, new DateTimeZone('UTC'));

        if ($startDateTime === false) {
          \Drupal::logger('appointment_booking')->error('Failed to parse date: @date', ['@date' => $datePart]);
          continue;
        }

        $startDateTime->setTime(...explode(':', $startTime));
        $endDateTime = (clone $startDateTime)->setTime(...explode(':', $endTime));

        $existingAppointments[] = [
          'title' => 'Appointment reserved',
          'start' => $startDateTime->format('Y-m-d\TH:i:s\Z'),
          'end' => $endDateTime->format('Y-m-d\TH:i:s\Z'),
          'color' => '#0000FF',
          'textColor' => '#FFFFFF',
        ];
      } else {
        \Drupal::logger('appointment_booking')->error('Invalid date format: @date', ['@date' => $appointmentDate]);
      }
    }
    return $existingAppointments;
  }

  protected function prepareUserSlotEvent($selectedStartDate, $selectedEndDate) {
    if ($selectedStartDate && $selectedEndDate) {
      return [
        'title' => 'Your appointment',
        'start' => $selectedStartDate,
        'end' => $selectedEndDate,
        'color' => '#00FF00',
        'textColor' => '#000000',
      ];
    }
    return null;
  }

  protected function prepareWorkingHours($working_hours) {
    $business_hours = [];
    $unavailable_slots = [];

    foreach ($working_hours as $day) {
      $day_of_week = $day['day'];
      $default_start_time = '08:00';
      $default_end_time = '18:00';

      if (!empty($day['starthours']) && !empty($day['endhours'])) {
        $start_time = str_pad($day['starthours'], 4, '0', STR_PAD_LEFT);
        $end_time = str_pad($day['endhours'], 4, '0', STR_PAD_LEFT);

        $start_time = substr($start_time, 0, 2) . ':' . substr($start_time, 2, 2);
        $end_time = substr($end_time, 0, 2) . ':' . substr($end_time, 2, 2);
      } else {
        $start_time = $default_start_time;
        $end_time = $default_end_time;
      }

      $business_hours[] = [
        'daysOfWeek' => [$day_of_week],
        'startTime' => $start_time,
        'endTime' => $end_time,
      ];

      if ($start_time > $default_start_time) {
        $unavailable_slots[] = [
          'daysOfWeek' => [$day_of_week],
          'startTime' => $default_start_time,
          'endTime' => $start_time,
          'title' => 'Indisponible',
          'color' => '#ffcccc',
          'textColor' => '#cc0000',
          'display' => 'background',
        ];
      }

      if ($end_time < $default_end_time) {
        $unavailable_slots[] = [
          'daysOfWeek' => [$day_of_week],
          'startTime' => $end_time,
          'endTime' => $default_end_time,
          'title' => 'Indisponible',
          'color' => '#ffcccc',
          'textColor' => '#cc0000',
          'display' => 'background',
        ];
      }
    }

    return [$business_hours, $unavailable_slots];
  }

  protected function prepareConfirmationData() {
    $agency = $this->tempStore->get('agency');
    $appointment_type = $this->tempStore->get('appointment_type');
    $adviser = $this->tempStore->get('adviser');

    $agency_data = $this->agencyService->loadAgency($agency);
    $appointment_type_data = $this->appointmentService->getAppointmentTypeById($appointment_type);
    $adviser_data = $this->adviserService->loadAdviser($adviser);

    return [
      'agency' => $agency_data->label() ?? '',
      'appointment_type' => $appointment_type_data['name'] ?? '',
      'adviser' => $adviser_data->label() ?? '',
      'date' => $this->tempStore->get('date') ?? '',
      'time' => $this->tempStore->get('start_time') . " - " . $this->tempStore->get('end_time') ?? '',
      'first_name' => $this->tempStore->get('first_name'),
      'last_name' => $this->tempStore->get('last_name') ?? '',
      'email' => $this->tempStore->get('email') ?? '',
      'phone' => $this->tempStore->get('phone') ?? '',
    ];
  }

  protected function buildConfirmationSection($confirmation_data) {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['confirmation']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Confirm your Appointment'),
        '#attributes' => ['class' => ['step-title']],
      ],
      'appointment_type' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $confirmation_data['appointment_type'] . ' appointments',
      ],
      'profile_section' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['profil']],
        'header' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['profil-header']],
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h3',
            '#value' => $this->t('User Profile'),
          ],
          'edit_button' => [
            '#type' => 'submit',
            '#value' => $this->t('Edit Profile'),
            '#name' => 'edit_user_profile',
            '#attributes' => ['class' => ['edit-button']],
          ]
        ],
        'profile_data' => [
          '#theme' => 'appointment_confirmation_profile',
          '#confirmation_data' => $confirmation_data,
        ],
      ],
      'appointment_section' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['appointment']],
        'header' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['appointment-header']],
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h3',
            '#value' => $this->t('Appointment'),
          ],
          'edit_button' => [
            '#type' => 'submit',
            '#value' => $this->t('Edit Date'),
            '#name' => 'edit_appointment_date',
            '#attributes' => ['class' => ['edit-button']],
          ]
        ],
        'appointment_data' => [
          '#theme' => 'appointment_confirmation_appointment',
          '#confirmation_data' => $confirmation_data,
        ],
      ],
    ];
  }
}