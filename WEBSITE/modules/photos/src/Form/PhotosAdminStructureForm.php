<?php

namespace Drupal\photos\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class PhotosAdminStructureForm extends ConfigFormBase {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photos_admin_settings';
  }

  /**
   * Constructs PhotosAdminSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository, EntityFieldManagerInterface $entity_field_manager, MessengerInterface $messenger, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('messenger'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Get variables for default values.
    $config = $this->config('photos.settings');

    // Load custom admin css and js library.
    $form['#attached']['library'] = [
      'photos/photos.admin',
    ];

    // Photos access integration settings.
    $module_photos_access_exists = $this->moduleHandler->moduleExists('photos_access');
    $url = Url::fromRoute('system.modules_list', [], ['fragment' => 'module-photos-access']);
    $link = Link::fromTextAndUrl('photos_access', $url)->toString();
    $description_msg = '';
    // Set warning if private file path is not set.
    if (!PrivateStream::basePath() && $config->get('photos_access_photos')) {
      $description_msg = $this->t('Warning: image files can still be accessed by
        visiting the direct URL. For better security, ask your website admin to
        setup a private file path.');
    }
    else {
      $description_msg = $this->t('The privacy settings appear on the photo
        album node edit page.');
    }

    $form['photos_access_photos'] = [
      '#type' => 'radios',
      '#title' => $this->t('Privacy settings'),
      '#default_value' => $config->get('photos_access_photos') ?: 0,
      '#description' => $module_photos_access_exists ? $description_msg : $this->t('Enable the @link module.', ['@link' => $link]),
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
      '#required' => TRUE,
      '#disabled' => ($module_photos_access_exists ? FALSE : TRUE),
    ];

    // Display settings.
    $form['display'] = [
      '#title' => $this->t('Display'),
      '#type' => 'container',
    ];
    $form['display']['description'] = [
      '#markup' => $this->t('Default view modes. Add more custom view modes for Photo here: @display_modes_link and enable them here: @view_modes_link.', [
        '@display_modes_link' => Link::fromTextAndUrl($this->t('View modes'), Url::fromRoute('entity.entity_view_mode.collection'))->toString(),
        '@view_modes_link' => Link::fromTextAndUrl($this->t('photos custom display settings'), Url::fromRoute('entity.entity_view_display.photos_image.default'))->toString(),
      ]),
    ];
    $viewModeOptions = $this->entityDisplayRepository->getViewModeOptionsByBundle('photos_image', 'photos_image');
    $form['display']['view_mode_rearrange_album_page'] = [
      '#title' => $this->t('Rearrange albums page'),
      '#type' => 'select',
      '#options' => $viewModeOptions,
      '#default_value' => $config->get('view_mode_rearrange_album_page') ?: 'sort',
    ];
    $form['display']['view_mode_rearrange_image_page'] = [
      '#title' => $this->t('Rearrange images page'),
      '#type' => 'select',
      '#options' => $viewModeOptions,
      '#default_value' => $config->get('view_mode_rearrange_image_page') ?: 'sort',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // ...
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('photos.settings')
      ->set('photos_access_photos', $form_state->getValue('photos_access_photos'))
      ->set('view_mode_rearrange_album_page', $form_state->getValue('view_mode_rearrange_album_page'))
      ->set('view_mode_rearrange_image_page', $form_state->getValue('view_mode_rearrange_image_page'))
      ->save();

    // Set warning if private file path is not set.
    if (!PrivateStream::basePath() && $form_state->getValue('photos_access_photos')) {
      $this->messenger->addWarning($this->t('Warning: image files can
        still be accessed by visiting the direct URL. For better security, ask
        your website admin to setup a private file path.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'photos.settings',
    ];
  }

}
