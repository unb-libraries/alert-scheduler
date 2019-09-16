/**
 * @file
 * A Backbone view for Alerts.
 */

(function ($, Backbone) {

  AlertView = Backbone.View.extend({
    /**
     * Backbone view for Alerts.
     *
     * @constructs
     *
     * @augments Backbone.View
     */
    initialize: function initialize() {
      this.listenTo(this.model, 'change', this.render);
      this.listenTo(this.model, 'remove', this.remove);
      this.render();
    },

    render: function render() {
      this.$el.html(AlertTemplate({
        title: this.model.getTitle(),
        message: this.model.getMessage(),
      }));

      if (this.model.isVisible()) {
        this.show();
      } else {
        this.hide();
      }
    },

    show: function() {
      this.$el.css('display', 'inherit');
    },

    hide: function() {
      this.$el.css('display', 'none');
    }

  });
})(jQuery, Backbone);