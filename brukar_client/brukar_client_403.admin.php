<?php


function brukar_client_403_admin() {
  $page['variables'] = drupal_get_form('brukar_client_403_admin_form');
  
  $page['rules'] = array(
    '#theme' => 'table',
    '#header' => array('Token', 'URL'),
    '#rows' => array(),
  );
  foreach (db_select('brukar_client_403', 'm')->fields('m')->execute() as $message)
    $page['rules']['#rows'][] = array(
      $message->token,
      'brukar/403/' . $message->token,
    );
  
  $page['create'] = drupal_get_form('brukar_client_403_admin_form', TRUE);

  return $page;
}

function brukar_client_403_admin_form($form, &$form_state, $rule = FALSE) {
	$form['rule'] = array(
	  '#type' => 'hidden',
	  '#value' => $rule ? 'new' : 'values',
	);
	
	$form['default'] = array(
	  '#type' => 'fieldset',
	  '#title' => $rule ? t('Create message') : t('Default message'),
	  '#collapsible' => TRUE,
	  '#collapsed' => TRUE,
	);
	
	if ($rule)
	  $form['default']['token'] = array(
	    '#type' => 'machine_name',
	    '#title' => 'Token',
	    '#machine_name' => array(
	      'exists' => 'function',
	    ),
	  );
	
  $form['default']['message_anonymous'] = array(
    '#type' => 'textarea',
    '#title' => t('Message for anonymous users'),
    '#default_value' => $rule ? '' : variable_get('brukar_client_403_anon', ''),
  );
  $form['default']['message_authenticated'] = array(
    '#type' => 'textarea',
    '#title' => t('Message for authenticated users'),
    '#default_value' => $rule ? '' : variable_get('brukar_client_403_auth', ''),
  );
  $form['default']['submit'] = array(
    '#type' => 'submit',
    '#value' => $rule ? t('Create') : t('Save settings'),
  );
  
  return $form;
}

function brukar_client_403_admin_form_submit($form, &$form_state) {
  if ($form_state['values']['rule'] == -1) {
    drupal_write_record($form_state['values']);
    drupal_set_message(t('New rule created.'));
  }
  elseif ($form_state['values']['rule'] == 0) {
    variable_set('brukar_client_403_anon', $form_state['values']['message_anonymous']);
    variable_set('brukar_client_403_auth', $form_state['values']['message_authenticated']);
    drupal_set_message(t('Settings are saved.'));
  }
}