/**
 * @file
 * ECL Datepicker initializer.
 */
(function (ECL, Drupal, $) {
  Drupal.behaviors.eclDatepicker = {
    attach: function attach(context, settings) {
      $('[data-ecl-datepicker-toggle]', context).once('.datepickersInitialized').each(function () {
        var datepicker = new ECL.Datepicker($(this)[0], {
          format: settings.oe_theme.ecl_datepicker_format
        });
        datepicker.init();
      });
    }
  };
})(ECL, Drupal, jQuery);
