<?php

/**
 * @file
 * Contains pin_codes\pin_codes.views.inc.
 * Provide a custom views field data that isn't tied to any other module. */

/**
 * Implements hook_views_data().
 */
function pin_codes_views_data() {
  $data = [];

  $data['pin_codes']['table']['group'] = t('Pin codes');
  $data['pin_codes']['table']['provider'] = 'pin_codes';

  $data['pin_codes']['table']['base'] = [
    'field' => 'id',
    'title' => t('Pin codes'),
    'help' => t('Partner codes'),
  ];

  // The ID field
  $data['pin_codes']['id'] = [
    'title' => t('ID'),
    'help' => t('The record ID.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  // The pin code field
  $data['pin_codes']['pin_code'] = [
    'title' => t('Voucher Code'),
    'help' => t('The voucher code.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // The pin code field
  $data['pin_codes']['pin_code'] = [
    'title' => t('Pin Code'),
    'help' => t('The pin code.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];


  // The used field
  $data['pin_codes']['pin_code_used'] = [
    'title' => t('Used'),
    'help' => t('Whether the pin code has been used or not'),
    'field' => [
      'id' => 'boolean',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'boolean',
      'label' => t('Used'),
      'type' => 'yes-no',
      'use_equal' => TRUE,
    ]
  ];

  return $data;
}
