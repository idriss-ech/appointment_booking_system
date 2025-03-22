<?php

namespace Drupal\appointment_booking\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Agency entity.
 *
 * @ContentEntityType(
 *   id = "agency",
 *   label = @Translation("Agency"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\appointment_booking\AgencyListBuilder",
 *     "form" = {
 *       "default" = "Drupal\appointment_booking\Form\AgencyForm",
 *       "add" = "Drupal\appointment_booking\Form\AgencyForm",
 *       "edit" = "Drupal\appointment_booking\Form\AgencyForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agency",
 *   admin_permission = "administer agency",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/agency/{agency}",
 *     "add-form" = "/admin/structure/agency/add",
 *     "edit-form" = "/admin/structure/agency/{agency}/edit",
 *     "delete-form" = "/admin/structure/agency/{agency}/delete",
 *     "collection" = "/admin/structure/agencies",
 *   },
 * )
 */
class Agency extends ContentEntityBase
{

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
  {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Name field.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Address field.
    $fields['address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Address'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Contact information field.
    $fields['contact'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Contact Information'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Operating hours field using office_hours.
    $fields['operating_hours'] = BaseFieldDefinition::create('office_hours')
      ->setLabel(t('Operating Hours'))
      ->setDescription(t('The operating hours of the agency.'))
      ->setCardinality(7) // Number of days in a week.
      ->setSettings([
        'time_format' => 'H:i', // 24-hour format.
        'comment' => TRUE, // Allow comments for each day.
        'valhrs' => FALSE, // Disable validation for hours.
        'cardinality_per_day' => 2, // Number of time slots per day.
      ])
      ->setDisplayOptions('form', [
        'type' => 'office_hours_default',
        'weight' => 4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'office_hours',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}