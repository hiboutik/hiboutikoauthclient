<?php

require 'HiboutikOAuthClient/src/Hiboutik/OAuth/autoloader.php';


// Hiboutik account: https://my_account.hiboutik.com
//                           ----------
$hiboutik_account = 'my_account';
$oauth_client_id = 'hiboutik_client';
$oauth_client_pass = 'my_password';

$hiboutik_oauth = new Hiboutik\OAuth\Client($hiboutik_account, $oauth_client_id, $oauth_client_pass);
$hiboutik_oauth->setScope('read_products write_products');// space delimiter
$hiboutik_oauth->template_install = 'templateInstall';// can be a path to a file to include
$hiboutik_oauth->template_result = 'templateResult';// can be a path to a file to include
/*
Returns an array with the page to display and the token or an error.
The callbacks are called with the result from this method as argument.
Returned array:
[
  'page' => 'result'|'launch',
  'result'|'url'|'error' => []|string
]
*/
$hiboutik_oauth->run();
/**
 * [
 *   'access_token'  => '18c148f580cb96ff458a0ec25c0e78b4a8bbf56d'
 *   'expires_in'    => 16000000
 *   'token_type'    => 'Bearer'
 *   'scope'         => 'basic_api'
 *   'refresh_token' => 'eef101f78656f404d79ea0ec877f9da12ae5c70d'
 * ];
 */
$access_token = $hiboutik_oauth->showToken();// store it in a database
if ($access_token !== null) {
  print_r($access_token);
}


function templateInstall($_, $oauth)
{
  print <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opalona app</title>
  </head>
  <body>
    <div>
HTML;

  if (isset($_['error'])) {
    print 'Error: '.$_['error_description'];
  } else {
    print '<a class="bt mt-3" href="'.$_['url'].'">Installer</a>';
  }

  print <<<HTML
    </div>
  </body>
</html>
HTML;
}


function templateResult($_, $oauth)
{
  print <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opalona app</title>
  </head>
  <body>
    <div>
HTML;

  if (isset($_['result']['error'])) {
    print $_['result']['error_description'];
  } else {
    print '<h1>Application instalée</h1>';
    print 'token: '.$_['result']['access_token'];
  }

  print <<<HTML
    </div>
  </body>
</html>
HTML;
}
