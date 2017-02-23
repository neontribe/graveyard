<?php

namespace Drupal\nt8tabsio\Service;

/**
 * Description of NT8TabsIOController
 *
 * @author tobias
 */
class NT8TabsRestService {

  protected $lastError = FALSE;
  protected $pubkey = FALSE;
  protected $secret = FALSE;
  protected $uri = FALSE;

  /**
   * Magic constructor.
   */
  public function __construct() {
    $this->pubkey = \Drupal::config('nt8tabsio.settings')->get('key');
    $this->secret = \Drupal::config('nt8tabsio.settings')->get('secret');
    $this->uri = \Drupal::config('nt8tabsio.settings')->get('uri');

    \Drupal::logger('NT8TabsRestService')->info('Pubkey: @pubkey', array('@pubkey' => $this->pubkey));
    \Drupal::logger('NT8TabsRestService')->info('Secret: @secret', array('@secret' => $this->secret));
    \Drupal::logger('NT8TabsRestService')->info('URI: @uri', array('@uri' => $this->uri));
  }

  /**
   * Get last (error) response from the API.
   *
   * @return string
   *   The last (error).
   */
  public function getLastError() {
    return $this->lastError;
  }

  /**
   * We use PHP's magic __call method for dynamic calling of the REST types.
   *
   * @param string $method
   *   The method called.
   * @param array $arguments
   *   The arguments to that method.
   *
   * @return mixed
   *   array of stdClass objects or FALSE on failure
   *
   * @throws \Exception
   */
  public function __call($method, $arguments) {
    $verbs = array('get', 'post', 'put', 'delete', 'del', 'options');
    if (in_array($method, $verbs)) {
      array_unshift($arguments, strtoupper($method));

      return call_user_func_array(array($this, 'rest'), $arguments);
    }

    if (strlen($method) > 3) {
      $action = substr($method, 0, 3);
      $var = substr($method, 3);

      if ($action == "get") {
        return $this->$var;
      }
      elseif ($action == "set") {
        $this->$var = $arguments[0];
        return;
      }
    }

    throw new \Exception("Method $method does not exist.");
  }

  /**
   * This method actually performs the calls back to the REST service.
   *
   * It also expects parameters 2 and 3 to be the path into the remote service
   * and an array of data parameters to use to build the request.
   *
   * @param string $method
   *   The http verb to use.
   *
   * @return mixed
   *   Array of stdClass objects or FALSE on failure.
   *
   * @throws \Exception
   */
  public function rest($method) {
    $this->lastError = FALSE;

    $args = array_slice(func_get_args(), 1);
    if (!empty($args[0])) {
      $path = $args[0];
    }

    if (!empty($args[1])) {
      $params = $args[1];
    }
    else {
      $params = array();
    }

    $restdata = $this->hmacEncode(array('data' => json_encode($params)));
    $querydata = array();

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_USERAGENT, "neontribe/nt8");
    curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    curl_setopt($curl, CURLOPT_SAFE_UPLOAD, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

    switch ($method) {
      case 'OPTIONS':
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "OPTIONS");
        break;

      case 'GET':
        $querydata = $this->hmacEncode(self::filterParams($params, TRUE));
        break;

      case 'POST':
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($restdata, NULL, '&'));
        $restdata = array();
        break;

