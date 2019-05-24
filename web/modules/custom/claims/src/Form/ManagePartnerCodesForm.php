<?php

namespace Drupal\claims\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class ManagePartnerCodesForm.
 */
class ManagePartnerCodesForm extends FormBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;
  /**
   * Constructs a new ManagePartnerCodesForm object.
   */
  public function __construct(
    Connection $database
  ) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_partner_codes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $conditions = $_GET;

    $query = $this->database->select('claim_codes', 'c')
      ->fields('c', ['id', 'voucher_code', 'partner', 'used']);

    if (!empty($conditions['voucher_code'])) {
      $query->condition('c.voucher_code', $conditions['voucher_code'], '=');
    }

    if (!empty($conditions['partner']) && $conditions['partner'] != 'any') {
      $query->condition('c.partner', $conditions['partner'], '=');
    }

    if (!empty($conditions['used']) && $conditions['used'] != 'any') {
      $val = $conditions['used'] == 'yes' ? 1 : 0;
      $query->condition('c.pin_code_used', $val, '=');
    }

    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $results = $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $header = $rows = [];

    $header = ['id' => 'ID', 'voucher_code' => 'Claim code', 'partner' => 'Partner', 'used' => 'Used'];

    foreach ($results as $key => $result) {
      $rows[$result['voucher_code']] = [
        'id' => $result['id'],
        'voucher_code' => $result['voucher_code'],
        'partner' => $result['partner'],
        'used' => $result['used'] ? 'Yes' : 'No',
      ];
    }

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form--inline clearfix']
      ],
    ];

    $form['container']['voucher_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Claim Code'),
      '#default_value' => !empty($conditions['voucher_code']) ? $conditions['voucher_code'] : '',
      '#maxlength' => 11,
      '#size' => 11,
      '#weight' => '0',
    ];

    $form['container']['partner'] = [
      '#type' => 'select',
      '#title' => $this->t('Partner'),
      '#options' => $this->getPartnerOptions(),
      '#default_value' => !empty($conditions['partner']) ? $conditions['partner'] : '',
      '#weight' => '0',
    ];

    $form['container']['used'] = [
      '#type' => 'select',
      '#title' => $this->t('Used'),
      '#options' => ['any' => $this->t('Any'), 'yes' => $this->t('Yes'), 'no' => $this->t('No')],
      '#default_value' => !empty($conditions['used']) ? $conditions['used'] : '',
      '#weight' => '0',
    ];

    $form['container']['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form-actions']
      ],
    ];

    $form['container']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['container']['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#name' => 'reset',
      '#submit' => array([$this, 'reset']),
    ];

    $form['container']['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#name' => 'delete',
      '#submit' => array([$this, 'delete']),
    ];

    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => t('No pin codes found'),
    ];

    $form['pager'] = array(
      '#type' => 'pager'
    );

    return $form;
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
    $values = $form_state->getValues();

    $query = [
      'voucher_code' => $values['voucher_code'],
      'partner' => $values['partner'],
      'used' => $values['used']
    ];

    $form_state->setRedirect('claims.manage_partner_codes_form', [], ['query' => $query]);

  }

  /**
   * Resets the form
   */
  public function reset(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('claims.manage_partner_codes_form');
  }


  /**
   * Delete the selected codes
   */
  public function delete(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $selected_rows = array_values(array_filter($values['table']));

    if (!empty($selected_rows)) {
      $query = $this->database->delete('claim_codes')
        ->condition('voucher_code', $selected_rows, 'in')
        ->execute();

      drupal_set_message(count($selected_rows) . ' codes successfully deleted', 'status');
    }

  }

  /**
   * Helper function that generates the options.
   */
  protected function getPartnerOptions() {
    $res = ['any' => 'Any'];

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'partners']);
    foreach ($nodes as $key => $node) {
      $res[$node->id()] = $node->getTitle();
    }

    return $res;
  }

}
