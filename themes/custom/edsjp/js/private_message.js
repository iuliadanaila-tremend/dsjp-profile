(function ($, Drupal) {
  Drupal.behaviors.privateMessagePage = {
    attach: function (context, settings) {
      var $thread = $('.private-message-thread');
      toggleDeleteVisibility($thread.find('.trigger-delete-visibility'), '.private_message_thread_delete_link_wrapper');

      function toggleDeleteVisibility($triggerElement, tooltip) {
        var $tooltip = $triggerElement.next(tooltip);

        $triggerElement
        .on('click', function () {
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

      /** Overlapping input used on the Add new contact popup so we can display picture*/
      var $input = $('div#visible-overlap-input');
      $('input[name="members[0][target_id]"]').on('autocompleteselect', function (e, ui) {
        $input.html('<div class="pill-selected">' + ui.item.label + '<span class="drop-select"></span>' + '</div>');
        $input.addClass("compleated");
        $('input[name="members[0][target_id]"]').addClass("compleated");
        $('span.drop-select').on('click',function() {
          $input.html('');
          $('input[name="members[0][target_id]"]').val('');
          $input.removeClass("compleated");
        $('input[name="members[0][target_id]"]').removeClass("compleated");
        });
      });

      $input.on('input', function() {
        var fakeDiv = this
        $('input[name="members[0][target_id]"]').val($(fakeDiv).html()).autocomplete("search");
      });

      function hideLoadPrevious() {
        if (drupalSettings.private_message) {
          if ($('.private-message-thread-messages .field--name-private-messages div.field__items > div').length == drupalSettings.private_message.total_messages) {
            $('div#load-previous-messages-button-wrapper').addClass('hidden');
          }
        }
      }
      hideLoadPrevious();
      $(document).ajaxComplete(function(e) {
        hideLoadPrevious();
      });
    }
  };
})(jQuery, Drupal);
