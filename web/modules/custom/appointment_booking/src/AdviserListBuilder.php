<?php

namespace Drupal\appointment_booking;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\appointment_booking\Service\OperatingHoursFormatter;

/**
 * Provides a listing of Adviser entities.
 */
class AdviserListBuilder extends EntityListBuilder {

  /**
   * The operating hours formatter service.
   *
   * @var \Drupal\appointment_booking\Service\OperatingHoursFormatter
   */
  protected $operatingHoursFormatter;

  /**
   * Constructs a new AdviserListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\appointment_booking\Service\OperatingHoursFormatter $operating_hours_formatter
   *   The operating hours formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, OperatingHoursFormatter $operating_hours_formatter) {
    parent::__construct($entity_type, $storage);
    $this->operatingHoursFormatter = $operating_hours_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('appointment_booking.operating_hours_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Name');
    $header['agency'] = $this->t('Agency');
    $header['working_hours'] = $this->t('Working Hours');
    $header['specializations'] = $this->t('Specializations');
    $header['operations'] = $this->t('Operations');
    return $header + parent::buildHeader();
  }


/**
 * {@inheritdoc}
 */
public function buildRow(EntityInterface $entity): array {
  /** @var \Drupal\appointment_booking\Entity\Adviser $entity */
  $row['name'] = $entity->label();
  $row['agency'] = $entity->get('agency')->entity->label();
  
  // Get the working hours and format them using the OperatingHoursFormatter service.
  $working_hours = $entity->get('working_hours')->getValue();
  $row['working_hours'] = $this->operatingHoursFormatter->formatOperatingHours($working_hours);

  // Get and sanitize the specializations field to ensure no HTML is rendered.
  $specializations = $entity->get('specializations')->value;
  $row['specializations'] = $specializations; // Sanitize using Html::escape()

  return $row + parent::buildRow($entity);
}


  /**
   * {@inheritdoc}
   */
  public function render() {
    // Add the "Add Adviser" button.
    $build['add_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Adviser'),
      '#url' => Url::fromRoute('entity.adviser.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    // Render the table.
    $build['table'] = parent::render();

    return $build;
  }
}
