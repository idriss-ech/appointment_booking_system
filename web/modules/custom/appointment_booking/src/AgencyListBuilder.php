<?php

namespace Drupal\appointment_booking;

use Drupal\appointment_booking\Service\OperatingHoursFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the Agency entity.
 */
class AgencyListBuilder extends EntityListBuilder
{
  /**
   * The operating hours formatter service.
   *
   * @var \Drupal\appointment_booking\Service\OperatingHoursFormatter
   */
  protected $operatingHoursFormatter;

  /**
   * Constructs a new AgencyListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\appointment_booking\Service\OperatingHoursFormatter $operating_hours_formatter
   *   The operating hours formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, OperatingHoursFormatter $operating_hours_formatter)
  {
    parent::__construct($entity_type, $storage);
    $this->operatingHoursFormatter = $operating_hours_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type)
  {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('appointment_booking.operating_hours_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader()
  {
    $header['name'] = $this->t('Name');
    $header['address'] = $this->t('Address');
    $header['contact'] = $this->t('Contact Information');
    $header['operating_hours'] = $this->t('Operating Hours');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity)
  {
    /** @var \Drupal\appointment_booking\Entity\Agency $entity */
    $row['name'] = $entity->label();
    $row['address'] = $entity->get('address')->value;
    $row['contact'] = $entity->get('contact')->value;

    // Format the operating hours.
    $operating_hours = $entity->get('operating_hours')->getValue();
    
    // Use the injected service to format the operating hours.
    $row['operating_hours'] = $this->operatingHoursFormatter->formatOperatingHours($operating_hours);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Add the "Add Agency" button.
    $build['add_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Agency'),
      '#url' => Url::fromRoute('entity.agency.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    // Render the table.
    $build['table'] = parent::render();

    return $build;
  }
}
