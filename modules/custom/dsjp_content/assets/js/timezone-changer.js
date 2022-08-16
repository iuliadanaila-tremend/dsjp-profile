(function ($) {

  Drupal.behaviors.timezoneChanger = {
    attach: function (context, settings) {
      // Get current user timezone.
      var userTimezone = moment.tz.guess();
      // Iterate over all events on this page.
      $.each(settings.dsjp_content.eventTimezone, function (nid, value) {
        // Get the article tag with the id = nid
        let $element = $('article[data-history-node-id="' + nid + '"');
        // Convert the timestamp to miliseconds.
        let eventStart = value.startTimestamp * 1000;
        let eventStartDate = new Date(eventStart);
        // Get the timezone of the node.
        let timezone = value.timezone;
        // If the node is in another timezone than the current user we need to change the date.
        if (timezone !== userTimezone) {
          let eventStartTz = moment(eventStartDate).tz(timezone, true);
          let format = 'D MMMM YYYY HH:mm A';
          let newStartDate = eventStartTz.tz(userTimezone).format(format);
          let newStartDateShort = eventStartTz.tz(userTimezone).format('D MMMM YYYY');

          let timezoneFormat = eventStartTz.tz(userTimezone).format('ZZ');
          if ($element.hasClass('node--full')) {
            // Get the date element.
            let $dateElement = $element.find('.event-entry.event-time');
            if (typeof $dateElement !== 'undefined') {
              // Buil the event end date in the new timezone
              let eventEnd = value.endTimestamp * 1000;
              let eventEndDate = new Date(eventEnd);
              let eventEndTz = moment(eventEndDate).tz(timezone, true);
              let newEndDate = eventEndTz.tz(userTimezone).format(format);
              let newEndDateShort = eventEndTz.tz(userTimezone).format('D MMMM YYYY');
              // Set the new date field value.
              if (newStartDate == newEndDate) {
                $dateElement.text(newStartDate + ' GMT' + timezoneFormat);
              } else if(newEndDateShort == newStartDateShort){
                $dateElement.text(newStartDate + ' - ' . eventEndTz.tz(userTimezone).format('HH:mm A') + ' GMT' + timezoneFormat);
              } else{
                $dateElement.text(newStartDate + ' GMT' + timezoneFormat + ' - ' + newEndDate + ' GMT' + timezoneFormat);
              }
            }
          }
          else if($element.is('.node--listing, .node--teaser')) {
            // Use a shorter format.
            let shortFormat = 'D MMMM YYYY HH:mm A';
            let newStartDate = eventStartTz.tz(userTimezone).format(shortFormat);
            // Get the date element.
            let $dateElement = $element.find('.field--name-field-dsj-date time:first-child');
            if (typeof $dateElement !== 'undefined') {
              $dateElement.text(newStartDate + ' GMT' + timezoneFormat);
            }
          }
        }
      });
    }
  }

})(jQuery);
