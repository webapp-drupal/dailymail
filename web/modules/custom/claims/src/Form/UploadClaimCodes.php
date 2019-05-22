<?php

namespace Drupal\claims\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class UploadClaimCodes.
 */
class UploadClaimCodes extends FormBase {

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
   * Constructs a new UploadClaimCodes object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'upload_claim_codes';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['partner'] = [
      '#type' => 'select',
      '#title' => $this->t('Partner'),
      '#description' => $this->t('Partner to upload claim codes for'),
      '#options' => $this->getPartnerOptions(),
      '#weight' => 0,
    ];

    $form['claim_codes'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Claim Codes'),
      '#description' => $this->t('Only : @extentions File type ',['@extentions' => 'csv']),
      '#weight' => 1,
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => 'public://claim_codes/',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 3,
    ];

    return $form;
  }

  /**
   * Fetches the option list for partner values
   */
  public function getPartnerOptions() {
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['type' => 'partners']);
    $options = [];

    foreach ($nodes as $node) {
      $options[$node->id()] = $node->getTitle();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasFileElement()) {
      $fileArray = $form_state->getValue('claim_codes');

      if (is_array($fileArray)) {
        if (isset($fileArray[0])) {
          $file_id = $fileArray[0];
          $file = \Drupal\file\Entity\File::load($file_id);

          if ($file != NULL) {
            $filename = $file->getFilename();

            // Get the absolute file path to pass to the query
            $absolute_path = \Drupal::service('file_system')->realpath($file->getFileUri());
          }
        }
      }
    }

    if ($absolute_path) {
      $partner = $form_state->getValue('partner');
      $queryString = "LOAD DATA LOCAL INFILE '" . $absolute_path . "' INTO TABLE {claim_codes} FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' IGNORE 1 LINES (voucher_code) SET used = 0, pin_code = '', claim_date = 0, voucher_expire = 0, partner = " . $partner;
      $query = $this->database->query($queryString);

      $messenger = \Drupal::messenger();
      $messenger->addMessage('Claim codes successfully uploaded', $messenger::TYPE_STATUS);
    }

  }

}
