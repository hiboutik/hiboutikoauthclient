# Hiboutik OAuth Client

This package requests tokens from the Hiboutik OAuth Server.


## Requirements

* PHP 5.3.0 or newer
* PHP cURL extension


## Installation

### Composer
```sh
composer require hiboutik/oauth
```

And in your script

```php
<?php
require 'vendor/autoload.php';
```

### Manual installation

Download this package and include the autoloader:

```php
require 'HiboutikOAuthClient/src/Hiboutik/OAuth/autoloader.php';
```


## Use

You need your OAuth client id, password and the name of your Hiboutik account
```php
$hiboutik_account = 'my_account';
$oauth_client_id = 'hiboutik_client';
$oauth_client_pass = 'qwerty';
```
Next initialize the class:
```php
$hiboutik_oauth = new Hiboutik\OAuth\Client($hiboutik_account, $oauth_client_id, $oauth_client_pass);
```
Set the OAuth scope(s):
```php
$hiboutik_oauth->setScope('read_products write_products');// space delimiter
```
Register the templates for installation page and the confirmation page. You can specify a callback or a file to include:
```php
$hiboutik_oauth->template_install = 'templateInstall';
$hiboutik_oauth->template_result = 'templateResult';
```
Here is a very basic exemple of a callback for installation:
```php
function templateInstall($_, $oauth)
{
  print <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My app</title>
  </head>
  <body>
    <div>
HTML;

  if (isset($_['error'])) {
    print 'Error: '.$_['error_description'];
  } else {
    print '<a class="bt mt-3" href="'.$_['url'].'">Install</a>';
  }

  print <<<HTML
    </div>
  </body>
</html>
HTML;
}
```
and for processing the result:
```php
function templateResult($_, $oauth)
{
  print <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My app</title>
  </head>
  <body>
    <div>
HTML;

  if (isset($_['result']['error'])) {
    print $_['result']['error_description'];
  } else {
    print '<h1>Application installed</h1>';
    print 'token: '.$_['result']['access_token'];
  }

  print <<<HTML
    </div>
  </body>
</html>
HTML;
}
```

The callbacks are passed two arguments. The first is an array. The second is the Hiboutik\OAuth\Client object.
See the exemple (exemples/exemple_1.php).
Finally, run the client:
```php
$hiboutik_oauth->run();
```
When a token is obtained it will be made available like so:
```php
$access_token = $hiboutik_oauth->showToken();
if ($access_token !== null) {
  print_r($access_token);
}
```
The token array has the following structure (the tokens are for illustratives purposes only):
```php
[
  'access_token'  => '18c148f580cb96ff458a0ec25c0e78b4a8bbf56d'
  'expires_in'    => 16000000
  'token_type'    => 'Bearer'
  'scope'         => 'basic_api'
  'refresh_token' => 'eef101f78656f404d79ea0ec877f9da12ae5c70d'
];
```
See the exemples directory.


