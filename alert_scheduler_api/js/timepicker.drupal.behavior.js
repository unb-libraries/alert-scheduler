(function ($, Drupal) {
  Drupal.behaviors.timepicker = {
    attach: function attach(context, settings) {
      var widgetSettings = {
        'timeFormat': 'H:i',
        'step': 30,
      };

      var that = this;

      $('input[type="date"]', context).each(function(index, el) {
        var dateInput = $(el);
        if (dateInput.val() === '') {
          var today = that.formatDate(new Date());
          dateInput.val(today);
        }
      });

      $('input[type="time"]', context).each(function(index, el) {
        var timeInput = $(el);
        timeInput.attr('type', 'text');
        timeInput.attr('placeholder', 'hh:m');

        if (timeInput.val() === '') {
          var now = that.formatTime(new Date());
          if (timeInput.attr('name').indexOf('end_value') > 0) {
            timeInput.val('23:59');
          } else {
            timeInput.val(now);
          }
        } else {
          var datePartner = $('input[type="date"]', context).get(index);
          if (datePartner !== undefined) {
            var time = that.formatTime(new Date($(datePartner).val() + ' ' + timeInput.val()));
            timeInput.val(time);
          }
        }

        timeInput.timepicker(widgetSettings);
      });
    },

    formatDate: function(date) {
      var year = date.getFullYear();
      var month = date.getMonth() + 1;
      if (month < 10) {
        month = '0' + month;
      }
      var day = date.getDate();
      if (day < 10) {
        day = '0' + day;
      }
      return year + '-' + month + '-' + day;
    },

    formatTime: function(time) {
      var hours = time.getHours();
      if (hours < 10) {
        hours = '0' + hours;
      }
      var minutes = time.getMinutes();
      if (minutes < 10) {
        minutes = '0' + minutes;
      }
      return hours + ':' + minutes;
    }


  };
})(jQuery, Drupal);
