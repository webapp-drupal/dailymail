(function ($, Drupal) {
  Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
      $('.main-menu-container', context).once('.navbar-toggler').click(function () {
        $('.menu-items').toggle();
        $(this).toggleClass('open');
      });
    }
  };
})(jQuery, Drupal);
