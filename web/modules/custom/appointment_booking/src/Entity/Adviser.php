<?php

namespace Drupal\appointment_booking\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Adviser entity.
 *
 * @ContentEntityType(
 *   id = "adviser",
 *   label = @Translation("Adviser"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\appointment_booking\AdviserListBuilder",
 *     "form" = {
 *       "default" = "Drupal\appointment_booking\Form\AdviserForm",
 *       "add" = "Drupal\appointment_booking\Form\AdviserForm",
 *       "edit" = "Drupal\appointment_booking\Form\AdviserForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "adviser",
 *   admin_permission = "administer adviser",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/adviser/{adviser}",
 *     "add-form" = "/admin/structure/adviser/add",
 *     "edit-form" = "/admin/structure/adviser/{adviser}/edit",
 *     "delete-form" = "/admin/structure/adviser/{adviser}/delete",
 *     "collection" = "/admin/structure/advisers",
 *   },
 * )
 */
class Adviser extends ContentEntityBase
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

    // Agency reference field.
    $fields['agency'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Agency'))
      ->setSetting('target_type', 'agency')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Working hours field using office_hours.
    $fields['working_hours'] = BaseFieldDefinition::create('office_hours')
      ->setLabel(t('Working Hours'))
      ->setDescription(t('The working hours of the adviser.'))
      ->setCardinality(7) // Number of days in a week.
      ->setSettings([
        'time_format' => 'H:i', // 24-hour format.
        'comment' => TRUE, // Allow comments for each day.
        'valhrs' => FALSE, // Disable validation for hours.
        'cardinality_per_day' => 2, // Number of time slots per day.
      ])
      ->setDisplayOptions('form', [
        'type' => 'office_hours_default',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'office_hours',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Specializations field.
    $fields['specializations'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Specializations'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}