(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.initMenu = {
    attach: function attach() {
      var elt = document.querySelector("[data-ecl-menu]");
      var m = new window.ECL.Menu(elt, { attachSwipeListener: false });
      m.init();
    },
  };

  Drupal.behaviors.commentsPlaceholders = {
    attach: function (element) {
      if (typeof CKEDITOR !== 'undefined') {
        CKEDITOR.on('instanceLoaded', function (e) {
          for (var i in CKEDITOR.instances) {
            var text = '';
            if (i.indexOf('comment') !== -1) {
              text = Drupal.t('Write a new comment');
              var object = $('[id="' + i + '"]').data('parent-author');
              if (typeof object !== 'undefined') {
                var parentAuthor = object.length > 0 ? object : '';
                text = Drupal.t('Reply to @author post', {'@author': parentAuthor});
              }
            }
            if (typeof CKEDITOR.instances[i].editable() !== 'undefined') {
              CKEDITOR.instances[i].editable().editor.config.editorplaceholder = text;
            }
          }
        });
        CKEDITOR.on('instanceReady', function(evt) {
          evt.editor.on('blur', function (e) {
            $('[id="' + this.name + '"]').closest('form').find('div.form-actions').find('button').removeClass('focus');
          });
          evt.editor.on('focus', function (e) {
            $('[id="' + this.name + '"]').closest('form').find('div.form-actions').find('button').addClass('focus');
          });
          if ($('body').hasClass('private-messages-page')) {
            evt.editor.document.getBody().setAttribute('id', 'private-message-ckeditor');
            evt.editor.on('autoGrow', function (e) {
              e.cancel();
            });
          }
        });
      }
      $('span.comment-attachment-toggle').once('toggleAttachment').on('click', function () {
        $(this).next('div').toggleClass('hidden');
      });
      $('input[name*="dsj_attachments"]').once('toggleAttachAttachment').change(function () {
        var spanText = $(this).prev().find('span').text();
        $(this).prev().find('span').after('<div class="file-name">' + this.value.replace(/.*[\/\\]/, '') + '</div>')
        $(this).next('button').addClass('is-visible');
      });
    }
  };

  Drupal.behaviors.fieldLoadMoreLoader = {
    attach: function (context) {
      $(".load-more-btn", context).bind("click", function (e) {
        e.preventDefault();

        var $parentFieldWrapper = $(this).closest(".field-load-more");
        var parentWrapperClass = $(this).attr("data-field-class");
        var $itemsWrapper = $parentFieldWrapper.hasClass('field__items') ? $parentFieldWrapper : $(".field__items", $parentFieldWrapper).first();
        var itemLimit =
          drupalSettings.field_load_more[parentWrapperClass].limit;
        var count = 0;
        $itemsWrapper.children(".field__item.hidden").each(function () {
          $(this).removeClass("hidden");
        });
        var invisibleItemsCount = $itemsWrapper.children(".field__item.hidden").length;
        if (invisibleItemsCount === 0) {
          $(this).addClass("hidden");
          $itemsWrapper.children("div").children("a.view-less-btn").removeClass("hidden");
        }
      });
      $(".view-less-btn", context).bind("click", function (e) {
        e.preventDefault();

        var $parentFieldWrapper = $(this).closest(".field-load-more");
        var $itemsWrapper = $parentFieldWrapper.hasClass('field__items') ? $parentFieldWrapper : $(".field__items", $parentFieldWrapper).first();
        var parentWrapperClass = $(this).attr("data-field-class");
        var itemLimit =
          drupalSettings.field_load_more[parentWrapperClass].limit;

        // Take only the first field items in case we have nested "Show more" buttons.
        $itemsWrapper.children(".field__item:not(.hidden)").each(function (
          index,
          element
        ) {
          if (index >= itemLimit) {
            $(element).addClass("hidden");
          }
        });
        $(this).addClass("hidden");
        $itemsWrapper.children("div").children("a.load-more-btn").removeClass("hidden");
      });

      /** self assessment */
      $(".view-dsj-assessments", context)
        .find(".assessment-toggler .toggle")
        .on("click", function (event) {
          var $this = $(event.target);

          $this
            .closest(".paragraph--type--dsj-assessment")
            .find(".assessment-header")
            .toggleClass("expanded");
        });
    },
  };

  Drupal.behaviors.menuItems = {
    attach: function (context) {
      var $menuItems = $(".ecl-menu__item", context);

      $menuItems.on("mousedown", function (event) {
        event.preventDefault();
      });

      $("body").on("mouseup", function () {
        $menuItems.removeClass("ecl-menu__item--focused");
      });
    },
  };

  Drupal.behaviors.facetCheckboxes = {
    attach: function (context, settings) {
      $(".js-facets-checkbox-links label").on("click", function (event) {
        var $label = $(this);
        var $checkbox = $label.prev();
        var isChecked = $checkbox.attr("checked") === "checked";

        if (isChecked) {
          $checkbox.removeAttr("checked");
        } else {
          $checkbox.attr("checked", "checked");
        }
      });
    },
  };

  Drupal.behaviors.enableSelect2 = {
    attach: function (context, settings) {
      $(".form-item select", context).select2({
        width: '100%',
        placeholder: Drupal.t('Choose some options'),
        templateSelection: function (data) {
          if (data.selected) {
            return data.text;
          }
          return  Drupal.t('Choose some options');
        }
      });
    }
  }

  function scrollDown($groupedCards) {
    $("html, body").animate(
      {
        scrollTop: $groupedCards.offset().top + 2,
      },
      500
    );
  }

  if ($("#edit-keywords").val()) {
    $("#edit-keywords").addClass("input-has-value");
  }

  if ($("#edit-fulltext--2").val()) {
    $("#edit-fulltext--2").addClass("input-has-value");
  }

  if ($("#edit-fulltext").val()) {
    $("#edit-fulltext").addClass("input-has-value");
  }

  if ($("#edit-keywords--2").val()) {
    $("#edit-keywords--2").addClass("input-has-value");
  }

  $(".form-text").blur(function (event) {
    if ($(this).val() != "") {
      $(this).addClass("input-has-value");
    } else {
      $(this).removeClass("input-has-value");
    }
  });

  Drupal.behaviors.attachSearchBehavior = {
    attach: function (context, settings) {
      /* Handle search - homepage */

      var $siteHeader = $('.ecl-site-header-core', context);
      var $searchForm = $siteHeader.find('.ecl-form');

      $('<div class="toggle-search" />')
        .on('click', toggleSearchVisibility)
        .insertAfter($searchForm);

      function toggleSearchVisibility(event) {
        var $this = $(event.target);

        $this
          .closest('.ecl-site-header-core')
          .toggleClass('search-class');
      }
    },
  };

  /** mark empty-state for warning block on top of my messages */
  if($('.private-message-view-empty').length) {
    $('#block-privatemessagepagetextblock').addClass('empty-state');
  }
})(jQuery, Drupal, drupalSettings);
