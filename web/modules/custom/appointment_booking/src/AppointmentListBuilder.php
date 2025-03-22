<?php
namespace Drupal\appointment_booking;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a list controller for the Appointment entity.
 */
class AppointmentListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new AppointmentListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Title');
    $header['adviser'] = $this->t('Adviser');
    $header['date'] = $this->t('Date');
    $header['agency'] = $this->t('Agency');
    $header['appointment_type'] = $this->t('Appointment Type');
    $header['status'] = $this->t('Status');
    $header['operations'] = $this->t('Operations');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\appointment_booking\Entity\Appointment $entity */
    $row['title'] = $entity->label();
    $row['adviser'] = $entity->get('adviser')->entity->label();
    $row['date'] =  $entity->get('date')->value;
    $row['agency'] = $entity->get('agency')->entity->label();
    $row['appointment_type'] = $entity->label();
    $row['status'] = $entity->get('status')->value;

   
   

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Add the "Add Appointment" button.
    $build['add_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Appointment'),
      '#url' => Url::fromRoute('entity.appointment.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    // Render the table with filters.
    $build['filters'] = $this->buildFilters();
    $build['table'] = parent::render();

    return $build;
  }

  /**
   * Build filters for Adviser, Day of Appointment, Appointment Type, and Agency.
   */
  public function buildFilters() {
    $filters = [];

    // Filter by Adviser (Autocomplete)
    $filters['adviser'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Adviser'),
      '#target_type' => 'adviser',
      '#tags' => TRUE,
      '#autocomplete_route_name' => 'entity.adviser.autocomplete', // Fix autocomplete route
    ];

    // Filter by Day of Appointment (date)
    $filters['date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Date of Appointment'),
    ];

    // Filter by Appointment Type (Taxonomy)
    $filters['appointment_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Appointment Type'),
      '#options' => $this->getAppointmentTypes(),
    ];

    // Filter by Agency (Select)
    $filters['agency'] = [
      '#type' => 'select',
      '#title' => $this->t('Agency'),
      '#options' => $this->getAgencies(),
    ];

    // Add Apply and Reset buttons.
    $filters['actions'] = [
      '#type' => 'actions',
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
    ];
    $filters['actions']['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#button_type' => 'primary',
      '#submit' => ['::applyFilters'],
    ];
    $filters['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#button_type' => 'secondary',
      '#submit' => ['::resetFilters'],
    ];

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['appointment-filters', 'clearfix']],
      '#children' => [
        'filters' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['appointment-filters-row']],
          '#children' => $filters,
        ],
      ],
    ];
  }

  /**
   * Handle the Apply Filters action.
   */
  public function applyFilters(array $form, FormStateInterface $form_state) {
    // Logic to apply filters
    drupal_set_message($this->t('Filters have been applied.'));
  }

  /**
   * Handle the Reset Filters action.
   */
  public function resetFilters(array $form, FormStateInterface $form_state) {
    // Logic to reset filters
    drupal_set_message($this->t('Filters have been reset.'));
  }

  /**
   * Get appointment types from the taxonomy vocabulary 'appointment_types'.
   *
   * @return array
   *   The options array of appointment types.
   */


public function getAppointmentTypes() {
    $options = [];
    $vocabulary = Vocabulary::load('appointment_types'); // Load the taxonomy vocabulary

    if ($vocabulary) {
        // Load terms using the loadTree() method, which gets terms by vocabulary ID
        $terms = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadTree($vocabulary->id()); // Get terms in the vocabulary

        foreach ($terms as $term) {
            $options[$term->tid] = $term->name; // Use term ID (tid) and name
        }
    }

    return $options;
}


  /**
   * Get agencies for the agency filter.
   *
   * @return array
   *   The options array of agencies.
   */
  public function getAgencies() {
    $options = [];
    $agencies = \Drupal::entityTypeManager()->getStorage('agency')->loadMultiple();
    foreach ($agencies as $agency) {
      $options[$agency->id()] = $agency->label();
    }
    return $options;
  }
}
