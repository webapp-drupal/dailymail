<?php

namespace Drupal\pin_codes\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\views\Plugin\views\access\AccessPluginBase;

/**
 * Access plugin that controls the access to partners page.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "partners_page_access",
 *   title = @Translation("Partners page Access"),
 *   help = @Translation("Will be available to users with valid pin code only")
 * )
 */
class PartnersPageAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Access only to users with valid pin code only');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('pin_codes');
    $pin_code = $tempstore->get('pin_code');;

    if (isset($pin_code)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_custom_access', '\Drupal\pin_codes\Access\PartnersPageAccess::access');
  }

}
