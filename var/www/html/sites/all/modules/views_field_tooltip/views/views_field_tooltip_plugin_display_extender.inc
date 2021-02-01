<?php

/**
 * @file
 */

/**
 * A display extender Views plugin for views_field_toolip.
 */
class views_field_tooltip_plugin_display_extender extends views_plugin_display_extender {

  /**
   * {@inheritdoc}
   */
  public function options_definition_alter(&$options) {
    $options['tooltips'] = array('default' => array(), 'unpack_translatable' => 'unpack_tooltips');
    $options['label'] = array('default' => array());
    $options['label2'] = array('default' => array());
    $options['field'] = array('default' => array());
  }

  /**
   * Unpack Translatable callback.
   */
  public function unpack_tooltips(&$translatable, $storage, $option, $definition, $parents, $keys = array()) {
    $tooltips = $storage[$option];
    if (!empty($tooltips)) {
      foreach ($tooltips as $field => $info) {
        // Left-over from previous version that only had label tooltips.
        if (is_string($info)) {
          $info = array('label_tooltip' => $info);
        }

        if (!empty($info['field_tooltip']['text'])) {
          $translation_keys = array_merge($keys, array($field, 'field_tooltip', 'text'));
          $translatable[] = array(
            'value' => $info['field_tooltip']['text'],
            'keys' => $translation_keys,
            'format' => NULL,
          );
        }
        if (!empty($info['label_tooltip']['text'])) {
          $translation_keys = array_merge($keys, array($field, 'label_tooltip', 'text'));
          $translatable[] = array(
            'value' => $info['label_tooltip']['text'],
            'keys' => $translation_keys,
            'format' => NULL,
          );
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function options_form(&$form, &$form_state) {
    if ($form_state['section'] != 'qtip') {
      return;
    }

    $library = variable_get('views_field_tooltip_library', 'qtip2');
    $info = views_field_tooltip_get_library_info($library);
    if (!empty($info)) {
      $form['label'] = array(
        '#type' => 'textarea',
        '#title' => t('Label #1 default settings'),
        '#description' => t('These settings apply to all label #1 tooltips and can be overridden in the field settings UI.'),
        '#default_value' => jsonpp($this->display->get_option('label')),
      );
      $form['label2'] = array(
        '#type' => 'textarea',
        '#title' => t('Label #2 default settings'),
        '#description' => t('These settings apply to all label #2 tooltips and can be overridden in the field settings UI.'),
        '#default_value' => jsonpp($this->display->get_option('label2')),
      );
      $qtip_field = $this->display->get_option('field');
      if (empty($qtip_field)) {
        $qtip_field = $this->display->get_option('qtip');
      }
      $form['field'] = array(
        '#type' => 'textarea',
        '#title' => t('Field default settings'),
        '#description' => t('These settings apply to all field tooltips and can be overridden in the field settings UI.') . ' ' . call_user_func($info['help callback']),
        '#default_value' => jsonpp($qtip_field),
      );
    }
    else {
      foreach (array('label', 'label2', 'field') as $qtip) {
        $form[$qtip] = array(
          '#type' => 'value',
          '#value' => jsonpp($this->display->get_option($qtip)),
        );
      }
      drupal_set_message(t('Tooltip library variable <code>views_field_tooltip_library</code> holds unknown value %library. Supported libraries are <em>qtip</em> and <em>qtip2</em>.', array('%library' => $library)), 'warning');
    }
    $form['#title'] = t('Tooltip style');
  }

  /**
   * {@inheritdoc}
   */
  public function options_validate(&$form, &$form_state) {
    if ($form_state['section'] != 'qtip') {
      return;
    }

    foreach (array('label', 'label2', 'field') as $qtip) {
      json_decode($form_state['values'][$qtip], TRUE);
      if (json_last_error() != JSON_ERROR_NONE) {
        form_set_error($qtip, t(json_last_error_msg()));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function options_submit(&$form, &$form_state) {
    if ($form_state['section'] != 'qtip') {
      return;
    }

    foreach (array('label', 'label2', 'field') as $qtip) {
      $settings = json_decode($form_state['values'][$qtip], TRUE);
      $this->display->set_option($qtip, $settings);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function options_summary(&$categories, &$options) {
    $options['qtip'] = array(
      'category' => 'other',
      'title' => t('Tooltip style'),
      'value' => t('Settings'),
      'desc' => t('Change the default tooltip style settings for this display.'),
    );
  }

}
