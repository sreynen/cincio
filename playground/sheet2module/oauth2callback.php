<?php

session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/google.oauth.credentials.inc';

set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/includes/google-api-php-client/src');

require_once 'Google/Client.php';

print_r($_GET);
die();

if ($_GET['code']) {

  $client = new Google_Client();
  $client->setClientId($oauth_client_id);
  $client->setClientSecret($oauth_client_secret);
  $client->setRedirectUri($oauth_redirect_uri);
  $client->addScope("https://www.googleapis.com/auth/drive");

  $client->authenticate($_GET['code']);
  $_SESSION['oauth_token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . '/playground/drive2module/';
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
  exit();

}
else {

  $redirect = 'http://' . $_SERVER['HTTP_HOST'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));


}
