(function ($, Drupal) {
  Drupal.AjaxCommands.prototype.disable_button = function (ajax, response, status) {
    $(response.selector).attr('disabled', 'disabled');
  }
})(jQuery, Drupal);
