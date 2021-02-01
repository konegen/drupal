(function ($) {

  Drupal.behaviors.viewsFieldTooltipAjax = {

    attach: function(context) {
      $('a', context).not('.views-field-tooltip-skip').attr('target', '_parent');
    }
  }

})(jQuery);
