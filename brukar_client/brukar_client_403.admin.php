<?php


function brukar_client_403_admin() {
  $page['variables'] = drupal_get_form('brukar_client_403_admin_values');
  
  $page['rules'] = array(
    '#theme' => 'table',
    '#header' => array('Token', 'URL'),
    '#rows' => array(),
  );
  foreach (db_select('brukar_client_403', 'm')->fields('m')->execute() as $message)
    $page['rules']['#rows'][] = array(
      l($message->token, 'admin/config/people/brukar/403/' . $message->id),
      l('brukar/403/' . $message->token, 'brukar/403/' . $message->token),
    );
  
  $page['create'] = drupal_get_form('brukar_client_403_admin_form');

  return $page;
}

function brukar_client_403_admin_form($form, &$form_state, $rule = null) {
  if (!is_null($rule))
    $rule = db_select('brukar_client_403', 'c')->fields('c')->condition('id', $rule)->execute()->fetch();
  
  $form['id'] = array(
    '#type' => 'value',
    '#value' => is_null($rule) ? '' : $rule->id,
  );
  
  $form['default'] = array(
    '#type' => 'fieldset',
    '#title' => is_null($rule) ? t('Create message') : t('Update message'),
    '#collapsible' => is_null($rule),
    '#collapsed' => is_null($rule),
  );
  $form['default']['token'] = array(
    '#type' => 'machine_name',
    '#title' => 'Token',
    '#default_value' => is_null($rule) ? '' : $rule->token,
    '#machine_name' => array(
      'exists' => 'brukar_client_403_admin_form_machine_name',
    ),
  );
  $form['default']['message_anonymous'] = array(
    '#type' => 'textarea',
    '#title' => t('Message for anonymous users'),
    '#default_value' => is_null($rule) ? '' : $rule->message_anonymous,
  );
  $form['default']['message_authenticated'] = array(
    '#type' => 'textarea',
    '#title' => t('Message for authenticated users'),
    '#default_value' => is_null($rule) ? '' : $rule->message_authenticated,
  );
  $form['default']['submit'] = array(
    '#type' => 'submit',
    '#value' => is_null($rule) ? t('Create') : t('Update'),
  );
  
  return $form;
}

function brukar_client_403_admin_form_submit($form, &$form_state) {
  drupal_write_record('brukar_client_403', $form_state['values'], $form_state['values']['id'] == '' ? array() : 'id');
  drupal_set_message($form_state['values']['id'] == '' ? t('New rule created.') : t('Rule updated.'));
  drupal_goto('admin/config/people/brukar/403');
}

function brukar_client_403_admin_form_machine_name($machine_name) {
  return false;
}

function brukar_client_403_admin_values($form, &$form_state) {
  $form['default'] = array(
      '#type' => 'fieldset',
      '#title' => t('Default message'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
  );
  $form['default']['message_anonymous'] = array(
      '#type' => 'textarea',
      '#title' => t('Message for anonymous users'),
      '#default_value' => variable_get('brukar_client_403_anon', ''),
  );
  $form['default']['message_authenticated'] = array(
      '#type' => 'textarea',
      '#title' => t('Message for authenticated users'),
      '#default_value' => variable_get('brukar_client_403_auth', ''),
  );
  $form['default']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save settings'),
  );

  return $form;
}

function brukar_client_403_admin_values_submit($form, &$form_state) {
  variable_set('brukar_client_403_anon', $form_state['values']['message_anonymous']);
  variable_set('brukar_client_403_auth', $form_state['values']['message_authenticated']);
  drupal_set_message(t('Settings are saved.'));
}