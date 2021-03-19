<?php

namespace Hiboutik\OAuth;


/**
 * @package Hiboutik\OAuth\Client
 *
 * @version 1.0.0
 * @author  Hiboutik
 *
 * @license GPLv3
 * @license https://gnu.org/licenses/gpl.html
 *
 */
class Client implements ClientInterface
{
  /** @var object Hiboutik\OAuth\HttpRequest */
  protected $hr;
  /** @var string */
  protected $hiboutik_account;
  /** @var string */
  protected $client_pass;
  /** @var string */
  protected $token_endpoint_uri;
  /** @var string|null */
  protected $token = null;
  /** @var integer */
  protected $timestamp;
  /** @var string */
  protected $scope;
  /** @var string */
  protected $hiboutik_uri;

  /** @var string */
  public $client_id;
  /** @var object|null Callable object */
  public $template_install = null;
  /** @var object|null Callable object */
  public $template_result = null;


/**
 * Class constructor
 *
 * @param string $hiboutik_account
 * @param string $client_id
 * @param string $client_pass
 * @return void
 */
  public function __construct($hiboutik_account, $client_id, $client_pass)
  {
    $this->scope = 'basic_api';
    $this->hiboutik_uri = '.hiboutik.com/oauth_api';
    $this->token_endpoint_uri = "https://$hiboutik_account".$this->hiboutik_uri."/token.php";
    $this->hiboutik_account = $hiboutik_account;
    $this->timestamp = date('U');
    $this->client_id = $client_id;
    $this->client_pass = $client_pass;
  }


/**
 * Generates an hmac hash using the client id, secret and a timestamp
 *
 * @return string
 */
  public function startSession()
  {
    return hash_hmac('sha256', 'client_id='.$this->client_id.'&timestamp='.$this->timestamp, $this->client_pass);
  }


/**
 * Generates a query string
 *
 * @return string
 */
  public function authQueryString()
  {
    return 'state='.($this->startSession()).'&account='.($this->hiboutik_account).'&timestamp='.($this->timestamp);
  }


/**
 * Checks if the hmac hash is valid and initiates an HTTP request to get the
 * access token.
 *
 * @param boolean $session_check
 * @return array
 */
  public function handleRequest($session_check = false)
  {
    if (!isset($_GET['error'])) {
      if ($session_check) {
        $result = $this->tokenRequest();
        if (isset($result['access_token'])) {
          $this->token = $result;
          return $result;
        } else {
          return [
            'error' => $result['error'],
            'error_description' => $result['error_description']
          ];
        }
      } else {
        return [
          'error' => 'invalid_session',
          'error_description' => 'This session is invalid'
        ];
      }
    } else {
      return [
        'error' => $_GET['error'],
        'error_description' => $_GET['error_description']
      ];
    }
  }


/**
 * Checks the session comparing the hmac values, received as $_GET parameter and
 * the one calculated.
 *
 * @return boolean
 */
  public function validSession()
  {
    // Check if it is an old state; 1800 seconds of variation allowed
    if (date('U') > ($_GET['timestamp'] + 1800)) {
      return false;
    }
    // Preventing timing attacks
    if (function_exists('\hash_equals')) {
      return hash_equals(
        $_GET['state'],
        hash_hmac('sha256', 'client_id='.$this->client_id.'&timestamp='.$_GET['timestamp'], $this->client_pass)
      );
    } else {// Ante PHP 5.6.0
      $hash = hash_hmac('sha256', 'client_id='.$this->client_id.'&timestamp='.$_GET['timestamp'], $this->client_pass);
      $len_hash = strlen($hash);
      $len_hash_get = strlen($_GET['state']);
      if ($len_hash === $len_hash_get) {
        $equal = true;
        for ($i = $len_hash - 1; $i !== -1; $i--) {
          if ($hash[$i] !== $_GET['state'][$i]) {
            $equal = false;
          }
        }
        return $equal;
      } else {
        return false;
      }
    }
  }


/**
 * Returns the token
 *
 * @return array
 */
  public function showToken()
  {
    return $this->token;
  }


/**
 * Makes an HTTP request with the code received and get the token in exchange
 *
 * @return array
 */
  public function tokenRequest(HttpRequest $hr = null)
  {
    $this->hr = $hr === null ? new HttpRequest('HiboutikOauthClient Client v1') : $hr;
    $this->hr->basicAuth($this->client_id, $this->client_pass);
    $result_json = $this->hr->post($this->token_endpoint_uri, [
      'grant_type' => 'authorization_code',
      'code' => $_GET['code']
    ]);
    return json_decode($result_json, JSON_OBJECT_AS_ARRAY);
  }


/**
 * Make an HTTP request with the refresh token and get a new token in exchange
 *
 * @param string $refresh_token
 * @param HttpRequest HttpRequest object
 * @return array
 */
  public function getRefreshToken($refresh_token, HttpRequest $hr = null)
  {
    $this->hr = $hr === null ? new HttpRequest('HiboutikOauthClient Client v1') : $hr;
    $this->hr->basicAuth($this->client_id, $this->client_pass);
    $result_json = $this->hr->post($this->token_endpoint_uri, [
      'grant_type' => 'refresh_token',
      'refresh_token' => $refresh_token
    ]);
    return json_decode($result_json, JSON_OBJECT_AS_ARRAY);
  }


/**
 * Change the scope; for multiple scopes the separator is space
 *
 * @return HiboutikOauthClient '$this' object
 */
  public function setScope($scope)
  {
    $this->scope = urlencode($scope);
    return $this;
  }


/**
 * @param string $template Callable object
 * @return HiboutikOauthClient '$this' object
 */
  public function setTemplateInstall(Callable $template)
  {
    $this->template_install = $template;
  }


/**
 * @param string $template Callable object
 * @return HiboutikOauthClient '$this' object
 */
  public function setTemplateResult(Callable $template)
  {
    $this->template_result = $template;
  }


/**
 * This function binds everything toghether. If there's a response from the
 * authorization endpoint the state (hmac) is checked, the request is handled
 * and the callback is executed.
 * If the page is called directly or an error is receive another callback is
 * executed.
 *
 * @return array
 */
  public function run()
  {
    if (isset($_GET['code']) and isset($_GET['state'])) {
      $result = [
        'page' => 'result',
        'result' => $this->handleRequest($this->validSession())
      ];
      if ($this->template_result !== null) {
        // If the template is a '.php' file it will be included else it will be executed
        if (strripos($this->template_result, '.php', -4) !== false) {
          $oauth = $this;
          $_ = $result;
          require $this->template_result;
        } else {
          call_user_func($this->template_result, $result, $this);
        }
      }
    } else {
      $result = ['page' => 'install'];
      if (isset($_GET['error'])) {
        $result['error'] = $_GET['error'];
        $result['error_description'] = $_GET['error_description'];
      } else {
        $result['url'] = 'https://'.($this->hiboutik_account).
          $this->hiboutik_uri.'/authorize/?response_type=code&client_id='.($this->client_id).
          '&state='.$this->startSession().'&scope='.$this->scope.'&account='.
          ($this->hiboutik_account).
          '&timestamp='.($this->timestamp);
      }
      if ($this->template_install !== null) {
        // If the template is a '.php' file it will be included else it will be executed
        if (strripos($this->template_install, '.php', -4) !== false) {
          $oauth = $this;
          $_ = $result;
          require $this->template_install;
        } else {
          call_user_func($this->template_install, $result, $this);
        }
      }
    }
    return $result;
  }

}
