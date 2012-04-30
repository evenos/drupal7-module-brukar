<?php

/**
 * @file
 */

function brukar_admin($form, &$form_state = array()) {
  $form['server'] = array(
    '#type' => 'fieldset',
    '#title' => 'Server',
    '#collapsible' => variable_get('brukar_url', '') != '',
    '#collapsed' => variable_get('brukar_url', '') != '',
  );
  $form['server']['brukar_url'] = array(
    '#type' => 'textfield',
    '#title' => 'Adresse',
    '#default_value' => variable_get('brukar_url', ''),
  );
  $form['server']['brukar_consumer_key'] = array(
    '#type' => 'textfield',
    '#title' => 'Consumer key',
    '#default_value' => variable_get('brukar_consumer_key', ''),
  );
  $form['server']['brukar_consumer_secret'] = array(
    '#type' => 'textfield',
    '#title' => 'Consumer secret',
    '#default_value' => variable_get('brukar_consumer_secret', ''),
  );
  $form['behavior'] = array(
    '#type' => 'fieldset',
    '#title' => 'Behavior',
  );
  $form['behavior']['brukar_keyword'] = array(
    '#type' => 'textfield',
    '#title' => 'Keyword',
    '#default_value' => variable_get('brukar_keyword', 'brukar'),
  );
  $form['behavior']['brukar_name'] = array(
    '#type' => 'select',
    '#title' => 'Name in database',
    '#options' => array(
      '!name' => '[Name]',
      '!name (!sident)' => '[Name] ([Short ident])',
      '!ident' => '[Ident]',
    ),
    '#default_value' => variable_get('brukar_name', '!name'),
  );
  $form['behavior']['brukar_forced'] = array(
    '#type' => 'radios',
    '#title' => 'Forced login',
    '#options' => array('Nei', 'Ja'),
    '#default_value' => variable_get('brukar_forced', '0'),
  );
  $form['behavior']['brukar_hidden'] = array(
    '#type' => 'radios',
    '#title' => 'Hidden login',
    '#options' => array('Nei', 'Ja'),
    '#default_value' => variable_get('brukar_hidden', '0'),
  );
  $form['behavior']['brukar_dup'] = array(
    '#type' => 'radios',
    '#title' => 'Disable username/password',
    '#options' => array('Nei', 'Ja'),
    '#default_value' => variable_get('brukar_dup', '0'),
  );
  
  return system_settings_form($form);
}

function brukar_admin_validate($form, &$form_state) {
  require_once(drupal_get_path('module', 'brukar_common') . '/OAuth.php');

  $method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer($form_state['values']['brukar_consumer_key'], $form_state['values']['brukar_consumer_secret']);
  $callback =  url($_GET['q'], array('absolute' => TRUE));

  $req = OAuthRequest::from_consumer_and_token($consumer, NULL, 'GET', $form_state['values']['brukar_url'] . 'server/oauth/request_token', array('oauth_callback' => $callback));
  $req->sign_request($method, $consumer, NULL);
  parse_str(trim(@file_get_contents($req->to_url())), $token);

  if (count($token) == 0) {
    form_set_error('server', t('Invalid settings.'));
  }
}

function brukar_admin_submit($form, &$form_state) {
  menu_rebuild();
}