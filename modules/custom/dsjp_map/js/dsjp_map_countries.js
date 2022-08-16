(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.mapComponent = {
    attach: function (context) {
      $('.world-map-container .small-countries .country-svg', context)
        .hover(function (e) {
          let countryId = $(this).find('svg').attr('id');
          $('div#tooltip-' + countryId).toggleClass('hidden');
        })
        .on('click', function () {
          $('.world-map-container').addClass("info-opened");
          $('div.country-container:not(.hidden)').toggleClass('hidden');
          $('div.country-container.code-' + $(this).find('svg').attr('id')).toggleClass('hidden');
        });
    }
  };

})(jQuery, Drupal, drupalSettings);