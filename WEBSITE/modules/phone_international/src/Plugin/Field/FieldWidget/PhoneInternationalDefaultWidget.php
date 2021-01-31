<?php

namespace Drupal\phone_international\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;

/**
 * Plugin implementation of the 'phone_international_widget' widget.
 *
 * @FieldWidget(
 *   id = "phone_international_widget",
 *   module = "phone_international",
 *   label = @Translation("Text field"),
 *   field_types = {
 *     "phone_international"
 *   }
 * )
 */
class PhoneInternationalDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'initial_country' => 'PT',
      'geolocation' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'phone_international',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#country' => $this->getSetting('initial_country'),
      '#geolocation' => $this->getSetting('geolocation') ? 1 : 0,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['geolocation'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Geolocation'),
      '#default_value' => $this->getSetting('geolocation'),
    ];

    $countries = CountryManager::getStandardList();
    $elements['initial_country'] = [
      '#type' => 'select',
      '#title' => t('Initial Country'),
      '#options' => $countries,
      '#default_value' => $this->getSetting('initial_country'),
      '#description' => t('Set default selected country to use in phone field.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $geolocation = $this->getSetting('geolocation');
    $summary[] = t('Use Geolocation: @display_label', ['@display_label' => ($geolocation ? t('Yes') : 'No')]);
    if (!$geolocation) {
      $summary[] = t('Default selected country: @value', ['@value' => $this->getSetting('initial_country')]);
    }
    return $summary;
  }

}
