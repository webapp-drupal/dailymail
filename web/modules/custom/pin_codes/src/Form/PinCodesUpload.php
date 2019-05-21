<?php

namespace Drupal\pin_codes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Implements PinCodesUpload form controller.
 *
 * This is required for form buildin to upload file with pin codes
 * File upload Form
 */

class PinCodesUpload extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pin_codes_upload';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // File.
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => 'Upload CSV file with Pin Codes',
      '#description' => $this->t('Only : @extentions File type ',['@extentions' => 'csv']),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => 'public://pin_codes/',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#description' => $this->t('Upload file, #type = submit'),

    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // getting file name for feature db query
    if ($form_state->hasFileElement()) {
      $fileArray = $form_state->getValue('file');

      if (is_array($fileArray)) {
        if (isset($fileArray[0])) {
          $file_id = $fileArray[0];
          $file = \Drupal\file\Entity\File::load($file_id);

          if ($file != NULL) {
            $filename = $file->getFilename();
            $absolute_path = \Drupal::service('file_system')->realpath($file->getFileUri());
          }
        }
      }
    }

    //starting DB conection
    if ($absolute_path) {
      $connection = \Drupal::database();
      $queryString = "LOAD DATA LOCAL INFILE '" . $absolute_path . "' INTO TABLE {pin_codes} FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' IGNORE 1 LINES (pin_code)";
      $query = $connection->query($queryString);

      $messenger = \Drupal::messenger();
      $messenger->addMessage('Pin codes successfully uploaded', $messenger::TYPE_STATUS);
    }
  }

}
