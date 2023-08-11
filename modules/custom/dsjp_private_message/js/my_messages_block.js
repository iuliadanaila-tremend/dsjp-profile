(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.myMessagesBlock = {
    attach: function (context, settings) {   
      var $privateMessageBlock = $('#block-views-block-dsj-private-messages-block-private-messages', context);
      var $triggerDeleteBtn = $privateMessageBlock.find('.trigger-submit-events').addClass('disabled');

      /** select all checkboxes funtionality */
      $privateMessageBlock
        .find('input[name="select_all"]')
        .on('change', function () {
          $('.view-dsj-private-messages .vbo-view-form input:checkbox').prop('checked', $(this).prop('checked'));
        });

      /** enable / disable the delete trigger button */
      $privateMessageBlock
        .find('.ecl-checkbox__input, #edit-select-all')
        .on('change', function() {
          if (!$privateMessageBlock.find('.ecl-checkbox__input:checked').length) {
            $triggerDeleteBtn.addClass('disabled');

            return;
          }

          $triggerDeleteBtn.removeClass('disabled');
        });
      
      toggleDeleteVisibility($privateMessageBlock.find('.trigger-submit-events'), '[id*="edit-actions"]');

      function toggleDeleteVisibility($triggerElement, tooltip) {
        var $tooltip = $triggerElement.next(tooltip);

        $triggerElement
          .on('click', function () {
            if ($triggerElement.hasClass('disabled')) {
              return;
            }
            
            $tooltip.toggleClass('hidden');
          });

        $('body', context)
          .on('click', function(event) {
            var $this = $(event.target);

            if ($this.is($triggerElement)) {
              return;
            }

            if ($tooltip.hasClass('hidden')) {
              return;
            }
            
            $tooltip.addClass('hidden');
          });
      }
      
    },
  };
})(jQuery, Drupal, drupalSettings);
