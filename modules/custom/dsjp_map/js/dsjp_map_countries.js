(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.mapComponent = {
    attach: function (context) {
      $('.world-map-container .small-countries .country-svg', context)
        .hover(function (e) {
          let countryId = $(this).find('svg').attr('id');
          $('div#tooltip-' + countryId).toggleClass('hidden');
        })
        .on('click', function () {
          $('div.active-country svg').each(function(){
            $(this).find("path").css({ fill: $(this).attr('originalfill') });
          });
          $('div.active-country').removeClass('active-country');
          $('.world-map-container').addClass('info-opened');
          $('div.country-container:not(.hidden)').toggleClass('hidden');
          $('div.country-container.code-' + $(this).find('svg').attr('id')).toggleClass('hidden');

          $(this).addClass('active-country');
          $(this).each(function(){
            $(this).find("path").css({ fill: '#b13e65' });
          });
          $('#'+ $('#world-map').attr("data-active-country")).attr('fill', $('#world-map').attr("data-active-color"));
        });
    }
  };

})(jQuery, Drupal, drupalSettings);
