<?php

require_once(drupal_get_path('module', 'brukar_server') . '/brukar_server.class.php');

function brukar_server_oauth_request_token() {
  $server = _brukar_server();
  $request = OAuthRequest::from_request();
  $token = $server->fetch_request_token(&$request);
  echo $token;
  exit();
}

function brukar_server_oauth_authorize() {
  global $user;

  if (isset($_SESSION['oauth_token'])) {
    $_GET['oauth_token'] = $_SESSION['oauth_token'];
    unset($_SESSION['oauth_token']);
  }

  if (isset($_GET['oauth_token'])) {
    $token = _lookup_token($_GET['oauth_token'], 'request');
  
    if ($token !== FALSE) {
      if ($user->uid != 0) {
        $token->uid = $user->uid;
        drupal_write_record('brukar_token', $token, 'id');
        drupal_goto($token->callback . '?oauth_token=' . $token->token_key);
      } else {
        $_SESSION['oauth_token'] = $_GET['oauth_token'];
      }
    }
  }

  drupal_goto('<front>');
}

function brukar_server_oauth_access_token() {
  $server = _brukar_server();
  $request = OAuthRequest::from_request();
  $token = $server->fetch_access_token(&$request);
  echo $token;
  exit();
}

function brukar_server_oauth_user() {
  $server = _brukar_server();
  $request = OAuthRequest::from_request();
  list($consumer, $token) = $server->verify_request($request);
  $user = user_load($token->uid);
  
  echo json_encode(array(
    'id' => $user->uid,
    'name' => $user->name,
    'mail' => $user->mail,  
  ));
  exit();
}
