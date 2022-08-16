/**
 * @file
 * Infinite Scroll JS.
 */

(function ($, Drupal, debounce) {
  "use strict";

  // The threshold for how far to the bottom you should reach before reloading.
  var scrollThreshold = 200;

  // The selector for the automatic pager.
  var automaticPagerSelector = '[data-drupal-views-infinite-scroll-pager="automatic"]';

  // The selector for both manual load and automatic pager.
  var pagerSelector = '[data-drupal-views-infinite-scroll-pager]';

  // The selector for the automatic pager.
  var contentWrapperSelector = '[data-drupal-views-infinite-scroll-content-wrapper]';

  // The event and namespace that is bound to window for automatic scrolling.
  var scrollEvent = 'scroll.views_infinite_scroll';

  /**
   * Insert a views infinite scroll view into the document.
   *
   * @param {jQuery} $newView
   *   New content detached from the DOM.
   */
  $.fn.infiniteScrollInsertView = function ($newView) {
    // Extract the view DOM ID from the view classes.
    var matches = /(js-view-dom-id-\w+)/.exec(this.attr('class'));

    if (!matches) {
      return;
    }

    var currentViewId = matches[1].replace('js-view-dom-id-', 'views_dom_id:');

    // Get the existing ajaxViews object.
    var view = Drupal.views.instances[currentViewId];
    // Remove once so that the exposed form and pager are processed on
    // behavior attach.
    view.$view.removeOnce('ajax-pager');
    view.$exposed_form.removeOnce('exposed-form');
    // Make sure infinite scroll can be reinitialized.
    var $existingPager = view.$view.find(pagerSelector);
    $existingPager.removeOnce('infinite-scroll');

    var $newRows = $newView.find(contentWrapperSelector).children();
    var $newPager = $newView.find(pagerSelector);

    if (view.$view.find(contentWrapperSelector).children().hasClass('vbo-view-form')) {
      var $oldRows = view.$view.find(contentWrapperSelector);
      var $newHeader = $newRows.find('div[id^=edit-header].js-form-wrapper');
      $newRows.find('div[id^=edit-header].js-form-wrapper').remove();
      $newRows.prepend($oldRows.children().find('.views-row'));
      $newRows.prepend($newHeader);
      $oldRows.children()
        // Add the new rows to existing view.
        .replaceWith($newRows);
      $newRows
        // Trigger a jQuery event on the wrapper to inform that new content was
        // loaded and allow other scripts to respond to the event.
        .trigger('views_infinite_scroll.new_content', $newRows.clone())
    }
    else {
      view.$view.find(contentWrapperSelector)
        // Trigger a jQuery event on the wrapper to inform that new content was
        // loaded and allow other scripts to respond to the event.
        .trigger('views_infinite_scroll.new_content', $newRows.clone())
        // Add the new rows to existing view.
        .append($newRows);
    }

    // Replace the pager link with the new link and ajaxPageState values.
    $existingPager.replaceWith($newPager);

    // Run views and VIS behaviors.
    Drupal.attachBehaviors(view.$view[0]);
  };

  /**
  * Get the closest scrollable parent
  *
  * @param {jQuery} $element
  *   jQuery instance of the element.
  * @param includeHidden
  *   If the container with hidden scrollbar should be included.
  *
  * @return {jQuery}
  *   jQuery instance of the closest scrollable parent.
  */
  function getScrollParent($element, includeHidden) {
    var position = $element.css('position'),
      excludeStaticParent = position === 'absolute',
      overflowRegex = includeHidden ? /(auto|scroll|hidden)/ : /(auto|scroll)/,
      scrollParent = $element
        .parents()
        .filter(function () {
          var parent = $(this);
          if (excludeStaticParent && parent.css('position') === 'static') {
            return false;
          }
          return overflowRegex.test(
            parent.css('overflow') +
            parent.css('overflow-y') +
            parent.css('overflow-x')
          );
        })
        .eq(0);

    return position === 'fixed' || !scrollParent.length ?
        $($element[0].ownerDocument || document)
        : scrollParent;
  }

  /**
    * Determine if the scrollbar is reaching the bottom of a element.
    *
    * @param {jQuery} $element
    *   jQuery instance of the element.
    * @param {jQuery} $pager
    *   jQuery instance of the pager.
    *
    * @return {boolean}
    *   Is the bottom reached or not.
    */
  function isBottomReached($element, $pager) {
    // If the scroll position is equal or larger than the total height of the scrollbar,
    // that means the bottom of the element is reached.
    if ($element.is(document)) {
      const $window = $(window);
      return (
        $window.innerHeight() + $window.scrollTop() >=
          $pager.offset().top - scrollThreshold
        );
    }
    return (
      $element.innerHeight() + $element.scrollTop() >=
      $element[0].scrollHeight - scrollThreshold
    );
  }

  /**
   * Handle the automatic paging based on the scroll amount.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Initialize infinite scroll pagers and bind the scroll event.
   * @prop {Drupal~behaviorDetach} detach
   *   During `unload` remove the scroll event binding.
   */
  Drupal.behaviors.views_infinite_scroll_automatic = {
    attach : function (context, settings) {
      $(context).find(automaticPagerSelector).once('infinite-scroll').each(function () {
        var $pager = $(this);
        $pager.addClass('visually-hidden');
        // Attach the scroll event to the first scrollable container
        // of the pager.
        var $parent = $(getScrollParent($pager));
        var $scroller = $parent.is(document) ? $(window) : $parent;
        $scroller.on(scrollEvent, debounce(function() {
          if (isBottomReached($parent, $pager)) {
            $pager.find('[rel=next]').click();
            $scroller.off(scrollEvent);
          }
        }, 200));
      });
    },
    detach: function (context, settings, trigger) {
      // In the case where the view is removed from the document, remove it's
      // events. This is important in the case a view being refreshed for a reason
      // other than a scroll. AJAX filters are a good example of the event needing
      // to be destroyed earlier than above.
      if (trigger === 'unload') {
        var $pagers = $(context).find(automaticPagerSelector);
        if ($pagers.removeOnce('infinite-scroll').length) {
            $pagers.each(function(index, element) {
              getScrollParent($(element)).off(scrollEvent);
            });
        }
      }
    }
  };

  /**
   * Views AJAX pagination filter.
   *
   * In case the Entity View Attachment is rendered in a view context,
   * the default filter function prevents the required 'Use AJAX' setting
   * to work.
   *
   * @return {Boolean}
   *   Whether to apply the Views AJAX paginator. VIS requires this setting
   *   for pagination.
   */
  Drupal.views.ajaxView.prototype.filterNestedViews = function () {
    return this.$view.hasClass('view-eva') || !this.$view.parents('.view').length;
  };

})(jQuery, Drupal, Drupal.debounce);
