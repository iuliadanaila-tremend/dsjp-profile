/**
 * @file
 * Contains user_online_status.js.
 */

(function (Drupal, drupalSettings) {
  Drupal.behaviors.UserOnlineStatus = {
    attach: function (context) {

      // Collect all UIDs on page.
      var statusWrapperArray = Array.prototype.slice.call(context.querySelectorAll('.user-online-status'));
      var uids = {};
      statusWrapperArray.forEach(function (el) {
        var uid = el.getAttribute('data-user-online-status-uid');
        uids[uid] = uid;
      });
      // Convert to an array.
      uids = Object.values(uids);

      /**
       * Print a user's online status.
       *
       * @param {string} uid
       * @param {string} status
       */
      var printOnlineStatus = function (uid, status) {

        var statusWrapperElementArray = Array.prototype.slice.call(context.querySelectorAll('.user-online-status[data-user-online-status-uid="' + uid + '"]'));
        statusWrapperElementArray.forEach(function (el) {
          var responseElement = el.querySelector('.response');
          setStatusClasses(el, status);

          // Print status.
          switch (status) {
            case 'online':
              responseElement.innerHTML = Drupal.t('online');
              break;

            case 'absent':
              responseElement.innerHTML = Drupal.t('absent');
              break;

            case 'offline':
              responseElement.innerHTML = Drupal.t('offline');
              break;

            default:
              console.log('user_online_status: Status not found.');
          }
        });
      };

      // Fetch status.
      if (uids.length) {
        var request = new XMLHttpRequest();
        request.open('POST', '/online-statuses', true);

        request.onload = function () {
          if (request.status >= 200 && request.status < 400) {
            var response = JSON.parse(request.responseText);
            for (var uid in response) {
              printOnlineStatus(uid, response[uid]);
            }
          }
          else {
            console.log('user_online_status: Error fetching status.');
          }
        };
        request.onerror = function () {
          // There was a connection error of some sort.
          console.log('user_online_status: Could not fetch status.');
        };

        var data = {
          uids: uids,
        };
        request.send(JSON.stringify(data));
      }
    }
  };

  /**
   * Set custom status classes.
   *
   * @param {object} el
   * @param {string} currentStatus
   */
  function setStatusClasses(el, currentStatus) {
    var statusClasses = drupalSettings.user_online_status.classes;
    var statusElement = el.querySelector('.status');

    for (var statusClass in statusClasses) {
      if (statusElement.classList.contains(status)) {
        statusElement.classList.remove(statusClass, ...(statusClasses[statusClass].split(' ')));
      }
    }
    statusElement.classList.add(currentStatus);
    if (statusClasses[currentStatus]) {
      statusElement.classList.add(...(statusClasses[currentStatus].split(' ')));
    }
  }
  
})(Drupal, drupalSettings);
