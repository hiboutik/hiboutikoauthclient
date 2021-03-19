<?php

namespace Hiboutik\OAuth;


/**
 * @package Hiboutik\OAuth\HttpRequest
 *
 * @version 1.0.0
 * @author  Hiboutik
 *
 * @license GPLv3
 * @license https://gnu.org/licenses/gpl.html
 *
 */
class HttpRequest implements HttpRequestInterface
{
  /** @var object Curl connection */
  protected $curl;
  /** @var array Headers of the last request made*/
  protected $current_headers;
  /** @var array Headers to be sent with the next request. Shape: $key => $value*/
  protected $send_headers;

  /** @var array Curl options */
  protected $curl_opts;


/**
 * Default constructor
 *
 * @param string $ua User agent
 */
  public function __construct($ua = '-')
  {
    if (!extension_loaded('curl')) {
      throw new \ErrorException('cURL library is not loaded');
    }
    $this->current_headers = [];
    $this->send_headers = [];
    $this->curl = curl_init();
    $this->curl_opts = [
      CURLOPT_AUTOREFERER    => false,
      CURLOPT_FORBID_REUSE   => true,
      CURLOPT_FRESH_CONNECT  => true,
      CURLOPT_RETURNTRANSFER => true,

      CURLOPT_CONNECTTIMEOUT => 4,
      CURLOPT_TIMEOUT        => 10,
      CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,

      CURLOPT_USERAGENT      => $ua,

      CURLOPT_HEADERFUNCTION => $this->_makeCallback(),
    ];
  }


/**
 * Reset curl
 *
 * @return object HttpRequest ($this)
 */
  public function resetCurl()
  {
    curl_reset($this->curl);
    return $this;
  }


/**
 * Make a get request
 *
 * @param string $url
 * @param array|null $data
 * @return string
 */
  public function get($url, $data = null)
  {
    $this->curl_opts[CURLOPT_URL] = $data === null ? $url : $url.'?'.http_build_query($data, '', '&');
    $this->curl_opts[CURLOPT_HTTPGET] = true;
    $this->curl_opts[CURLOPT_HTTPHEADER] = $this->_prepareHeaders();

    $this->_setOptions();

    if (($result = curl_exec($this->curl)) !== false) {
      return $result;
    }
  }


/**
 * Make a post request
 *
 * This method does not support file uploads.
 * 'Content-Type' is either 'application/json' or
 * 'application/x-www-form-urlencoded'
 *
 * @param string $url
 * @param array|object|null $data
 * @param boolean $is_json
 * @return string
 */
  public function post($url, $data = null, $is_json = false)
  {
/*
If $data is "url encoded" the content type is "application\/x-www-form-
urlencoded", otherwise, if the data passed is an array the content type will
be "multipart\/form-data;boundary=------------------------a83e...."
*/
    if ($is_json) {
      $this->setHeaders('Content-Type', 'application/json');
      $send_data = json_encode($data);
    } else {
      if (is_array($data) || is_object($data)) {
        $send_data = http_build_query($data, '', '&');
      } else {
        $send_data = http_build_query([$data], '', '&');
      }
    }

    $this->curl_opts[CURLOPT_URL] = $url;
    $this->curl_opts[CURLOPT_POST] = true;
    $this->curl_opts[CURLOPT_POSTFIELDS] = $send_data;
    $this->curl_opts[CURLOPT_HTTPHEADER] = $this->_prepareHeaders();

    $this->_setOptions();

    if (($result = curl_exec($this->curl)) !== false) {
      return $result;
    }
  }


/**
 * Make a put request
 *
 * This method does not support file uploads.
 * 'Content-Type' is either 'application/json' or
 * 'application/x-www-form-urlencoded'
 *
 * @param string $url
 * @param array|object|null $data
 * @param boolean $is_json
 * @return string
 */
  public function put($url, $data = null, $is_json = false)
  {
//     curl_setopt_array($this->curl, [
//       CURLOPT_URL => $url,
//       CURLOPT_PUT => true,
//       CURLOPT_INFILE => '',
//       CURLOPT_INFILESIZE => 0
//     ]);
    if ($is_json) {
      $this->setHeaders('Content-Type', 'application/json');
      $send_data = json_encode($data);
    } else {
      if (is_array($data) || is_object($data)) {
        $send_data = http_build_query($data, '', '&');
      } else {
        $send_data = http_build_query([$data], '', '&');
      }
    }
    $this->curl_opts[CURLOPT_URL] = $url;
    $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
    $this->curl_opts[CURLOPT_POSTFIELDS] = $send_data;
    $this->curl_opts[CURLOPT_HTTPHEADER] = $this->_prepareHeaders();

    $this->_setOptions();

    if (($result = curl_exec($this->curl)) !== false) {
      return $result;
    }
  }


/**
 * Make a delete request
 *
 * @param string $url
 * @return string
 */
  public function delete($url)
  {
    $this->curl_opts[CURLOPT_URL] = $url;
    $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    $this->curl_opts[CURLOPT_HTTPHEADER] = $this->_prepareHeaders();

    $this->_setOptions();

    if (($result = curl_exec($this->curl)) !== false) {
      return $result;
    }
  }


/**
 * Get request status
 *
 * @return array
 */
  public function status()
  {
    $return_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    if ($return_code === 0) {
      return [
        'error' => [
          'code' => curl_errno($this->curl),
          'error_description' => curl_error($this->curl)
        ]
      ];
    } else {
      return curl_getinfo($this->curl);
    }
  }


/**
 * Set user agent
 *
 * @param string $ua User agent name
 * @return object HttpRequest ($this)
 */
  public function setUserAgent($ua)
  {
    curl_setopt($this->curl, CURLOPT_USERAGENT, $ua);
    return $this;
  }


/**
 * Get all or a header field
 *
 * @param string $field Optional
 * @return array|string
 */
  public function getHeader($field = null)
  {
    if ($field !== null) {
      if (isset($this->current_headers[$field])) {
        return $this->current_headers[$field];
      } else {
        return null;
      }
    } else {
      return $this->current_headers;
    }
  }


/**
 * Set headrs to send
 *
 * @param string $header_name
 * @param string $header_value
 * @return object HttpRequest ($this)
 */
  public function setHeaders($header_name = '', $header_value = '')
  {
    $this->send_headers[$header_name] = $header_value;
    return $this;
  }


/**
 * Convert headers array to right format
 *
 * The headers are stored in an array shaped as $key => $value pairs. This
 * enables replacement.
 * The Curl class expects the shape of the array to be:
 * <code>
 *   [
 *     'Content-type: application/json',
 *     'Cache-Control: no-cache, must-revalidate'
 *   ];
 * </code>
 * Hence this method.
 *
 * @return array
 */
  protected function _prepareHeaders()
  {
    $headers = [];
    foreach ($this->send_headers as $key => $value) {
      $headers[] = "$key: $value";
    }
    return $headers;
  }


/**
 * Set basic authentication
 *
 * @param string $user
 * @param string $password
 * @return object HttpRequest ($this)
 */
  public function basicAuth($user, $password)
  {
    $this->curl_opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
    $this->curl_opts[CURLOPT_USERPWD] = "$user:$password";
    return $this;
  }


/**
 * Unset basic authentication
 *
 * @return object HttpRequest ($this)
 */
  public function stopBasicAuth()
  {
    curl_setopt_array($this->curl, [
      CURLOPT_HTTPAUTH => null,
      CURLOPT_USERPWD => null
    ]);
    return $this;
  }


/**
 * Set OAuth token
 *
 * @param string $token
 * @return object HttpRequest ($this)
 */
  public function setOAuthToken($token)
  {
    if (defined('CURLOPT_XOAUTH2_BEARER')) {
      curl_setopt($this->curl, CURLOPT_XOAUTH2_BEARER, $token);
    } else {
      $this->setHeaders('Authorization', "Bearer $token");
    }
    return $this;
  }


/**
 * Get last HTTP code
 *
 * @return integer
 */
  public function getCode()
  {
    return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
  }


/**
 * @internal
 *
 * Get request's headers
 *
 * This method is called after the request is complete and builds an array with
 * headers fields.
 *
 * @return function
 */
  protected function _makeCallback()
  {
    return function ($ch, $header_data)
    {
      if (stripos($header_data, 'HTTP/') === false and $header_data !== "\r\n") {
        preg_match('/([A-z0-9-]+):\s*(.*)$/', $header_data, $matches);
        $this->current_headers[$matches[1]] = $matches[2];
      }
      return strlen($header_data);
    };
  }


/**
 * Send result to file
 *
 * @param strinf $destination Path\to\file
 * @return object HttpRequest ($this)
 */
  public function toFile($destination)
  {
    $file = fopen($destination, 'w');
    curl_setopt($this->curl, CURLOPT_FILE, $file);
    return $this;
  }


/**
 * Default destructor
 *
 * @return integer
 */
  public function __destruct()
  {
    curl_close($this->curl);
  }


/**
 * @internal
 *
 * Sets headers in the Curl object
 *
 * @return void
 */
  protected function _setOptions()
  {
    if (!curl_setopt_array($this->curl, $this->curl_opts)) {
      throw new \Exception('Class Hiboutik\HttpRequest: invalid option;');
    }
  }
}
