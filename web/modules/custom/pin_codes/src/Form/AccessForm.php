<?php

namespace Drupal\pin_codes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\pin_codes\Ajax\DisableButtonCommand;

/**
 * Class AccessForm.
 */
class AccessForm extends FormBase {

  /**
   * Drupal\user\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $userPrivateTempstore;
  /**
   * Drupal\Core\Session\SessionManagerInterface definition.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;
  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new AccessForm object.
   */
  public function __construct(
    PrivateTempStoreFactory $user_private_tempstore,
    SessionManagerInterface $session_manager,
    AccountProxyInterface $current_user,
    Connection $database
  ) {
    $this->userPrivateTempstore = $user_private_tempstore;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user'),
      $container->get('database')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'access_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Attach our library
    $form['#attached']['library'][] = 'pin_codes/ajax_commands';

    $form['#attributes'] = [
      'class' => ['form-inline']
    ];

    $form['pin_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Your Unique Claim Code!'),
      '#maxlength' => 15,
      '#size' => 15,
      '#weight' => '0',
      // '#ajax' => [
      //   'callback' => [$this, 'validatePinCode'],
      //   'event' => 'keyup',
      //   'progress' => [
      //     'type' => 'throbber',
      //     'message' => t('Verifying pin code...'),
      //   ]
      // ],
      // '#suffix' => '<span class="pin-invalid-message"></span>',
      '#attributes' => [
        'class' => ['input-lg']
      ]
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => ['btn btn-black'],
        // 'disabled' => 'disabled'
      ]
    ];

    return $form;
  }

  /**
   * Ajax callback to validate the pin code field.
   */
  public function validatePinCode(array &$form, FormStateInterface $form_state) {
    $pin = $form_state->getValue('pin_code');

    // Look for the pin in our custom table
    $queryString = 'SELECT * FROM {pin_codes} WHERE BINARY pin_code = :pin_code AND pin_code_used = 0';
    $query = $this->database->query($queryString, [':pin_code' => $pin]);
    $result = $query->fetchField();

    $response = new AjaxResponse();

    if (empty($pin)) {
      return $response;
    }
    else if (empty($result)) {
      // set the form error
      $css = ['border' => '2px solid red'];
      $message = $this->t('Pin Code Not Valid. Please check the pin code.');
      $response->addCommand(new CssCommand('#edit-pin-code', $css));
      $response->addCommand(new HtmlCommand('.pin-invalid-message', $message));
      $response->addCommand(new DisableButtonCommand('.form-submit'));
    }
    else {
      $css = ['border' => '1px solid #ddd'];
      $message = $this->t('Pin code Valid, please submit.');
      $response->addCommand(new CssCommand('#edit-pin-code', $css));
      $response->addCommand(new HtmlCommand('.pin-invalid-message', $message));
      $response->addCommand(new InvokeCommand('.form-submit', 'removeAttr', ['disabled']));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $pin = $form_state->getValue('pin_code');

    // Look for the pin in our custom table
    $queryString = 'SELECT * FROM {pin_codes} WHERE BINARY pin_code = :pin_code AND pin_code_used = 0';
    $query = $this->database->query($queryString, [':pin_code' => $pin]);
    $result = $query->fetchField();

    if (empty($result)) {
      $form_state->setErrorByName('pin_code', "Sorry - your Unique Claim is not recognised.   Please check and try again.  If you continue to experience problems, please  contact us on 0330 100 0601, 9am-6pm.");
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pin = $form_state->getValue('pin_code');

    // Get user private temp store
    $tempstore = $this->userPrivateTempstore->get('pin_codes');

    // Set the pincode to store for checking views access
    // And set the expiry to 3600 (1 hour)
    $tempstore->set('pin_code', $pin, 3600);

    // Redirect to partners page
    $form_state->setRedirect('view.partners.page_1');
    return;
  }

}
