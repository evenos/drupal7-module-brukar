<?php

function brukar_server_admin_overview() {
   return '';
}
 
function brukar_server_admin_applications() {
  $res['apps'] = array(
    '#theme' => 'table',
    '#rows' => array(),
    '#header' => array(t('Name'), t('Owner'), t('Status'), t('Operations')),
  );
  
  foreach (db_select('brukar_application', 'a')->fields('a')->orderBy('name')->execute() as $row) {
    $res['apps']['#rows'][] = array(
      array('data' => l($row->name, 'admin/structure/brukar/application/' . $row->id)),
      array('data' => is_null($row->uid) ? '-' : user_load($row->uid)->name),
      array('data' => $row->active == 0 ? t('Inactive') : t('Active')),
      array('data' => array(
        '#theme' => 'links', '#attributes' => array('class' => array('links', 'inline')), '#links' => array( 
          'edit' => array('title' => t('edit'), 'href' => 'admin/structure/brukar/application/' . $row->id . '/edit'),
          'users' => array('title' => t('users'), 'href' => 'admin/structure/brukar/application/' . $row->id . '/users'),
        )
      )),
    );
  }
  
  return $res;
}

function brukar_server_admin_applications_form($form, &$form_state, $app = NULL) {
  $form['id'] = array(
    '#type' => 'value',
    '#value' => $app ? $app->id : 0,
  );
  $form['name'] = array(
    '#title' => t('Name'),
    '#type' => 'textfield',
    '#default_value' => $app ? $app->name : '',
  );
  $form['url_homepage'] = array(
    '#title' => 'Url, homepage',
    '#type' => 'textfield',
    '#default_value' => $app ? $app->url_homepage : '',
  );
  $form['url_login'] = array(
    '#title' => 'Url, login',
    '#type' => 'textfield',
    '#default_value' => $app ? $app->url_login : '',
  );
  $form['url_callback'] = array(
    '#title' => 'Url, callback',
    '#type' => 'textfield',
    '#default_value' => $app ? $app->url_callback : '',
  );
  $form['submit'] = array(
    '#value' => $app ? t('Save') : t('Create'),
    '#type' => 'submit',  
  );
  
  return $form;
}

function brukar_server_admin_applications_form_submit($form, &$form_state) {
  $val = (object) $form_state['values'];
  
  if ($val->id == 0) {
    $val->consumer_key = brukar_common_uuid();
    $val->consumer_secret = brukar_common_uuid();
    $val->active = 1;
  }
  
  drupal_write_record('brukar_application', $val, $val->id == 0 ? array() : 'id');
  drupal_goto('admin/structure/brukar/application');
}

function brukar_server_admin_applications_users($app) {
  $res['add'] = drupal_get_form('brukar_server_admin_applications_users_add', $app);
  
  $res['users'] = array(
    '#theme' => 'table',
    '#header' => array(t('Name'), t('Level'), t('Status'), t('Operations')),
    '#rows' => array(),
  );
  
  foreach (db_select('brukar_access', 'a')->fields('a')->execute() as $row) {
    $res['users']['#rows'][] = array(
      array('data' => user_load($row->uid)->name),
      array('data' => '-'),
      array('data' => $row->active == 0 ? t('Inactive') : t('Active')),
      array('data' => array(
        '#theme' => 'links', '#attributes' => array('class' => array('links', 'inline')), '#links' => array(
          'change' => array('title' => t('change'), 'href' => 'admin/structure/brukar/application/' . $app->id . '/users/change/' . $row->id),
          'remove' => array('title' => t('remove'), 'href' => 'admin/structure/brukar/application/' . $app->id . '/users/remove/' . $row->id),
        )
      )),
    );
  }
  
  return $res;
}

function brukar_server_admin_applications_users_add($form, &$form_state, $app) {
  $form['application_id'] = array(
    '#type' => 'value',
    '#value' => $app->id,
  );
  
  $form['user'] = array(
    '#title' => t('User'),
    '#type' => 'textfield',
    '#autocomplete_path' => 'user/autocomplete',
  );
  
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add'),
  );
  
  return $form;
}

function brukar_server_admin_applications_users_add_submit($form, &$form_state) {
  $user = user_load_by_name($form_state['values']['user']);
  $record = array(
    'uid' => $user->uid,
    'application' => $form_state['values']['application_id'],
    'active' => 1,
  );
  drupal_write_record('brukar_access', $record);
}

function brukar_server_admin_applications_users_change($app, $access) {
  $access->active = $access->active == 0 ? 1 : 0;
  drupal_write_record('brukar_access', $access, 'id');
  drupal_goto('admin/structure/brukar/application/' . $app->id . '/users');
}

function brukar_server_admin_applications_users_remove($app, $access) {
  db_delete('brukar_access')->condition('id', $access->id)->execute();
  drupal_goto('admin/structure/brukar/application/' . $app->id . '/users');
}

function brukar_server_admin_applications_view($app) {
  $res = array();

  $res['consumer_key'] = array(
    '#theme' => 'user_profile_item',
    '#title' => 'Consumer key',
    '#markup' => $app->consumer_key,
  );
  $res['consumer_secret'] = array(
    '#theme' => 'user_profile_item',
    '#title' => 'Consumer secret',
    '#markup' => $app->consumer_secret,
  );
  
  $res['url_homepage'] = array(
    '#theme' => 'user_profile_item',
    '#title' => 'Url, homepage',
    '#markup' => l($app->url_homepage, $app->url_homepage),
  );
  $res['url_login'] = array(
    '#theme' => 'user_profile_item',
    '#title' => 'Url, login',
    '#markup' => l($app->url_login, $app->url_login),
  );
  $res['url_callback'] = array(
    '#theme' => 'user_profile_item',
    '#title' => 'Url, callback',
    '#markup' => l($app->url_callback, $app->url_callback),
  );
  
  return $res;
}

function brukar_server_admin_applications_title($app) {
  return $app->name;
}