<?php

namespace Drupal\appointment_booking;

use Drupal\appointment_booking\Service\AdviserService;
use Drupal\appointment_booking\Service\AgencyService;
use Drupal\appointment_booking\Service\AppointmentService;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Provides a list controller for the Appointment entity.
 */
class AppointmentListBuilder extends EntityListBuilder
{
    protected $dateFormatter;
    protected $appointmentService;
    protected $agencyService;
    protected $adviserService;
    protected $messenger;
    protected $currentUser;
    protected $languageManager;

    // Filter properties
    protected $adviserFilter;
    protected $dateFilter;
    protected $typeFilter;
    protected $agencyFilter;

    /**
     * Constructs a new AppointmentListBuilder object.
     */
    public function __construct(
        EntityTypeInterface $entity_type,
        EntityStorageInterface $storage,
        DateFormatterInterface $date_formatter,
        AppointmentService $appointmentService,
        AgencyService $agencyService,
        AdviserService $adviserService,
        MessengerInterface $messenger,
        AccountInterface $current_user,
        LanguageManagerInterface $language_manager
    ) {
        parent::__construct($entity_type, $storage);
        $this->dateFormatter = $date_formatter;
        $this->appointmentService = $appointmentService;
        $this->agencyService = $agencyService;
        $this->adviserService = $adviserService;
        $this->messenger = $messenger;
        $this->currentUser = $current_user;
        $this->languageManager = $language_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type)
    {
        return new static(
            $entity_type,
            $container->get('entity_type.manager')->getStorage($entity_type->id()),
            $container->get('date.formatter'),
            $container->get('appointment_booking.service'),
            $container->get('appointment_booking.agency_service'),
            $container->get('appointment_booking.adviser_service'),
            $container->get('messenger'),
            $container->get('current_user'),
            $container->get('language_manager')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header = [
            'title' => $this->t('Title'),
            'adviser' => $this->t('Adviser'),
            'date' => $this->t('Date'),
            'agency' => $this->t('Agency'),
            'appointment_type' => $this->t('Appointment Type'),
            'status' => $this->t('Status'),
            'operations' => $this->t('Operations'),
        ];
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        $row['title'] = $entity->label();
        $row['adviser'] = $entity->get('adviser')->entity->label();
        // $row['date'] = $this->dateFormatter->format($entity->get('date')->value, 'custom', 'l, j F Y : H:i - H:i');
        $row['date'] = $entity->get('date')->value;
        $row['agency'] = $entity->get('agency')->entity->label();
        $row['appointment_type'] = $entity->label();
        $row['status'] = $entity->get('status')->value;

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $build['add_button'] = [
            '#type' => 'link',
            '#title' => $this->t('Add Appointment'),
            '#url' => Url::fromRoute('entity.appointment.add_form'),
            '#attributes' => ['class' => ['button', 'button--primary']],
        ];

        $build['filters'] = $this->buildFilters();
        $build['table'] = parent::render();
        $build['#attached']['library'][] = 'appointment_booking/global';

        return $build;
    }

    /**
     * Build the filters form.
     */
    public function buildFilters()
    {
        $request = \Drupal::request();
        $this->adviserFilter = $request->query->get('adviser');
        $this->dateFilter = $request->query->get('date');
        $this->typeFilter = $request->query->get('appointment_type');
        $this->agencyFilter = $request->query->get('agency');

        $filters['wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['filters-flex-wrapper']],
        ];

        // Filters container
        $filters['wrapper']['filters'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['filters-container']],
        ];

        // Adviser filter with 'All' option
        $filters['wrapper']['filters']['adviser'] = [
            '#type' => 'entity_autocomplete',
            '#title' => $this->t('Adviser'),
            '#target_type' => 'adviser',
            '#tags' => TRUE,
            '#default_value' => NULL,
            '#selection_handler' => 'default',
            '#selection_settings' => [
                'target_bundles' => NULL,
                'match_operator' => 'CONTAINS',
            ],
            '#attributes' => ['class' => ['filter-item']],
        ];

        // Date filter
        $filters['wrapper']['filters']['date'] = [
            '#type' => 'date',
            '#title' => $this->t('Date'),
            '#default_value' => $this->dateFilter,
            '#attributes' => ['class' => ['filter-item']],
        ];

        $appointmentTypes = $this->appointmentService->getAppointmentTypes();
        $appointmentTypeOptions = [];
        foreach ($appointmentTypes as $type) {
            $appointmentTypeOptions[$type['id']] = $type['name'];
        }

        $agencies = $this->agencyService->getAgencies();
        $agenciesOptions = [];
        foreach ($agencies as $agency) {
            $agenciesOptions[$agency['id']] = $agency['name'];
        }

        // Appointment Type filter with 'All' option
        $filters['wrapper']['filters']['appointment_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Appointment Type'),
            '#options' => $appointmentTypeOptions,
            '#empty_option' => $this->t('- All -'),
            '#default_value' => $this->typeFilter,
            '#attributes' => ['class' => ['filter-item']],
        ];

