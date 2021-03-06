<?php

/**
 * @file
 * Contains claims\claims.views.inc..
 * Provide a custom views field data that isn't tied to any other module. */

/**
 * Implements hook_views_data().
 */
function claims_views_data() {
  $data = [];

  // Claim link field
  $data['views']['claim_link_field'] = [
    'title' => t('Claim link field'),
    'help' => t('Provide a field that displays link to claim the voucher'),
    'field' => [
      'id' => 'claim_link_field',
    ]
  ];

  $data['views']['voucher_code_summary'] = [
    'title' => t('Voucher codes usage '),
    'help' => t('Provides a field that displays partner code usage summary'),
    'field' => [
      'id' => 'voucher_code_summary',
    ]
  ];

  $data['claim_codes']['table']['group'] = t('Claims');
  $data['claim_codes']['table']['provider'] = 'claims';

  $data['claim_codes']['table']['base'] = [
    'field' => 'id',
    'title' => t('Claims'),
    'help' => t('Claims data'),
  ];

  // The ID field
  $data['claim_codes']['id'] = [
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

  // The voucher code field
  $data['claim_codes']['voucher_code'] = [
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

  // The used field
  $data['claim_codes']['used'] = [
    'title' => t('Used'),
    'help' => t('Whether the claim code has been used or not'),
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

  // The partner field
  $data['claim_codes']['partner'] = [
    'title' => t('Partner'),
    'help' => t('The partner node for the code'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'base' => 'node_field_data',
      'base field' => 'nid',
      'id' => 'standard',
      'label' => t('Partner node'),
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['claim_codes_report']['table']['group'] = t('Claims Record');
  $data['claim_codes_report']['table']['provider'] = 'claims';

  $data['claim_codes_report']['table']['base'] = [
    'field' => 'id',
    'title' => t('Claims Record'),
    'help' => t('Claims record data'),
  ];

  // The ID field
  $data['claim_codes_report']['id'] = [
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

  // The voucher code field
  $data['claim_codes_report']['voucher_code'] = [
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

  // The partner field
  $data['claim_codes_report']['partner'] = [
    'title' => t('Partner'),
    'help' => t('The partner node for the code'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'base' => 'node_field_data',
      'base field' => 'nid',
      'id' => 'standard',
      'label' => t('Partner node'),
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // The voucher expire date field
  $data['claim_codes_report']['voucher_expire'] = [
    'title' => t('Voucher Expire Date'),
    'help' => t('The voucher expire date.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ]
  ];

  // The pin code field
  $data['claim_codes_report']['pin_code'] = [
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

  // The claim date field
  $data['claim_codes_report']['claim_date'] = [
    'title' => t('Claim date'),
    'help' => t('The claim code.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ]
  ];


  // Partner reference filter
  $data['claim_codes_report']['partner_titles'] = [
    'title' => t('Partner node titles'),
    'filter' => [
      'title' => t('Partner node titles'),
      'help' => t('Show partner node Titles as a list.'),
      'field' => 'partner',
      'id' => 'partner_titles',
    ],
  ];

  return $data;
}
