<?php

/**
 * @file
 */

function brukar_client_oauth_request() {
  require_once(drupal_get_path('module', 'brukar_common') . '/OAuth.php');

  $method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer(variable_get('brukar_consumer_key'), variable_get('brukar_consumer_secret'));

  // Better solution available?
  $query = $_GET;
  unset($query['q']);
  $callback =  url($_GET['q'], array('absolute' => TRUE, 'query' => $query));

  $req = OAuthRequest::from_consumer_and_token($consumer, NULL, 'GET', variable_get('brukar_url') . 'server/oauth/request_token', array('oauth_callback' => $callback));
  $req->sign_request($method, $consumer, NULL);
  parse_str(trim(file_get_contents($req->to_url())), $token);

  if (count($token) > 0) {
    $_SESSION['auth_oauth'] = $token;
    drupal_goto(variable_get('brukar_url') . 'server/oauth/authorize?oauth_token=' . $token['oauth_token']);
  }
  else {
    $debug_data = array(
        'request_uri' => request_uri(),
        'auth_oauth' =>  isset($_SESSION['auth_oauth']) ? $_SESSION['auth_oauth'] : 'no auth_oauth');
    watchdog(
        'brukar_client',
        'Unable to retrieve token for login.<br/>Debug data:<br/><pre>!debug_data</pre><br/>',
        array('!debug_data' =>  print_r($debug_data, TRUE) ),
        WATCHDOG_ERROR);

    drupal_set_message(t('Unable to retrieve token for login.'), 'warning');
    drupal_goto('<front>');
  }
}

function brukar_client_oauth_callback() {
  require_once(drupal_get_path('module', 'brukar_common') . '/OAuth.php');

  $method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer(variable_get('brukar_consumer_key'), variable_get('brukar_consumer_secret'));

  if (isset($_SESSION['auth_oauth']) && $_SESSION['auth_oauth']['oauth_token'] == $_GET['oauth_token']) {
    unset($_GET['oauth_token']);
    $tmp = new OAuthToken($_SESSION['auth_oauth']['oauth_token'], $_SESSION['auth_oauth']['oauth_token_secret']);

    $req = OAuthRequest::from_consumer_and_token($consumer, $tmp, 'GET', variable_get('brukar_url') . 'server/oauth/access_token', array());
    $req->sign_request($method, $consumer, $tmp);
    parse_str(trim(file_get_contents($req->to_url())), $token);

    unset($_SESSION['auth_oauth']);

    if (count($token) > 0) {
      $_SESSION['_brukar_access_token'] = array('token' => $token['oauth_token'], 'token_secret' => $token['oauth_token_secret']);
      $token = new OAuthToken($token['oauth_token'], $token['oauth_token_secret']);

      $req = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', variable_get('brukar_url') . 'server/oauth/user', array());
      $req->sign_request($method, $consumer, $token);

      brukar_client_login((array) json_decode(trim(file_get_contents($req->to_url()))));
    }
  }

  $debug_data = array(
      'cookie' => $_COOKIE,
      'request_uri' => request_uri(),
      'auth_oauth' =>  isset($_SESSION['auth_oauth']) ? $_SESSION['auth_oauth'] : 'no auth_oauth');
  watchdog(
      'brukar_client',
      'User login failed.<br/>Debug data:<br/><pre>!debug_data</pre><br/>',
      array('!debug_data' =>  print_r($debug_data, TRUE) ),
      WATCHDOG_ERROR);

  drupal_set_message(t('Noe gikk feil under innlogging.'), 'warning');
  drupal_goto('<front>');
}

function brukar_client_login($data) {
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

  $authmap_user = db_query('SELECT uid FROM {authmap} WHERE module = :module AND authname = :ident', array(':ident' => $data['id'], ':module' => 'brukar'))->fetch();
  if ($authmap_user === FALSE) {
    $provided = module_invoke_all('brukar_client_user', $edit);
    $user = !empty($provided) ? $provided[0] : user_save(NULL, $edit);
    user_set_authmaps($user, array('authname_brukar' => $data['id']));
  }
  else {
    $user = user_save(user_load($authmap_user->uid), $edit);
  }

  $form_state = (array) $user;
  user_login_submit(array(), $form_state);

  // Better solution available?
  $query = $_GET;
  unset($query['q']);
  drupal_goto($_GET['q'] == variable_get('site_frontpage') ? '<front>' : url($_GET['q'], array('absolute' => TRUE, 'query' => $query)));
}
