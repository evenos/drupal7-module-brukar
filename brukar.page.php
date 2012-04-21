<?php

function brukar_admin() {
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

  return system_settings_form($form);
}

function brukar_oauth_request() {
  $method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer(variable_get('brukar_consumer_key'), variable_get('brukar_consumer_secret'));
  $callback =  url($_GET['q'], array('absolute' => TRUE));

  $req = OAuthRequest::from_consumer_and_token($consumer, NULL, 'GET', variable_get('brukar_url') . 'server/oauth/request_token', array('oauth_callback' => $callback));
  $req->sign_request($method, $consumer, NULL);
  parse_str(trim(file_get_contents($req->to_url())), $token);
    
  $_SESSION['auth_oauth'] = $token;
  drupal_goto(variable_get('brukar_url') . 'server/oauth/authorize?oauth_token='.$token["oauth_token"]);
}

function brukar_oauth_callback() {
  $method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer(variable_get('brukar_consumer_key'), variable_get('brukar_consumer_secret'));

  if (isset($_SESSION['auth_oauth']) && $_SESSION['auth_oauth']['oauth_token'] == $_GET['oauth_token']) {
    $tmp = new OAuthToken($_SESSION["auth_oauth"]["oauth_token"], $_SESSION['auth_oauth']['oauth_token_secret']);

    $req = OAuthRequest::from_consumer_and_token($consumer, $tmp, "GET", variable_get('brukar_url') . 'server/oauth/access_token', array());
    $req->sign_request($method, $consumer, $tmp);
    parse_str(trim(file_get_contents($req->to_url())), $token);

    unset($_SESSION["auth_oauth"]);
    
    $token = new OAuthToken($token["oauth_token"], $token["oauth_token_secret"]);
    $req = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", variable_get('brukar_url') . 'server/oauth/user', array());
    $req->sign_request($method, $consumer, $token);

    brukar_login((array) json_decode(trim(file_get_contents($req->to_url()))));
  }
  
  drupal_set_message('Det gikk feil under innlogging med OAuth. Prøv gjerne igjen.', 'warning');
  drupal_goto('<front>');
}

function brukar_login($data) {
  global $user;

  $edit = array(
    'name' => t(variable_get('brukar_name', '!name'), array(
      '!name' => $data['name'],
      '!sident' => substr($data['id'], 0, 4),
      '!ident' => $data['id'],
    )),
    'mail' => $data['mail'],
    'status' => 1,
    'data' => array('brukar' => $data),
  );

  if ($user->uid != 0) {
    user_save($user, $edit);
    user_set_authmaps($user, array('authname_brukar' => $data['id']));
      drupal_goto('user');
  }

  $user = db_query('SELECT uid FROM {authmap} WHERE module = :module AND authname = :ident', array(':ident' => $data['id'], ':module' => 'brukar'))->fetch();
  if ($user === false) {
    $user = user_save(null, $edit);
    user_set_authmaps($user, array('authname_brukar' => $data['id']));
  } else {
    $user = user_save(user_load($user->uid), $edit);
  }

  $form_state = (array) $user;
  user_login_submit(array(), $form_state);

  drupal_goto($_GET['q'] == variable_get('site_frontpage') ? '<front>' : $_GET['q']);
}
