<?php

namespace Drupal\pin_codes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class ManagePincodesForm.
 */
class ManagePincodesForm extends FormBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;
  /**
   * Constructs a new ManagePincodesForm object.
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
    return 'manage_pincodes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $conditions = $_GET;

    $query = $this->database->select('pin_codes', 'p')
      ->fields('p', ['id', 'pin_code', 'pin_code_used']);

    if (!empty($conditions['pin_code'])) {
      $query->condition('p.pin_code', $conditions['pin_code'], '=');
    }

    if (!empty($conditions['used']) && $conditions['used'] != 'any') {
      $val = $conditions['used'] == 'yes' ? 1 : 0;
      $query->condition('p.pin_code_used', $val, '=');
    }

    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $results = $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $header = $rows = [];

    $header = ['id' => 'ID', 'pin_code' => 'Pin code', 'pin_code_used' => 'Pin code Used '];

    foreach ($results as $key => $result) {
      $rows[$result['pin_code']] = [
        'id' => $result['id'],
        'pin_code' => $result['pin_code'],
        'pin_code_used' => $result['pin_code_used'] ? 'Yes' : 'No',
      ];
    }

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form--inline clearfix']
      ],
    ];

    $form['container']['pin_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pin Code'),
      '#default_value' => !empty($conditions['pin_code']) ? $conditions['pin_code'] : '',
      '#maxlength' => 11,
      '#size' => 11,
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
    $pin_code = $values['pin_code'];
    $used = $values['used'];
    $query = [
      'pin_code' => $values['pin_code'],
      'used' => $values['used']
    ];

    $form_state->setRedirect('pin_codes.manage_pincodes_form', [], ['query' => $query]);
  }

  /**
   * Resets the form
   */
  public function reset(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('pin_codes.manage_pincodes_form');
  }


  /**
   * Delete the selected codes
   */
  public function delete(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $selected_rows = array_values(array_filter($values['table']));

    if (!empty($selected_rows)) {
      $query = $this->database->delete('pin_codes')
      	->condition('pin_code', $selected_rows, 'in')
      	->execute();

      drupal_set_message(count($selected_rows) . ' codes successfully deleted', 'status');
    }

  }

}
