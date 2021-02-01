<?php

/**
 * @file
 * Describes hooks provided by the Views Field Tooltip module.
 */

/**
 * Provide information about supported tooltip libraries.
 *
 * @return array
 *   Associative array of entries describing each tooltip library:
 *   - key: The machine name of the library.
 *   - name: The human name of the library
 *   - "help callback": A function that returns a string describing typical
 *     settings for the given tooltip library.
 *   - "needs local file": A flag whether the admin settings should prompt for
 *     the path of a local JavaScript file for this library.
 *   - attached: Array of JS and CSS with the same format as Form API attribute
 *     "#attached".
 *
 * @see views_field_tooltip_views_field_tooltip_library_info()
 * @see views_field_tooltip__qtip_get_help()
 * @see https://api.drupal.org/api/drupal/developer!topics!forms_api_reference.html/7#attached
 * @see hook_views_field_tooltip_library_info_alter()
 */
function hook_views_field_tooltip_library_info() {
  $path = drupal_get_path('module', 'views_field_tooltip');
  return array(
    'qtip' => array(
      'name' => t('qTip'),
      'help callback' => 'views_field_tooltip__qtip_get_help',
      'needs local file' => TRUE,
      'attached' => array(
        'js' => array(
          $path . '/js/views_field_tooltip.qtip.js' => 'file',
        ),
      ),
    ),
    'qtip2' => array(
      'name' => t('qTip2'),
      'help callback' => 'views_field_tooltip__qtip2_get_help',
      'needs local file' => FALSE,
      'attached' => array(
        'js' => array(
          '//cdn.jsdelivr.net/qtip2/2.2.0/jquery.qtip.min.js' => 'external',
          $path . '/js/views_field_tooltip.qtip2.js' => 'file',
        ),
        'css' => array(
          '//cdn.jsdelivr.net/qtip2/2.2.0/jquery.qtip.min.css' => 'external',
          // CSS fixes for iframe tooltips.
          '.html .qtip { max-width: none; }
           .html .qtip-content { height: 100%; }
          ' => 'inline',
        ),
      ),
    ),
  );
}

/**
 * Alter information about tooltip libraries.
 *
 * @param array $info
 *   Reference to library info array as described for
 *   hook_views_field_tooltip_library_info().
 *
 * @see hook_views_field_tooltip_library_info()
 */
function hook_views_field_tooltip_library_info_alter(array &$info) {
}

/**
 * Alter the tooltip structures before rendering.
 *
 * @param object $data
 *   Object containing references to tooltip context:
 *   - view: the view being rendered
 *   - tooltips: the tooltip settings for the current display
 *   - labels: the labels structure that will be sent to the front-end
 *   - fields: the fields structure that will be sent to the front-end
 */
function hook_views_field_tooltip_pre_render_alter($data) {
  if ($data->view->style_plugin instanceof views_matrix_plugin_style_matrix) {
    $xfield = $data->view->style_plugin->options['xconfig']['field'];
    $xfield_css = views_clean_css_identifier($xfield);
    $data->view->style_plugin->options['xconfig']['class'] .= " views-field-{$xfield_css}";
  }
}
