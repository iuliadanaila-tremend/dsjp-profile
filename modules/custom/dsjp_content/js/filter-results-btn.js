/**
 * @file
 * Facets open on button click for mobile
 */
jQuery(document).ready(function ($) {
  'use strict';
  if(!$('.view-dsj-search .view-empty').length){
      $('#block-dsj-filter-results-button').show();
  }

  $('.toggle-facet-filters').on('click', function (event) {
    event.preventDefault();
    $(this).toggleClass('is-open');
    $('#block-dsj-facets-list').toggleClass('is-open');
  });
});
