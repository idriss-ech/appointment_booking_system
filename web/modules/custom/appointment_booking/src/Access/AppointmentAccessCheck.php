<?php 
namespace Drupal\appointment_booking\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

class AppointmentAccessCheck implements AccessInterface {

  /**
   * Checks access for authenticated users.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAuthenticated())
      ->addCacheContexts(['user.roles:authenticated']);
  }
}