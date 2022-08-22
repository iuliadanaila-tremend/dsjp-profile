(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.popupBlock = {
    attach: function (context, settings) {
      if (drupalSettings.dsjp_content.popup_content) {
        $('body').append(drupalSettings.dsjp_content.popup_content);
        $('.popup-cta span#popup-cta-close').on('click', function () {
          $.cookie('popup_cta_closed', 1, {
            path: '/'
          });
          $('.popup-cta').addClass('hidden');
        });
        if ($.cookie('popup_cta_closed') != 1 && drupalSettings.show_popup == 1) {
          $('.popup-cta').removeClass('hidden');
        }
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
