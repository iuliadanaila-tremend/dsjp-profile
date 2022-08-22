(function ($, Drupal) {
  Drupal.behaviors.timeAgoDateFormatter = {
    attach: function (context, settings) {
      var $timeAgoWrappper = $('.time-ago-wrapper', context);

      if (!$timeAgoWrappper.length) {
        return;
      }

      $timeAgoWrappper.once('setTimeAgo').each(function () {
        var unixTimestamp =  $(this).attr('data-date');
        var newTime = timeAgo(new Date(unixTimestamp*1000));
        $(this).text(newTime);
      });
    }
  };

  function timeAgo(val) {
    val = 0 | (Date.now() - val) / 1000;
    var unit, length = { second: 60, minute: 60, hour: 24, day: 7, week: 4.35,
      month: 12, year: 10000 }, result;

    for (unit in length) {
      result = val % length[unit];
      if (!(val = 0 | val / length[unit]))
        return result + ' ' + (result-1 ? unit + 's' : unit);
    }
  }

})(jQuery, Drupal);
