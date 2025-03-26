<?php

namespace Drupal\appointment_booking\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

class AdviserService
{
    protected $entityTypeManager;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Get all adviser users.
     */
    public function getAllAdvisers()
    {
        $users = $this->entityTypeManager->getStorage('user')->loadByProperties([
            'roles' => 'adviser', // Assuming advisers have 'adviser' role
        ]);
        return $users;
    }

    /**
     * Get a list of advisers for select options.
     */
    public function getAdvisers()
    {
        $advisers = $this->getAllAdvisers();
        $options = [];
        foreach ($advisers as $adviser) {
            $options[] = [
                'id' => $adviser->id(), 
                'name' => $adviser->getDisplayName()
            ];
        }
        return $options;
    }

    /**
     * Get advisers by agency (assuming users have a field_agency reference field)
     */
    public function queryAdvisersByAgency($agency_id)
    {
        $query = $this->entityTypeManager->getStorage('user')->getQuery()
            ->condition('status', 1)
            // ->condition('roles', 'adviser')
            ->condition('field_agency', $agency_id)
            ->accessCheck(TRUE);

        $uids = $query->execute();
        $advisers = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);

        $options = [];
        foreach ($advisers as $adviser) {
            $options[] = [
                'id' => $adviser->id(),
                'name' => $adviser->getDisplayName()
            ];
        }

        return $options;
    }

    /**
     * Load a user (adviser) by ID.
     */
    public function loadAdviser($uid)
    {
       
        return $this->entityTypeManager
            ->getStorage('user')
            ->load($uid);
    }

    /**
     * Update a user (adviser).
     */
    public function updateAdviser($uid, array $data)
    {
        $user = $this->loadAdviser($uid);
        if ($user instanceof UserInterface) {
            foreach ($data as $field => $value) {
                if ($user->hasField($field)) {
                    $user->set($field, $value);
                }
            }
            $user->save();
            return $user;
        }
        return null;
    }

    /**
     * Delete a user (adviser).
     * Note: Be careful with deleting users as it may affect other content.
     */
    public function deleteAdviser($uid)
    {
        $user = $this->loadAdviser($uid);
        if ($user instanceof UserInterface) {
            $user->delete();
            return true;
        }
        return false;
    }

    /**
     * Create a new adviser user.
     */
    public function createAdviser(array $data)
    {
        $user = $this->entityTypeManager
            ->getStorage('user')
            ->create($data);
        
        // Set default values for adviser
        $user->enforceIsNew();
        $user->addRole('adviser'); // Assign adviser role
        $user->activate(); // Activate the account
        
        $user->save();
        return $user;
    }
}