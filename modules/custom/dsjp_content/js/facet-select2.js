(function ($, Drupal) {
  Drupal.behaviors.facetSelect2 = {
    attach: function (context, settings) {
      $('.facets-widget-dropdown select').select2({
        width: '100%'
      });
    }
  };
})(jQuery, Drupal);
