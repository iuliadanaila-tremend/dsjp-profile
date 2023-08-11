(function($) {
  // Argument passed from InvokeCommand.
  $.fn.hideListItem = function(argument) {
    let listParent = $(this).closest(argument);
    listParent.hide();
  };
})(jQuery);