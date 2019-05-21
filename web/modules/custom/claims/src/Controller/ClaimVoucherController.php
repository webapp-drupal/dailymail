<?php

namespace Drupal\claims\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;

/**
 * Class ClaimVoucherController.
 */
class ClaimVoucherController extends ControllerBase {

  /**
   * Drupal\user\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $userPrivateTempstore;
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Constructs a new ClaimVoucherController object.
   */
  public function __construct(PrivateTempStoreFactory $user_private_tempstore, EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->userPrivateTempstore = $user_private_tempstore;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * Display.
   *
   * @return string
   *   Return Hello string.
   */
  public function display() {
    $tempstore = $this->userPrivateTempstore->get('claims');
    $partner_nid = $tempstore->get('partner');
    $partner_node = $this->entityTypeManager->getStorage('node')->load($partner_nid);
    $node = $this->entityTypeManager->getViewBuilder('node')->view($partner_node);

    return $node;
    // $rendered = \Drupal::service('renderer')->renderRoot($node);

    // return [
    //   '#theme' => 'claim_voucher',
    //   '#node' => $node['#node'],
    // ];
  }

  /**
   * Saves the data in user tempstore and redirects to claim voucher page
   *
   * @return AjaxResponse
   *
   */
  public function link($nid) {
    // Get user private temp store
    $tempstore = $this->userPrivateTempstore->get('claims');

    // Set the partner nid to store for showing on claim voucher page
    $tempstore->set('partner', $nid);

    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand('/claim-voucher'));

    return $response;
  }

  /**
   * Generates the printable version of voucher claim page
   *
   * @return AjaxResponse
   *
   */
  public function print($nid) {
    // Get user private temp store and get the pin code
    $tempstore = $this->userPrivateTempstore->get('pin_codes');
    $pin_code = $tempstore->get('pin_code');

    // Load the node and get the voucher code and days voucher is valid for
    $partner_node = $this->entityTypeManager->getStorage('node')->load($nid);
    $voucher_code = $partner_node->get('field_voucher_code')->value;
    $days_valid = '+' . $partner_node->get('field_days_valid')->value ?: 0 . ' days';
    $days_valid_time = strtotime($days_valid, time());

    $queryString = "UPDATE {claim_codes} SET pin_code = :pin_code, used = 1, claim_date = :claim_date, voucher_expire = :voucher_expire WHERE voucher_code = :voucher_code";

    $query = $this->database->query($queryString, [':pin_code' => $pin_code, ':claim_date' => time(), ':voucher_expire' => $days_valid_time, ':voucher_code' => $voucher_code]);

    // \Drupal::logger('$result')->notice('@type', array('@type' => dpr($result, TRUE)));
    // \Drupal::logger('$voucher_code')->notice('<pre>@type</pre>', array('@type' => print_r($voucher_code, TRUE)));



    // Unset the temporary user data that allow access to claim page
    $this->userPrivateTempstore->get('claims')->delete('partner');
    $this->userPrivateTempstore->get('pin_codes')->delete('pin_code');

    $url = Url::fromRoute('entity_print.view', ['export_type' => 'pdf', 'entity_type' => 'node', 'entity_id' => $nid])->toString();
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));

    return $response;
  }

}
