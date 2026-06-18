(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.actstreamWall = {
    attach: function (context) {
      once('actstream-wall', '#actstream-wall', context).forEach(function (wall) {
        var settings = drupalSettings.actstream_wall || {};
        var refreshUrl = settings.refresh_url || '/actstream/wall/refresh';
        var interval = settings.refresh_interval || 30000;
        var lastTimestamp = settings.last_timestamp || 0;

        function poll() {
          $.getJSON(refreshUrl, { since: lastTimestamp }, function (data) {
            if (data.items && data.items.length) {
              data.items.forEach(function (item) {
                $(wall).prepend(item.html);
              });
              lastTimestamp = data.last_timestamp;

              // Remove the "empty" message if items arrive.
              $(wall).find('.actstream-wall__empty').remove();
            }
          });
        }

        setInterval(poll, interval);
      });
    }
  };

})(jQuery, Drupal, drupalSettings, once);
