<?php

namespace Drupal\claims\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Random;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Link;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("voucher_code_summary")
 */
class VoucherCodeSummary extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $connection = \Drupal::database();
    $total_vouchers = $connection->select('claim_codes', 'c')
      ->condition('c.partner', $values->nid, '=')
      ->countQuery()->execute()->fetchField();

    $used_vouchers = $connection->select('claim_codes', 'c')
      ->condition('c.partner', $values->nid, '=')
      ->condition('c.used', 1, '=')
      ->countQuery()->execute()->fetchField();

    $summary = $used_vouchers . ' of ' . $total_vouchers . ' used';
    $link = Link::createFromRoute($this->t($summary), 'claims.claims_report', ['nid' => $values->nid], ['attributes' => ['class' => 'use-ajax', 'data-dialog-type' => 'modal', 'data-dialog-options' => json_encode(['width' => '90%'])]])->toString();

    return $link;
  }

}
