/**
 * @file
 * Facets views Link widgets handling.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Handle link widgets.
   */
  Drupal.behaviors.facetsLinkWidget = {
    attach: function (context, settings) {
      var $datepickerFacets = $('.js-facets-datepicker', context)
        .once('js-facets-datepicker-on-click');

        // We are using list wrapper element for Facet JS API.
      if ($datepickerFacets.length > 0) {
        $datepickerFacets
          .each(function (index, widget) {
            var facetId = $(this).attr('data-drupal-facet-id');
            var values = {};
            var defaultDate = '';

            if(settings.facets.datepickerValues !== 'undefined'){
                values = settings.facets.datepickerValues[facetId]['values'];
                defaultDate = settings.facets.datepickerValues[facetId]['activeDate'];
            }

            var $widget = $(widget);
            $widget.datepicker({
              defaultDate: defaultDate,
              dateFormat: 'dd-mm-yy',
              beforeShowDay: function (date) {
                var string = jQuery.datepicker.formatDate('yy-mm-dd', date);
                return [string in values];
              },
              onSelect: function (selectedDate) {
                var string = jQuery.datepicker.formatDate('yy-mm-dd', $(this).datepicker('getDate'));
                window.location = values[string]['url'];
              }
            });
            $widget.val(defaultDate);

            var $widgetLinks = $widget.find('.facet-item > a');

            // Click on link will call Facets JS API on widget element.
            var clickHandler = function (e) {
              e.preventDefault();

              $widget.trigger('facets_filter', [$(this).attr('href')]);
            };

            // Add correct CSS selector for the widget. The Facets JS API will
            // register handlers on that element.
            $widget.addClass('js-facets-widget');

            // Add handler for clicks on widget links.
            $widgetLinks.on('click', clickHandler);

            // We have to trigger attaching of behaviours, so that Facets JS API can
            // register handlers on link widgets.
            Drupal.attachBehaviors(this.parentNode, Drupal.settings);
          });
      }
    }
  };

})(jQuery, Drupal);
