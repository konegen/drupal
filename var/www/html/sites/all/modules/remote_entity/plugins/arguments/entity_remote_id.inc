<?php
/**
 * @file
 * Plugin to provide an argument handler for all entity remote ids.
 */

$plugin = array(
  'title' => t("Entity: Remote ID"),
  'description' => t('Creates an entity context from a remote ID argument.'),
  'context' => 'ctools_argument_entity_remote_id_context',
  'get child' => 'ctools_argument_entity_remote_id_get_child',
  'get children' => 'ctools_argument_entity_remote_id_get_children',
  'default' => array(
    'entity_remote_id' => '',
    'always_load_remote' => FALSE,
  ),
  'settings form' => 'ctools_argument_entity_remote_id_settings_form',
  'placeholder form' => 'ctools_argument_entity_remote_id_ctools_argument_placeholder',
);

function ctools_argument_entity_remote_id_get_child($plugin, $parent, $child) {
  $plugins = ctools_argument_entity_remote_id_get_children($plugin, $parent);
  return $plugins[$parent . ':' . $child];
}

function ctools_argument_entity_remote_id_get_children($original_plugin, $parent) {
  $plugins = array();
  foreach (remote_entity_get_remote_entity_types() as $entity_type => $entity) {
    $plugin = $original_plugin;
    $plugin['title'] = t('@entity: Remote ID', array('@entity' => $entity['label']));
    $plugin['keyword'] = $entity_type;
    $plugin['description'] = t('Creates @entity context from an remote ID argument.', array('@entity' => $entity_type));
    $plugin['name'] = $parent . ':' . $entity_type;
    $plugin_id = $parent . ':' . $entity_type;
    drupal_alter('ctools_entity_remote_context', $plugin, $entity, $plugin_id);
    $plugins[$plugin_id] = $plugin;
  }
  drupal_alter('ctools_entity_remote_contexts', $plugins);

  return $plugins;
}

/**
 * Discover if this argument gives us the entity we crave.
 */
function ctools_argument_entity_remote_id_context($arg = NULL, $conf = NULL, $empty = FALSE) {
  $entity_type = explode(':', $conf['name']);
  $entity_type = $entity_type[1];
  // If unset it wants a generic, unfilled context.
  if ($empty) {
    return ctools_context_create_empty('entity:' . $entity_type);
  }

  // We can accept either an entity object or a pure id.
  if (is_object($arg)) {
    return ctools_context_create('entity:' . $entity_type, $arg);
  }

  // Trim spaces and other garbage.
  $arg = trim($arg);
  if (empty($conf['always_load_remote'])) {
    $entity = remote_entity_load_by_remote_id($entity_type, array($arg));
  }
  else {
    $resource = clients_resource_get_for_component('remote_entity', $entity_type);
    $entity = $resource->remote_entity_load($arg);
  }
  if (empty($entity)) {
    return FALSE;
  }

  return ctools_context_create('entity:' . $entity_type, $entity);
}

function ctools_argument_entity_remote_id_settings_form(&$form, &$form_state, $conf) {
  $plugin = &$form_state['plugin'];

  $form['settings']['always_load_remote'] = array(
    '#title' => t('Always load from remote'),
    '#description' => t('If enabled local mirrors of the remote entity are ignored.'),
    '#type' => 'checkbox',
    '#default_value' => !empty($conf['always_load_remote']),
  );
  $form['settings']['#weight'] = 10;

  return $form;
}

function ctools_argument_entity_remote_id_ctools_argument_placeholder($conf) {
  $conf = array(
    '#title' => t('Enter remote ID of a @entity entity', array('@entity' => $conf['keyword'])),
    '#type' => 'textfield',
    '#maxlength' => 512,
    '#weight' => -10,
  );
  return $conf;
}
