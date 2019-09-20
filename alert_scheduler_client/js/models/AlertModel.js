/**
 * @file
 * A Backbone Model for Alerts.
 */
(function ($, Backbone) {
  /**
   * Backbone model for Alerts.
   *
   * @constructor
   *
   * @augments Backbone.Model
   */
  AlertModel = Backbone.Model.extend({
    /**
     * @type {object}
     *
     * @prop id
     * @prop title
     * @prop message
     * @prop interval
     */
    defaults: {
      id: "",
      title: "",
      message: "",
      isVisible: false,
      interval: {},
    },

    getId: function() {
      return this.get('id');
    },

    getTitle: function() {
      return this.get('title');
    },

    getMessage: function() {
      return this.get('message');
    },

    getInterval: function() {
      var interval = this.get('interval');
      return {
        from: new Date(interval.from),
        to: new Date(interval.to),
      };
    },

    isVisible: function() {
      var now = new Date();
      var interval = this.getInterval();
      return now >= interval.from && now <= interval.to;
    },

  });
})(jQuery, Backbone);