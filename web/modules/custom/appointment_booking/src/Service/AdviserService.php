<?php

namespace Drupal\appointment_booking\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class AdviserService
{
    protected $entityTypeManager;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Get all advisers .
     */
    public function getAllAdvisers()
    {
        $advisers = $this->entityTypeManager->getStorage('adviser')->loadMultiple();
        return $advisers;
    }
    /**
     * Get a list of advisers.
     */
    public function getAdvisers()
    {
        $advisers = $this->entityTypeManager->getStorage('adviser')->loadMultiple();
        $options = [];
        foreach ($advisers as $adviser) {
            $options[] = ['id' => $adviser->id(), 'name' => $adviser->label()];

        }
        return $options;
    }

    /**
     * Create a new adviser
     */
    public function createAdviser(array $data)
    {
        $adviser = $this->entityTypeManager
            ->getStorage('adviser')
            ->create($data);
        $adviser->save();
        return $adviser;
    }

    /** 
     * Load an adviser by ID.
     */
    public function loadAdviser($agency_id)
    {
        return $this->entityTypeManager
            ->getStorage('adviser')
            ->load($agency_id);
    }

    /**
     * update an adviser
     */
    public function updateAdviser($agency_id, array $data)
    {
        $adviser = $this->loadAdviser($agency_id);
        if ($adviser) {
            foreach ($adviser as $field => $value) {
                $adviser->set($field, $value);
            }
            $adviser->save();
        }
        return $adviser;
    }

    /**
     * delete an adviser
     */
    public function deleteAdviser($agency_id)
    {
        $adviser = $this->loadAdviser($agency_id);
        if ($adviser) {
            $adviser->delete();
        }
    }

    /**
     * Query advisers by agency ID.
     */
    public function queryAdvisersByAgency($agency_id)
{
    // Query advisers by agency ID
    $query = $this->entityTypeManager
        ->getStorage('adviser')
        ->getQuery()
        ->condition('agency', $agency_id)
        ->accessCheck(TRUE);

    // Execute the query and get adviser IDs
    $adviser_ids = $query->execute();

    // Load the advisers and extract ID and name
    $adviser_data = [];
    foreach ($adviser_ids as $id) {
        $adviser = $this->loadAdviser($id);
        $adviser_data[] = [
            'id' => $adviser->id(),          // Get the ID
            'name' => $adviser->label(),   // Get the name
        ];
    }

    return $adviser_data;
}


}