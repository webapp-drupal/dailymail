<?php

namespace Drupal\claims\Plugin\views\filter;

use Drupal\field\Entity\FieldConfig;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Database\Connection;

/**
 * PartnerTitles.
 */

/**
 * Filters by given list of related content title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("partner_titles")
 */
class PartnerTitles extends ManyToOne implements PluginInspectionInterface, ContainerFactoryPluginInterface {

  // TODO this doesn't work for tax terms or users. separate filter.
  /**
   * Options to sort by.
   *
   * @var sortByOptions
   */
  private $sortByOptions;

  /**
   * Order options.
   *
   * @var sortOrderOptions
   */
  private $sortOrderOptions;

  /**
   * Unpublished options.
   *
   * @var getUnpublishedOptions
   */
  private $getUnpublishedOptions;

  /**
   * Option to filter out no results.
   *
   * @var getFilterNoResultsOptions
   */
  private $getFilterNoResultsOptions;

  /**
   * Get relationships to use.
   *
   * @var getRelationships
   */
  private $getRelationships;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The queryfactory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The dbconnection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $Connection;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager, Connection $connection, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->Connection = $connection;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    if (empty($this->getRelationships)) {
      $this->broken();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->getRelationships = $this->view->getHandlers('relationship');
    if ($this->getRelationships === NULL) {
      $this->getRelationships = [];
    }
    // Check for existence of relationship and
    // Remove non-standard and non-node relationships
    // TODO Can I get the relationship type from the handler?
    $invalid_relationships = [
      'cid',
      'comment_cid',
      'last_comment_uid',
      'uid',
      'vid',
      'nid',
    ];
    foreach ($this->getRelationships as $key => $relationship) {
      // $is_node = strpos($relationship['table'], 'ode__');.
      $is_target = strpos($relationship['id'], 'target_id');
      if ($relationship['plugin_id'] != 'standard' ||
          in_array($key, $invalid_relationships) ||
          // $is_node === false ||.
          $is_target !== FALSE) {
        unset($this->getRelationships[$key]);
      }
    }

    // Set the sort options.
    $this->sortByOptions = ['nid', 'title'];
    $this->sortOrderOptions = ['DESC', 'ASC'];
    $this->getUnpublishedOptions = ['Unpublished', 'Published', 'All'];
    $this->getFilterNoResultsOptions = ['Yes', "No"];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if (!isset($this->options['expose']['identifier'])) {
      $this->options['expose']['identifier'] = $form_state->get('id');
    }
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Define the sort params as extra options.
   */
  public function hasExtraOptions() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    $form['sort_by'] = [
      '#type' => 'radios',
      '#title' => t('Sort by'),
      '#default_value' => $this->options['sort_by'],
      '#options' => $this->sortByOptions,
      '#description' => t('On what attribute do you want to sort the node titles?'),
      '#required' => TRUE,
    ];
    $form['sort_order'] = [
      '#type' => 'radios',
      '#title' => t('Sort by'),
      '#default_value' => $this->options['sort_order'],
      '#options' => $this->sortOrderOptions,
      '#description' => t('In what order do you want to sort the node titles?'),
      '#required' => TRUE,
    ];
    $form['get_unpublished'] = [
      '#type' => 'radios',
      '#title' => t('Published Status'),
      '#default_value' => $this->options['get_unpublished'],
      '#options' => $this->getUnpublishedOptions,
      '#description' => t('Do you want Published, Unpublished or All?'),
      '#required' => TRUE,
    ];
    $form['get_filter_no_results'] = [
      '#type' => 'radios',
      '#title' => t('Filter Out Options With No Results'),
      '#default_value' => $this->options['get_filter_no_results'],
      '#options' => $this->getFilterNoResultsOptions,
      '#description' => t('Do you want to filter out options that will give no results?'),
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitExtraOptionsForm($form, FormStateInterface $form_state) {
    // Define and regenerate the options if we change the sort.
    $this->defineOptions();
    $this->generateOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    // Reduce duplicates does not work. Do we need it?
    $form['reduce_duplicates']['#default_value'] = 0;
    $form['reduce_duplicates'] = ['#disabled' => TRUE];
    // Disable the none option. we have to have a relationship.
    unset($form['relationship']['#options']['none']);
    // Disable the expose button. this should be an exposed filter.
    $form['expose_button'] = ['#disabled' => TRUE];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Always exposed.
    $options['exposed'] = ['default' => 1];

    // Get the relationships. set the first as the default.
    if (isset($this->getRelationships)) {
      $relationship_field_names = array_keys($this->getRelationships);
      $options['relationship'] = ['default' => $relationship_field_names[0], $this->getRelationships];

      // Set the sort defaults. always numeric.
      // Compare with sort options private arrays to get value for sort.
      $options['sort_order'] = ['default' => 0];
      $options['sort_by'] = ['default' => 1];
      $options['get_unpublished'] = ['default' => 1];
      $options['get_filter_no_results'] = ['default' => 1];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    // Generate the values from the helper function.
    // TODO? - regenerate the list every time the relationship field is changed.
    $this->valueOptions = $this->generateOptions();
    return $this->valueOptions;
  }

  /**
   * Helper function that generates the options.
   */
  public function generateOptions() {
    $res = [];

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'partners']);
    foreach ($nodes as $key => $node) {
      $res[$node->id()] = $node->getTitle();
    }

    return $res;
  }

}
