(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.alerts = {
    attach: function attach(context, settings) {
      Drupal.alerts = Drupal.alerts || new AlertCollection(null, {
        'url': drupalSettings.alertScheduler.baseUrl,
        'autoRefresh': true,
        'refreshInterval': drupalSettings.alertScheduler.refreshInterval,
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
