<?php

namespace Drupal\appointment_booking\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class AppointmentService
{
    protected $entityTypeManager;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Get all appointements .
     */
    public function getAllAppointments()
    {
        $appointements = $this->entityTypeManager->getStorage('appointment')->loadMultiple();
        return $appointements;
    }

    /**
     * Get a list of appointment types (taxonomy terms).
     */
    public function getAppointmentTypes()
    {
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'appointment_types']);
        $options = [];
        foreach ($terms as $term) {
            $options[] = ['id' => $term->id(), 'name' => $term->label()];
        }
        return $options;
    }

    /**
     * Get appointment types by id.
     */
    public function getAppointmentTypeById($id)
    {
        if (empty($id)) {
            return null;
        }

        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($id);

        if (!$term) {
            return null;
        }

        return [
            'id' => $term->id(),
            'name' => $term->label(),
            'description' => $term->hasField('description') ? $term->get('description')->value : '',
        ];
    }
    /**
     * Create a new appointment
     */

    public function createAppointment(array $data)
    {
        $appointment = $this->entityTypeManager
            ->getStorage('appointment')
            ->create($data);
        $appointment->save();
        return $appointment;
    }

    /**
     * Load an appointment by ID.
     */

    public function loadAppointment($id)
    {
        return $this->entityTypeManager
            ->getStorage('appointment')
            ->load($id);

    }

    /**
     * Update an appointment
     */

    public function updateAppointment($id, array $data)
    {
        $appointment = $this->loadAppointment($id);
        if ($appointment) {
            foreach ($data as $field => $value) {
                $appointment->set($field, $value);
            }
            $appointment->save();
        }
        return $appointment;
    }

    /**
     * Delete an appointment
     */
    public function delete($id)
    {
        $appointment = $this->loadAppointment($id);
        if ($appointment) {
            $appointment->delete();
        }
    }


    /**
     * Find an appointment by phone number.
     *
     * @param string $phone
     *   The phone number to search for.
     *
     * @return \Drupal\Core\Entity\EntityInterface|null
     *   The appointment entity if found, otherwise null.
     */
    public function findAppointmentByPhone($phone)
    {
        if (empty($phone)) {
            return null;
        }

        // Load appointments where the phone number matches
        $appointments = $this->entityTypeManager
            ->getStorage('appointment')
            ->loadByProperties(['customer_phone' => $phone]);

        if (!empty($appointments)) {
            // Return the first matching appointment
            return reset($appointments);
        }

        return null;
    }
}