<?php

namespace Drupal\phone_international\Helpers;

/**
 * Interface isValid.
 */
interface isValid {

  /**
   * Full validation of a phone number.
   *
   * @param string $number
   *   Validate whether the number is valid.
   */
  public function isValidNumber($number);

  /**
   * Format phone number.
   *
   * @param string $number
   *   Format number.
   */
  public function formatNumber($number);

}
