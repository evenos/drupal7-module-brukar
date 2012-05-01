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

