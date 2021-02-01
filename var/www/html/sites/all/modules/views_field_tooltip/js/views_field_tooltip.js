(function ($) {

  Drupal.behaviors.viewsFieldTooltip = {
    attach: function(context) {
      // TODO: the qtip() function is sometimes not found on preview - figure out why.
      if (typeof $.fn.qtip === 'undefined') return;

      // Expand tokens found in qtip settings.
      var expand = function(tokens, qtip, skip_api) {
        // Deep clone the settings object to avoid modifying the original.
        var target = $.extend(true, {}, qtip);

        // Add API callbacks to hide the tooltip in case of "true AJAX" mode
        // before the actual contents have been loaded.
        if (!skip_api && typeof qtip.content === 'object' && qtip.content.url) {
          target.api = {};
          target.api.beforeShow = function () {
            if (!this.hasContent) {
              this.showCancelled = true;
              return false;
            }
            this.showCancelled = false;
          };
          target.api.beforeContentUpdate = function (content) {
            this.hasContent = content.trim().length > 0;
          };
          target.api.onContentUpdate = function () {
            if (this.hasContent && this.showCancelled) {
              this.show();
            }
          };
        }

        // Preprocess qTip option values.
        for (var prop in target) {
          if (target[prop] !== null && typeof tokens[target[prop]] !== 'undefined') {
            target[prop] = tokens[target[prop]];
          }
          else if (typeof target[prop].match === 'function' && target[prop].match(/^\$\(.*?\)$/)) {
            target[prop] = eval(target[prop]);
          }
          else if (typeof target[prop] === 'object') {
            target[prop] = expand(tokens, target[prop], true);
          }
        }
        return target;
      };

      // Loop on tooltip settings for all views and displays:
      // - insert the tooltip theme into the DOM
      // - activate the tooltip plugin
      $.each(Drupal.settings.viewsFieldTooltip, function(view, displays) {
        $.each(displays, function(display, settings) {
          var $display = $('.view-id-' + view + '.view-display-id-' + display, context);

          // Set the label tooltips.
          $.each(settings.labels, function(field, tooltips) {
            $.each(tooltips, function(key, tooltip) {
              var $cell = $('.views-' + key + '-tooltip-field-' + field, $display);
              $cell
                .filter('tbody *, thead tr:first-child *')
                .once('views-'+key+'-tooltip')
                .append(tooltip.theme)
                .children('.views-'+key+'-tooltip-icon')
                .each(function() {
                  $(this).qtip(expand({ "$target": $(this) }, tooltip.qtip));
                });
              if (Drupal.tableHeader && $cell.closest('table.sticky-enabled').length) {
                $(window).triggerHandler('resize.drupal-tableheader');
              }
            });
          });

          $.each(settings.fields, function(field, field_data) {
            if (field_data.single_setting) {
              $('.views-field-tooltip-field-' + field, $display).each(function() {
                var $cell = $(this);
                var qtip = $.extend({}, field_data.qtip);
                qtip.content = views_field_tooltip_replace_tokens(qtip.content, field_data, $cell, settings);
                var theme = views_field_tooltip_replace_tokens(field_data.theme, field_data, $cell, settings);
                $cell
                  .once('views-field-tooltip')
                  .append(theme)
                  .children('.views-field-tooltip-icon')
                  .each(function() {
                    $(this).qtip(expand({"$target": $(this)}, qtip));
                  });
                if (Drupal.tableHeader && $cell.closest('table.sticky-enabled').length) {
                  $(window).triggerHandler('resize.drupal-tableheader');
                }
              });
            }
            else {
              $.each(field_data, function(row, tooltip) {
                var $row, $cell;
                // Special cases based on style plugin.
                // TODO: Allow extensibility instead of special cases.
                if (settings.style_type == 'flipped_table') {
                  $row = $('tr.views-field-' + field, $display);
                  $cell = $('.views-field-tooltip-field-' + field + ':eq(' + row + ')', $row);
                }
                else if (settings.style_type == 'matrix') {
                  $cell = $('.views-field-tooltip-field-' + field + '.views-field-tooltip-row-' + row, $display);
                }
                else {
                  $row = $('.views-field-tooltip-row:eq(' + row + ')', $display);
                  $cell = $('.views-field-tooltip-field-' + field, $row);
                }
                $cell
                  .once('views-field-tooltip')
                  .append(tooltip.theme)
                  .children('.views-field-tooltip-icon')
                  .each(function() {
                    $(this).qtip(expand({"$target": $(this)}, tooltip.qtip));
                  });
                if (Drupal.tableHeader && $cell.closest('table.sticky-enabled').length) {
                  $(window).triggerHandler('resize.drupal-tableheader');
                }
              });
            }
          });
        });
      });

      /**
       * Replaces tokens in the given value, according to the tooltip settings.
       */
      function views_field_tooltip_replace_tokens(value, tooltip, $cell, settings) {
        var replace = function(search, replace, string) {
          string = string.split(search).join(replace);
          // Tokens in AJAX URLs will be URL-encoded, so we need to check for
          // that variant of the token, too.
          search = encodeURIComponent(search);
          replace = encodeURIComponent(replace);
          string = string.split(search).join(replace);
          return string;
        };
        if (tooltip.self_token) {
          value = replace(tooltip.self_token, $cell.html().trim(), value);
        }
        if (tooltip.row_token) {
          var row;
          if (settings.style_type == 'flipped_table') {
            row = $cell.index();
          }
          else if (settings.style_type == 'matrix') {
            row = $cell.attr('class').match(/\.views-field-tooltip-row-([0-9]+)/);
            row = row ? row[1] : '';
          }
          else {
            row = $cell.closest('.views-field-tooltip-row').index();
          }
          value = replace(tooltip.row_token, row, value);
        }
        return value;
      }
    }
  };

})(jQuery);
