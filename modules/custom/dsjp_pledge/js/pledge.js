(function ($, Drupal) {
  Drupal.behaviors.pledgeWidget = {
    attach: function attach() {
      $('input[name*="field_dsj_initiative_beneficiary"]:checkbox').on('change', function () {
        var text =$('input[name="field_dsj_initiative_beneficiary[' + $(this).data('delta') + '][value]"');
        if (($(this).data('keep-disabled') !== 1 && !$(this).prop('checked')) || $(this).data('keep-disabled') === 1) {
          text.parent('div.form-item').addClass('form-disabled');
          text.attr('disabled', 'disabled');
        }
        else {
          text.attr('disabled', false);
          text.parent('div.form-item').removeClass('form-disabled');
        }
      });
      // Trigger change to make sure the classes and attributes are set.
      $('input[name*="field_dsj_initiative_beneficiary"]:checkbox').change();
    },
  }
})(jQuery, Drupal);
