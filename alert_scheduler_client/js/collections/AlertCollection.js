/**
 * @file
 * A Backbone Collection of Alerts.
 */
(function ($, Backbone) {
  /**
   * Backbone collection of Alerts.
   *
   * @constructor
   *
   * @augments Backbone.Collection
   */
  AlertCollection = Backbone.Collection.extend({

    model: AlertModel,
    url: drupalSettings.alertScheduler.baseUrl,

    initialize: function(models, options) {
      this.url = options.url;
      this.fetch();
      if (options.autoRefresh) {
        this.enableAutoRefresh(options.refreshInterval);
      }
      this.listenTo(this, 'add', this.addView);
    },

    addView: function(alert, collection, options) {
      $(document).find('*[data-alerts-container]').each(function(index, container) {
        var div = document.createElement('div');
        new AlertView({
          el: div,
          model: alert,
        });
        $(container).append($(div).addClass('alert alert-' + alert.getId()));
      });
    },

    enableAutoRefresh: function(interval) {
      var collection = this;
      setInterval(function() {
        collection.fetch();
      }, interval);
    },

  });
})(jQuery, Backbone);