<?php

require_once(drupal_get_path('module', 'brukar_common') . '/OAuth.php');

function _brukar_server() {
  $server = new OAuthServer(new BrukarServerDataStore());
  $server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
  return $server;
}

function _lookup_consumer($consumer_key) {
  return db_select('brukar_application', 'a')
    ->fields('a')
    ->condition('consumer_key', $consumer_key, '=')
    ->execute()
    ->fetch();
}

function _lookup_token($token_key, $type) {
  return db_select('brukar_token', 't')
    ->fields('t')
    ->condition('token_key', $token_key, '=')
    ->condition('type', $type, '=')
    ->execute()
    ->fetch();
}

class BrukarServerToken extends OAuthToken {
  public $id = null;
  public $uid = null;
  
  function __construct($key, $secret, $id = NULL, $uid = NULL) {
    $this->id = $id;
    $this->uid = $uid;
    parent::__construct($key, $secret);
  }
}

class BrukarServerConsumer extends OAuthConsumer {
  public $id = null;
  
    function __construct($key, $secret, $callback_url=NULL, $id = NULL) {
    $this->id = $id;
  	parent::__construct($key, $secret, $callback_url);
  }
}

/**
 * Implementing data store for OAuth server.
 */
class BrukarServerDataStore extends OAuthDataStore {
  function lookup_consumer($consumer_key) {
    $app = _lookup_consumer($consumer_key);
    if ($app === false)
      return null;
    
    return new BrukarServerConsumer($app->consumer_key, $app->consumer_secret, NULL, $app->id);
  }
  
  function lookup_token($consumer, $token_type, $token) {
    $token = _lookup_token($token, $token_type);
    if ($token_type == 'access') {
      $record = array(
        'id' => $token->id,
        'last_used' => time(),
      );
      drupal_write_record('brukar_token', $record, 'id');
    }
    return new BrukarServerToken($token->token_key, $token->token_secret, $token->id, $token->uid);
  }

  function lookup_nonce($consumer, $token, $nonce, $timestamp) {
    // TODO implement me
  }

  function new_request_token($consumer, $callback = null) {
    $token = new BrukarServerToken(brukar_common_uuid(), brukar_common_uuid());
    $record = array(
      'application_id' => $consumer->id,
      'type' => 'request',
      'token_key' => $token->key,
      'token_secret' => $token->secret,
      'callback' => $callback,
      'created' => time(),
    );
    db_insert('brukar_token')->fields($record)->execute();
    // drupal_write_record('brukar_token', $record);
    return $token;
  }

  function new_access_token($token, $consumer, $verifier = null) {
    // db_query('DELETE FROM {auth_token} WHERE `id` = :id', array(':id' => $token->id))->execute();

    $aTokenD = db_query('SELECT * FROM {brukar_token} WHERE `type` = :type AND uid = :uid AND application_id = :aid', array(':uid' => $token->uid, ':aid' => $consumer->id, ':type' => 'access'))->fetch();

    if ($aTokenD === false) {
	    $aToken = new BrukarServerToken(brukar_common_uuid(), brukar_common_uuid());
	    $record = array(
	      'application_id' => $consumer->id,
	      'uid' => $token->uid,
	      'type' => 'access',
	      'token_key' => $aToken->key,
	      'token_secret' => $aToken->secret,
	      'created' => time(),
	    );
	    db_insert('brukar_token')->fields($record)->execute();
	    // drupal_write_record('brukar_token', $record);
    } else {
      $aToken = new BrukarServerToken($aTokenD->token_key, $aTokenD->token_secret, $aTokenD->id, $aTokenD->uid);
    }
    
    /* $record = array(
      'id' => $site->id,
      'last' => time(),
    );
    drupal_write_record('auth_user_site', $record, 'id'); */

    return $aToken;
  }
}