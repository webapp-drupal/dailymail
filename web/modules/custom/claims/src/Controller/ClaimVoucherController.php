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
use Drupal\Core\Ajax\PrependCommand;
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

    // Get user private temp store and get the pin code
    $tempstore = $this->userPrivateTempstore->get('pin_codes');
    $pin_code = $tempstore->get('pin_code');

    $voucher_code = $this->getVoucherCode($partner_node);

    // Load the node and get the voucher code and days voucher is valid for
    $num_days = !empty($partner_node->get('field_days_valid')->value) ? $partner_node->get('field_days_valid')->value : '0';
    $days_valid = '+' . $num_days . ' days';

    $days_valid_time = strtotime($days_valid, time());

    // Insert into report table
    $result = $this->database->insert('claim_codes_report')
      ->fields([
        'voucher_code' => $voucher_code,
        'partner' => $partner_nid,
        'pin_code' => $pin_code,
        'claim_date' => REQUEST_TIME,
        'voucher_expire' => $days_valid_time,
      ])
      ->execute();

    // Set voucher code as used
    $queryString = "UPDATE {claim_codes} SET used = 1 WHERE voucher_code = :voucher_code";
    $query = $this->database->query($queryString, [':voucher_code' => $voucher_code]);

    // Set pin code as used
    $queryString = "UPDATE {pin_codes} SET pin_code_used = 1 WHERE pin_code = :pin_code";
    $query = $this->database->query($queryString, [':pin_code' => $pin_code]);

    // Unset the temporary user data that allow access to claim page
    // $this->userPrivateTempstore->get('claims')->delete('voucher_code');
    // $this->userPrivateTempstore->get('claims')->delete('partner');
    // $this->userPrivateTempstore->get('pin_codes')->delete('pin_code');

    $node = $this->entityTypeManager->getViewBuilder('node')->view($partner_node);

    return $node;
  }

  protected function getVoucherCode($node) {
    if ($node->field_barcode_image && $node->field_barcode_image->entity) {
      $voucher_code = 'barcode';
    }
    else {
      $nid = $node->id();
      $connection = \Drupal::database();

      $query = $connection->query('SELECT voucher_code FROM {claim_codes} WHERE partner = :nid AND used = 0 LIMIT 1', [':nid' => $nid]);
      $voucher_code = $query->fetchField();

      $tempstore = \Drupal::service('user.private_tempstore')->get('claims');
      $tempstore->set('voucher_code', $voucher_code, 3600);
    }

    return $voucher_code;
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
    $tempstore->set('partner', $nid, 3600);

    // check if given partner has voucher code
    $connection = \Drupal::database();
    $query = $connection->query('SELECT voucher_code FROM {claim_codes} WHERE partner = :nid AND used = 0 LIMIT 1', [':nid' => $nid]);
    $voucher_code = $query->fetchField();

    $response = new AjaxResponse();

    if (empty($voucher_code)) {
      $messenger = \Drupal::messenger();
      $messenger->addMessage('We’re sorry – there seems to be a temporary error.  Please try again shortly.  Thank you.', $messenger::TYPE_ERROR);

      $status_messages = array('#type' => 'status_messages');
      $messages = \Drupal::service('renderer')->renderRoot($status_messages);

      $status_messages = [
        '#theme' => 'better_messages_wrapper',
        '#children' => $messages,
      ];

      $messages = \Drupal::service('renderer')->renderRoot($status_messages);

      if (!empty($messages)) {
        $response->addCommand(new PrependCommand('#views-bootstrap-partners-page-1', $messages));
      }

      return $response;
    }

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
    $url = Url::fromRoute('entity_print.view', ['export_type' => 'pdf', 'entity_type' => 'node', 'entity_id' => $nid])->toString();
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));

    return $response;
  }

}
