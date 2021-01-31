<?php

namespace Drupal\photo_albums;

use Drupal\Core\Site\Settings;

/**
 * Class TwoWayHashService.
 */
class TwoWayHashService {

  /**
   * Default encryption cypher.
   *
   * @var method
   */
  protected $method;

  /**
   * Private key.
   *
   * @var key
   */
  private $key;

  /**
   * Constructs a new TwoWayHashService object.
   */
  public function __construct() {
    // Get the hashing key and method settings.php.
    $two_way_hashing_key = Settings::get('two_way_hashing_key');
    $two_way_hashing_method = Settings::get('two_way_hashing_method');

    // Check the key is specified and not NULL and set it.
    if (isset($two_way_hashing_key) && !is_null($two_way_hashing_key)) {
      $this->key = $two_way_hashing_key;
    }

    // Set the method.
    $this->method = $two_way_hashing_method;

    // Convert ASCII keys to binary format.
    if (ctype_print($this->key)) {
      $this->key = openssl_digest($this->key, 'SHA256', TRUE);
    }
    else {
      $this->key = $this->key;
    }

    // Ensure the method specified is supported by openssl.
    if ($this->method) {
      if ($this->checkMethod($this->method)) {
        $this->method = $this->method;
      }
      else {
        \Drupal::logger('photo_albums')->error("Specified encryption method not supported: $this->method");
        throw new \Exception("Specified encryption method not supported: $this->method - Ensure you have set up \$two_way_hashing_method and \$two_way_hashing_key correctly in your settings.php file.");
      }
    }
  }

  /**
   * Check encryption method is supported.
   *
   * @param: $method string
   *
   * return bool
   */
  private function checkMethod($method) {
    // Ensure the method specified is supported by openssl.
    if ($method) {
      if (in_array(strtolower($method), openssl_get_cipher_methods())) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * Return IV bytes for selected method.
   */
  protected function ivBytes() {
    return openssl_cipher_iv_length($this->method);
  }

  /**
   * Set Key.
   */
  public function setKey($key) {
    if (ctype_print($key)) {
      // Convert ASCII keys to binary format.
      $this->key = openssl_digest($key, 'SHA256', TRUE);
    }
    else {
      $this->key = $key;
    }
  }

  /**
   * Set Mathod.
   */
  public function setMethod($method) {
    if ($this->checkMethod($method)) {
      $this->method = $method;
    }
    else {
      \Drupal::logger('photo_albums')->error("Specified encryption method not supported: $method");
      throw new \Exception("Specified encryption method not supported: $this->method - Ensure you have set up \$two_way_hashing_method and \$two_way_hashing_key correctly in your settings.php file.");
    }
  }

  /**
   * Encrypt strings.
   */
  public function encrypt($data) {
    $iv = openssl_random_pseudo_bytes($this->ivBytes());
    return bin2hex($iv) . openssl_encrypt($data, $this->method, $this->key, 0, $iv);
  }

  /**
   * Decrypt encrypted string.
   */
  public function decrypt($data) {
    $iv_strlen = 2 * $this->ivBytes();
    if (preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $data, $regs)) {
      list(, $iv, $crypted_string) = $regs;
      if (ctype_xdigit($iv) && strlen($iv) % 2 == 0) {
        return openssl_decrypt($crypted_string, $this->method, $this->key, 0, hex2bin($iv));
      }
    }
    // Failed to decrypt.
    return FALSE;
  }

  /**
   * Check password.
   */
  public function check($pass, $stored_hash) {
    // Decrupt the stored hash.
    $decrypted_pass = $this->decrypt($stored_hash);
    // And return true of false based on whether
    // the decrypted value is the same as the password
    // specified.
    return $decrypted_pass === $pass;
  }

}
