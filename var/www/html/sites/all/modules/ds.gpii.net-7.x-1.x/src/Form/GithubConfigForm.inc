<?php

/**
 * @file
 * Administration form to enter personal access token.
 */

/**
 * Implements hook_form().
 *
 * Configuration form for Github authentication settings.
 */
function github_form() {
  $form = array();

  $form['token'] = array(
    '#type' => 'textfield',
    '#title' => t('Personal access token for authorization:'),
    '#default_value' => variable_get('github.token', ''),
    '#size' => 40,
    '#maxlength' => 40,
    '#description' => t("Generate a personal access token <a href='https://github.com/settings/tokens/new' target='_blank' title='Github Personal Access Token'>here</a>."),
    '#required' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Submit',
  );

  //$form['#submit'][] = "github_form_submit";

  return $form;
}

/**
 * Implements hook_form_validate().
 *
 * Validate the entered access token with Github API.
 */
function github_form_validate($form, &$form_state) {
  if (empty($form_state['values']['token'])) {
    form_set_error('token', t('Token can not be empty.'));
  }
  $response = drupal_http_request('https://api.github.com/user', ['headers' => ['Authorization' => "token {$form_state['values']['token']}"]]);
  if (!empty($response->error)) {
    form_set_error('token', t('Error: @error', array('@error' => $response->error)));
  }
  $data = $response->data;
  $json = json_decode($data, TRUE);
  switch ($response->code) {
    case 200:
      $form_state['values']['login'] = $json['login'];
      break;

    case 401:
      form_set_error('token', t('Error: @error', array('@error' => $json['message'])));
      break;

    default:
      form_set_error('token', t('HTTP Status Code: @error', array('@error' => $response->status_message)));
  }
}

/**
 * Implements hook_form_submit().
 *
 * Output greeting with name if authentication is successful.
 */
function github_form_submit($form, &$form_state) {
  watchdog('github', 'Saved new token');
  drupal_set_message(t('Hi @login', array('@login' => $form_state['values']['login'])));
  variable_set('github.token', $form_state['values']['token']);
  variable_set('github.login', $form_state['values']['login']);
}
