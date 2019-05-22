<?php

namespace Drupal\pin_codes\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class DisableButtonCommand.
 */
class DisableButtonCommand implements CommandInterface {
  /**
   * A CSS selector string.
   *
   * If the command is a response to a request from an #ajax form element then
   * this value can be NULL.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs an DisableButtonCommand object.
   *
   * @param string $selector
   *   A jQuery selector.
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'disable_button',
      'selector' => $this->selector,
    ];
  }

}