      case 'PUT':
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($restdata, NULL, '&'));
        $restdata = array();
        break;

      case 'DELETE':
      case 'DEL':
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;

      default:
        watchdog(__METHOD__, 'Invalid method (:method) specified', array(':method' => $method), WATCHDOG_ERROR);
        return FALSE;
    }

    $url = $this->buildRequestUrl($method, $path, $querydata);

    curl_setopt($curl, CURLOPT_URL, $url);

    $this->lastError = FALSE;
    $response_body = curl_exec($curl);
    $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (substr($response_code, 0, 1) != '2') {
      // If we didn't get a 2xx HTTP response, log the responsebody as an error.
      $this->lastError = trim($response_body);
      $_params = array(
        '@method' => $method,
        '@path' => $path,
        '@err' => $this->lastError,
        '@json' => json_encode($params),
      );
      \Drupal::logger('NT8TabsRestService')->error('@method: @path @err @json', $_params);
    }

    $_response_body = trim($response_body);

    return $_response_body;
  }

  /**
   * Extracts page control parameters from the list and stashes the rest in a data element.
   *
   * @param array $params
   *   The parameters ready to send to the API.
   *
   * @return array
   *   Cleaned array.
   */
  protected function filterParams($params) {
    $filtered_params = array();

    if (isset($params['pageSize'])) {
      $filtered_params['pageSize'] = $params['pageSize'];
      unset($params['pageSize']);
    }
    if (isset($params['page'])) {
      $filtered_params['page'] = $params['page'];
      unset($params['page']);
    }
    if (isset($params['searchId'])) {
      $filtered_params['searchId'] = $params['searchId'];
      unset($params['searchId']);
    }
    if (isset($params['fields'])) {
      $filtered_params['fields'] = $params['fields'];
      unset($params['fields']);
    }
    if (count($params)) {
      $filtered_params['filter'] = http_build_query($params);
    }

    return $filtered_params;
  }

  /**
   * Parse arguments sent to the rest function.  Might be extended in future for callbacks.
   *
   * @param string $method
   *   The HTTP verb to be used to access the service.
   * @param string $path
   *   The path on the API.
   * @param array $data
   *   The data array.
   *
   * @return string
   *   The URI to access the API with.
   */
  public function buildRequestUrl($method, $path, $data) {
    $url = $this->uri . '/' . $path;
    if (in_array($method, array('GET', 'DELETE', 'DEL', 'OPTIONS')) && !empty($data)) {
      $url .= '?' . http_build_query($data, '', '&');
    }

    return $url;
  }

  /**
   * Return a json representation of this object.
   *
   * @param int $options
   *   The options constant to be passed to the json encode function.
   *
   * @return stringe
   *   The json rpresentaion of this object.
   */
  public function toJson($options = 0) {
    $data = $this->toArray();
    return json_encode($data, $options);
  }

  /**
   * Convert this object to an array.
   *
   * @return array
   *   This object as an array.
   */
  public function toArray() {
    $fields = $this->getFields();
    $data = array();
    foreach ($fields as $field) {
      $func = 'get' . ucfirst($field);
      $data[$field] = $this->$func();
    }

    return $data;
  }

  /**
   * Turns an array into an object.
   *
   * @param string|array $data
   *   The data to be set on the fields.
   * @param string $class
   *   The name of the class to be created.
   *
   * @return \class
   *   The class created.
   */
  public static function factory($data, $class) {
    if (!is_array($data)) {
      $_data = json_decode($data, TRUE);
    }
    else {
      $_data = $data;
    }

    if (isset($_data['errorCode']) && $_data['errorCode'] == -1) {
      watchdog(__METHOD__, $_data['errorDescription']);
      return FALSE;
    }
    else {
      $_class = new $class();
      $fields = $_class::getFields();
      foreach ($fields as $field) {
        $ucf_field = ucfirst($field);
        $func = "set$ucf_field";
        $_class->$func($_data[$field]);
      }
    }

    return $_class;
  }

  /**
   * Encode function.
   *
   * @param array $params
   *   Parameters to encode.
   * @param string $secret
   *   Secret key.
   * @param string $key
   *   Key.
   *
   * @return array
   *   Parameters.
   */
  function hmacEncode($params) {
    $params['APIKEY'] = $this->pubkey;
    $params['APISECRET'] = $this->secret;
    ksort($params);
    $params = array_map('strval', $params);
    $hash = $this->hmacHash(json_encode($params));
    $params['hash'] = $hash;
    unset($params['APISECRET']);
    return $params;
  }

  /**
   * Check function.
   *
   * @param string $params
   *   Parameters to check.
   * @param string $secret
   *   Secret key.
   *
   * @return bool
   *   Passed or failed.
   */
  function hmacCheck($params, $secret) {

    $hash = $params['hash'];
    unset($params['hash']);
    $params['APISECRET'] = $secret;
    ksort($params);
    $params = array_map('strval', $params);
    $_hash = neontabs_hmac_hash(json_encode($params));
    return ($_hash == $hash);
  }

  /**
   * Hash function.
   *
   * @param array $data
   *   Data.
   *
   * @return string
   *   The hashed data.
   */
  function hmacHash($data) {

    return hash('SHA256', $data, FALSE);
  }

}

// vim: set filetype=php expandtab tabstop=2 shiftwidth=2 autoindent smartindent:
