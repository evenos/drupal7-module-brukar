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
  if (!isset($_GET['oauth_token']))
    drupal_goto('<front>');
  
  global $user;

  $token = db_select('brukar_token', 't')->fields('t')->condition('token_key', $_GET['oauth_token'], '=')->execute()->fetch();

  if ($user->uid != 0) {
    $token->uid = $user->uid;
    drupal_write_record('brukar_token', $token, 'id');
    drupal_goto($token->callback . '?oauth_token=' . $token->token_key);
  } else {
    
  }
  
  return '';
}

function brukar_server_oauth_access_token() {
	$server = _brukar_server();
  $request = OAuthRequest::from_request();
	$token = $server->fetch_access_token(&$request);
	echo $token;
	exit();
}

function brukar_server_oauth_user() {
  return '';
}