        // Agency filter with 'All' option
        $filters['wrapper']['filters']['agency'] = [
            '#type' => 'select',
            '#title' => $this->t('Agency'),
            '#options' => $agenciesOptions,
            '#empty_option' => $this->t('- All -'),
            '#default_value' => $this->agencyFilter,
            '#attributes' => ['class' => ['filter-item']],
        ];

        // Actions container
        $filters['wrapper']['actions'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['actions-container']],
        ];

        // Apply button
        $filters['wrapper']['actions']['apply'] = [
            '#type' => 'submit',
            '#value' => $this->t('Apply Filters'),
            '#submit' => ['::applyFilters'],
            '#attributes' => ['class' => ['button', 'button--primary']],
        ];

        // Reset button
        $filters['wrapper']['actions']['reset'] = [
            '#type' => 'link',
            '#title' => $this->t('Reset'),
            '#url' => Url::fromRoute('<current>'),
            '#attributes' => ['class' => ['button', 'button--secondary']],
        ];

        // Export button
        $filters['wrapper']['actions']['export'] = [
            '#type' => 'link',
            '#title' => $this->t('Export CSV'),
            '#url' => Url::fromRoute('appointment_booking.export_csv', [], [
                'query' => [
                    'adviser' => $this->adviserFilter,
                    'date' => $this->dateFilter,
                    'appointment_type' => $this->typeFilter,
                    'agency' => $this->agencyFilter,
                ]
            ]),
            '#attributes' => ['class' => ['button', 'button--export']],
        ];

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['appointment-filters']],
            '#children' => $filters,
            '#attached' => [
                'library' => ['appointment_booking/filters'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIds()
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->sort($this->entityType->getKey('id'));

        // Apply filters
        if ($this->adviserFilter) {
            $query->condition('adviser', (array) $this->adviserFilter, 'IN');
        }
        if ($this->dateFilter) {
            $start = strtotime($this->dateFilter);
            $end = strtotime($this->dateFilter . ' +1 day');
            $query->condition('date', $start, '>=');
            $query->condition('date', $end, '<');
        }
        if ($this->typeFilter) {
            $query->condition('appointment_type', $this->typeFilter);
        }
        if ($this->agencyFilter) {
            $query->condition('agency', $this->agencyFilter);
        }

        return $query->execute();
    }

    /**
     * Apply filters handler.
     */
    public function applyFilters(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();
        $query = [
            'adviser' => $values['adviser'] ?? NULL,
            'date' => $values['date'] ?? NULL,
            'appointment_type' => $values['appointment_type'] ?? NULL,
            'agency' => $values['agency'] ?? NULL,
        ];

        $form_state->setRedirect('<current>', [], ['query' => array_filter($query)]);
    }
}
