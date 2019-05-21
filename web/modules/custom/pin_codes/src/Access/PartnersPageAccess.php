<?php

namespace Drupal\pin_codes\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

class PartnersPageAccess {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('pin_codes');
    $pin_code = $tempstore->get('pin_code');

    if (isset($pin_code)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
