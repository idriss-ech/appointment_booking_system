namespace Drupal\appointment_booking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BookingForm extends FormBase {

  protected $tempStore;

  public function __construct(PrivateTempStoreFactory $tempStore) {
    $this->tempStore = $tempStore->get('appointment_booking');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  public function getFormId() {
    return 'appointment_booking_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the current step from the form state or default to step 1.
    $step = $form_state->get('step') ?? 1;

    switch ($step) {
      case 1:
        return $this->buildAgencySelectionForm($form, $form_state);
      case 2:
        return $this->buildAppointmentTypeForm($form, $form_state);
      case 3:
        return $this->buildAdviserSelectionForm($form, $form_state);
      case 4:
        return $this->buildDateTimeSelectionForm($form, $form_state);
      case 5:
        return $this->buildPersonalInformationForm($form, $form_state);
      case 6:
        return $this->buildConfirmationForm($form, $form_state);
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle form submission based on the current step.
    $step = $form_state->get('step') ?? 1;

    switch ($step) {
      case 1:
        $this->handleAgencySelection($form_state);
        break;
      case 2:
        $this->handleAppointmentTypeSelection($form_state);
        break;
      case 3:
        $this->handleAdviserSelection($form_state);
        break;
      case 4:
        $this->handleDateTimeSelection($form_state);
        break;
      case 5:
        $this->handlePersonalInformation($form_state);
        break;
      case 6:
        $this->handleConfirmation($form_state);
        break;
    }

    // Move to the next step.
    $form_state->set('step', $step + 1);
    $form_state->setRebuild();
  }

  /******************************************************************************
   * Step 1: Agency Selection
   ******************************************************************************/

  protected function buildAgencySelectionForm(array &$form, FormStateInterface $form_state) {
    $form['agency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose an Agency'),
      '#options' => $this->getAgencies(),
      '#required' => TRUE,
      '#prefix' => '<div class="agency-cards">',
      '#suffix' => '</div>',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];

    return $form;
  }

  protected function handleAgencySelection(FormStateInterface $form_state) {
    $this->tempStore->set('agency_id', $form_state->getValue('agency'));
  }

  /******************************************************************************
   * Step 2: Appointment Type Selection
   ******************************************************************************/

  protected function buildAppointmentTypeForm(array &$form, FormStateInterface $form_state) {
    $form['appointment_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose an Appointment Type'),
      '#options' => $this->getAppointmentTypes(),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#submit' => ['::previousStep'],
    ];
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];

    return $form;
  }

  protected function handleAppointmentTypeSelection(FormStateInterface $form_state) {
    $this->tempStore->set('appointment_type', $form_state->getValue('appointment_type'));
  }

  /******************************************************************************
   * Step 3: Adviser Selection
   ******************************************************************************/

  protected function buildAdviserSelectionForm(array &$form, FormStateInterface $form_state) {
    $agency_id = $this->tempStore->get('agency_id');
    $form['adviser'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose an Adviser'),
      '#options' => $this->getAdvisersByAgency($agency_id),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#submit' => ['::previousStep'],
    ];
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];

    return $form;
  }

  protected function handleAdviserSelection(FormStateInterface $form_state) {
    $this->tempStore->set('adviser_id', $form_state->getValue('adviser'));
  }

  /******************************************************************************
   * Step 4: Date and Time Selection
   ******************************************************************************/

  protected function buildDateTimeSelectionForm(array &$form, FormStateInterface $form_state) {
    $form['date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Choose a Date and Time'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#submit' => ['::previousStep'],
    ];
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];

    return $form;
  }

  protected function handleDateTimeSelection(FormStateInterface $form_state) {
    $this->tempStore->set('date', $form_state->getValue('date'));
  }

  /******************************************************************************
   * Step 5: Personal Information
   ******************************************************************************/

  protected function buildPersonalInformationForm(array &$form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
    ];

    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#submit' => ['::previousStep'],
    ];
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];

    return $form;
  }

  protected function handlePersonalInformation(FormStateInterface $form_state) {
    $this->tempStore->set('name', $form_state->getValue('name'));
    $this->tempStore->set('email', $form_state->getValue('email'));
    $this->tempStore->set('phone', $form_state->getValue('phone'));
  }

  /******************************************************************************
   * Step 6: Confirmation
   ******************************************************************************/

  protected function buildConfirmationForm(array &$form, FormStateInterface $form_state) {
    $form['confirmation'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Please confirm your appointment.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#submit' => ['::previousStep'],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
    ];

    return $form;
  }

  protected function handleConfirmation(FormStateInterface $form_state) {
    // Create the appointment entity.
    $appointment = \Drupal::entityTypeManager()
      ->getStorage('appointment')
      ->create([
        'title' => $this->tempStore->get('name') . ' - Appointment',
        'date' => $this->tempStore->get('date')->format('Y-m-d H:i:s'),
        'agency' => $this->tempStore->get('agency_id'),
        'adviser' => $this->tempStore->get('adviser_id'),
        'customer_name' => $this->tempStore->get('name'),
        'customer_email' => $this->tempStore->get('email'),
        'customer_phone' => $this->tempStore->get('phone'),
        'status' => 'pending',
      ]);
    $appointment->save();

    // Send confirmation email.
    $this->sendConfirmationEmail($appointment);

    // Clear the tempstore.
    $this->tempStore->delete('agency_id');
    $this->tempStore->delete('appointment_type');
    $this->tempStore->delete('adviser_id');
    $this->tempStore->delete('date');
    $this->tempStore->delete('name');
    $this->tempStore->delete('email');
    $this->tempStore->delete('phone');

    // Display a success message.
    $this->messenger()->addMessage($this->t('Your appointment has been booked successfully.'));
  }

  /******************************************************************************
   * Helper Methods
   ******************************************************************************/

  protected function getAgencies() {
    $agencies = \Drupal::entityTypeManager()
      ->getStorage('agency')
      ->loadMultiple();
    $options = [];
    foreach ($agencies as $agency) {
      $options[$agency->id()] = $agency->label();
    }
    return $options;
  }

  protected function getAppointmentTypes() {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'appointment_types']);
    $options = [];
    foreach ($terms as $term) {
      $options[$term->id()] = $term->label();
    }
    return $options;
  }

  protected function getAdvisersByAgency($agency_id) {
    $advisers = \Drupal::entityTypeManager()
      ->getStorage('adviser')
      ->loadByProperties(['agency' => $agency_id]);
    $options = [];
    foreach ($advisers as $adviser) {
      $options[$adviser->id()] = $adviser->label();
    }
    return $options;
  }

  protected function sendConfirmationEmail($appointment) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'appointment_booking';
    $key = 'appointment_confirmation';
    $to = $appointment->get('customer_email')->value;
    $params = [
      'subject' => $this->t('Appointment Confirmation'),
      'message' => $this->t('Your appointment has been booked successfully.'),
    ];
    $mailManager->mail($module, $key, $to, 'en', $params);
  }

  public function previousStep(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?? 1;
    if ($step > 1) {
      $form_state->set('step', $step - 1);
      $form_state->setRebuild();
    }
  }
}