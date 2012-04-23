<?php

/**
 * @file
 */

function brukar_oauth_request() {
  require_once(drupal_get_path('module', 'brukar') . '/OAuth.php');

  $method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer(variable_get('brukar_consumer_key'), variable_get('brukar_consumer_secret'));
  $callback =  url($_GET['q'], array('absolute' => TRUE));

  $req = OAuthRequest::from_consumer_and_token($consumer, NULL, 'GET', variable_get('brukar_url') . 'server/oauth/request_token', array('oauth_callback' => $callback));
  $req->sign_request($method, $consumer, NULL);
  parse_str(trim(file_get_contents($req->to_url())), $token);

  if (count($token) > 0) {
    $_SESSION['auth_oauth'] = $token;
    drupal_goto(variable_get('brukar_url') . 'server/oauth/authorize?oauth_token=' . $token["oauth_token"]);
  }
  else {
    drupal_set_message(t('Unable to retrieve token for login.'), 'warning');
    drupal_goto('<front>');
  }
}

function brukar_oauth_callback() {
  require_once(drupal_get_path('module', 'brukar') . '/OAuth.php');

  $method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer(variable_get('brukar_consumer_key'), variable_get('brukar_consumer_secret'));

  if (isset($_SESSION['auth_oauth']) && $_SESSION['auth_oauth']['oauth_token'] == $_GET['oauth_token']) {
    $tmp = new OAuthToken($_SESSION["auth_oauth"]["oauth_token"], $_SESSION['auth_oauth']['oauth_token_secret']);

    $req = OAuthRequest::from_consumer_and_token($consumer, $tmp, "GET", variable_get('brukar_url') . 'server/oauth/access_token', array());
    $req->sign_request($method, $consumer, $tmp);
    parse_str(trim(file_get_contents($req->to_url())), $token);

    unset($_SESSION["auth_oauth"]);

    if (count($token) > 0) {
      $token = new OAuthToken($token["oauth_token"], $token["oauth_token_secret"]);
      $req = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", variable_get('brukar_url') . 'server/oauth/user', array());
      $req->sign_request($method, $consumer, $token);

      brukar_login((array) json_decode(trim(file_get_contents($req->to_url()))));
    }
  }

  drupal_set_message(t('Noe gikk feil under innlogging.'), 'warning');
  drupal_goto('<front>');
}

function brukar_login($data) {
  global $user;

  $edit = array(
    'name' => t(variable_get('brukar_name', '!name'), array(
      '!name' => $data['name'],
      '!sident' => substr($data['ident'], 0, 4),
      '!ident' => $data['ident'],
    )),
    'mail' => $data['mail'],
    'status' => 1,
    'data' => array('brukar' => $data),
  );

  if ($user->uid != 0) {
    user_save($user, $edit);
    user_set_authmaps($user, array('authname_brukar' => $data['ident']));
      drupal_goto('user');
  }

  $user = db_query('SELECT uid FROM {authmap} WHERE module = :module AND authname = :ident', array(':ident' => $data['ident'], ':module' => 'brukar'))->fetch();
  if ($user === FALSE) {
    $user = user_save(NULL, $edit);
    user_set_authmaps($user, array('authname_brukar' => $data['ident']));
  }
  else {
    $user = user_save(user_load($user->uid), $edit);
  }

  $form_state = (array) $user;
  user_login_submit(array(), $form_state);

  drupal_goto($_GET['q'] == variable_get('site_frontpage') ? '<front>' : $_GET['q']);
}
