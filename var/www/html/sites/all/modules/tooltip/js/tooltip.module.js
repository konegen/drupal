/**
 * @file
 * JavaScript code for the tooltip module for Drupal.
 *
 * This file is called tooltip.module.js to avoid name clashes with any
 * other modules or themes.
 */
jQuery(function ($) {
  "use strict";

  $(function () {
    // If tipsy is not loaded, fail silently.
    if (!$.fn.tipsy || !Drupal.settings.tooltip) {
      return;
    }

    $.each(Drupal.settings.tooltip, function (index, tip) {
      // Default options.
      tip.live = tip.live || true;

      // We can't provide the tip text as an argument directly, so we make a
      // callback for it instead. This is called a title callback.
      tip.title = function () {
        return tip.content;
      };

      $(tip.selector).tipsy(tip);
    });
  });
});
