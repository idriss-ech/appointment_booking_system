<?php

namespace Drupal\appointment_booking\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class AgencyService
{
    protected $entityTypeManager;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

     /**
     * Get all agencies .
     */
    public function getAllAgencies()
    {
        $agencies = $this->entityTypeManager->getStorage('agency')->loadMultiple();
        return $agencies;
    }

    /**
     * Get a list of agencies.
     */
    public function getAgencies()
    {
        $agencies = $this->entityTypeManager->getStorage('agency')->loadMultiple();
        $options = [];
        foreach ($agencies as $agency) {
              $options[] = ['id' => $agency->id(), 'name' => $agency->label()];
        }
        return $options;
    }

    /**
     * Create a new agency
     */
    public function createAgency($data)
    {
        $agency = $this->entityTypeManager
            ->getStorage('agency')
            ->create($data);
        $agency->save();
        return $agency;
    }

    /**
     * Load and agency by ID.
     */
    public function loadAgency($id)
    {
        return $this->entityTypeManager
            ->getStorage('agency')
            ->load($id);
    }

    /** 
     * Update an agency
     */
    public function updateAgency($id, $data)
    {
        $agency = $this->loadAgency($id);
        if ($agency) {
            foreach ($agency as $field => $value) {
                $agency->set($field, $value);
            }
            $agency->save();
        }
        return $agency;
    }

    /**
     * Delete an Agency
     */
    public function deleteAgency($id)
    {
        $agency = $this->loadAgency($id);
        if ($agency) {
            $agency->delete();
        }
    }

}