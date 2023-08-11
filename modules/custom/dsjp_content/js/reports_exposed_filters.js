(function ($, Drupal) {
  Drupal.behaviors.exposedFilters = {
    attach: function (context, settings) {
      // Reset the exposed filters when the content type filter is changed.
      $('select[name^="type"]').change(function () {
        var value = $(this).val();
        $(this).closest('form')[0].reset();
        $(this).closest('form').find('input[type="text"]').val('');
        $(this).closest('form').find('select option').removeAttr('selected');
        $(this).val(value);
      });
      // Display Learning content type column only for Learning content.
      var selectedOption = $('select[name^="type"] option:selected');
      if(selectedOption.val() === 'dsj_learning_content' || selectedOption.text() === 'Learning content') {
        $(".views-field.views-field-field-dsj-learning-content-type").show();
      }
      else {
        $(".views-field.views-field-field-dsj-learning-content-type").hide();
      }
      // Hide the status filter.
      $('div.form-item-status').addClass('hidden');
      // When moderation state changes and the discussion is set as a filter,
      // change the status filter values too.
      $('select[name="moderation_state"]').change(function () {
        var statusSelect = $('select[name="status"]');
        if (statusSelect.length > 0) {
          var value = $('select[name^="type"]').val();
          if (value.length === 0 || $.inArray('dsj_discussion', value) !== -1 || value === 'dsj_discussion') {
            if ($(this).val() == 2) {
              statusSelect.val(1);
            }
            else if ($(this).val() === 'All') {
              statusSelect.val('All');
            }
            else {
              statusSelect.val(0);
            }
            statusSelect.change();
          }
        }
      });
    }
  };
})(jQuery, Drupal);
