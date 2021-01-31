<?php

namespace Drupal\phone_international\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a phone_international form.
 *
 * Usage example:
 *
 * By default field has geolocation enable.
 *
 * @code
 * $form['phone'] = [
 *   '#type' => 'phone_international',
 *   '#title' => $this->t('International Phone'),
 * ];
 * @endcode
 *
 * If you want default country you need to do this:
 *
 * @code
 * $form['phone'] = [
 *   '#type' => 'phone_international',
 *   '#title' => $this->t('International Phone'),
 *   '#attributes' => [
 *      'data-country' => 'PT',
 *      'data-geo' => 0, // 0(Disable) or 1(Enable)
 *   ],
 * ];
 * @endcode
 *
 * @FormElement("phone_international")
 */
class PhoneInternationalElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (!empty($input['full_number'])) {
      return $input['full_number'];
    }
    else {
      return '';
    }
  }

  /**
   * Form element validation handler for #type 'phone_international'.
   */
  public static function validateNumber(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    $form_state->setValueForElement($element, $value);
    if ($value !== '' && !\Drupal::service('phone_international.validate')
      ->isValidNumber($value)) {
      $form_state->setError($element, t('The %name "%phone_international" is not valid.', [
        '%phone_international' => $value,
        '%name' => $element['#title'],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processInternationalPhone'],
      ],
      '#element_validate' => [
        [$class, 'validateNumber'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#tree' => TRUE,
      '#country' => '',
      '#geolocation' => 0,
    ];
  }

  /**
   * Add tel and hidden input to phone_international element.
   */
  public static function processInternationalPhone(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#attached']['library'][] = 'phone_international/phone_international';

    $element['int_phone'] = [
      '#type' => 'tel',
      '#default_value' => $element['#default_value'],
      '#attributes' => [
        'class' => ['phone_international-number'],
        'data-country' => $element['#country'],
        'data-geo' => $element['#geolocation'],
      ],
      '#theme_wrappers' => [],
      '#size' => 30,
      '#maxlength' => 128,
    ];

    if (!array_key_exists('data-geo', $element['#attributes'])) {
      $element['int_phone']['#attributes']['data-geo'] = 1;
    }

    // Add default country.
    if (!array_key_exists('data-country', $element['#attributes'])) {
      $element['int_phone']['#attributes']['data-country'] = 'PT';
    }

    // Get Module path to load utilsTellInput.js.
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('phone_international')->getPath();
    $element['#attached']['drupalSettings']['phone_international']['path'] = $module_path;

    $element['full_number'] = [
      '#type' => 'hidden',
    ];

    if (isset($element['#value']) && !empty($element['#value'])) {
      $element['int_phone']['#value'] = $element['#value'];
      $element['full_number']['#value'] = $element['#value'];
    }

    return $element;
  }

}
