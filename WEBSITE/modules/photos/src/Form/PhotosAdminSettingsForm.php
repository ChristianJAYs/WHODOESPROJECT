<?php

namespace Drupal\photos\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\photos\PhotosAlbum;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class PhotosAdminSettingsForm extends ConfigFormBase {

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

    // Check if colorbox is enabled.
    $colorbox = FALSE;
    if ($this->moduleHandler->moduleExists('colorbox')) {
      $colorbox = TRUE;
    }

    // Vertical tabs group.
    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Settings'),
    ];

    // Upload settings.
    $form['upload'] = [
      '#title' => $this->t('Upload'),
      '#type' => 'details',
      '#group' => 'settings',
    ];
    // @todo add option to disable multi-upload form.
    // Classic upload form settings.
    $num_options = [
      1 => 1,
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      6 => 6,
      7 => 7,
      8 => 8,
      9 => 9,
      10 => 10,
    ];
    // @todo this feels dated. Add an unlimited option with add more button?
    $form['upload']['photos_num'] = [
      '#type' => 'select',
      '#title' => $this->t('Classic form'),
      '#default_value' => $config->get('photos_num'),
      '#options' => $num_options,
      '#description' => $this->t('Maximum number of upload fields on the classic
        upload form.'),
    ];

    // Plupload integration settings.
    $module_plupload_exists = $this->moduleHandler->moduleExists('plupload');
    if ($module_plupload_exists) {
      $form['upload']['photos_plupload_status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Plupoad for file uploads'),
        '#default_value' => $config->get('photos_plupload_status'),
      ];
    }
    else {
      $config->set('photos_plupload_status', 0)->save();
      $form['upload']['photos_plupload_status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Plupoad for file uploads'),
        '#disabled' => TRUE,
        '#description' => $this->t('To enable multiuploads and drag&amp;drop upload features, download and install the @link module', [
          '@link' => Link::fromTextAndUrl($this->t('Plupload integration'), Url::fromUri('http://drupal.org/project/plupload'))->toString(),
        ]),
      ];
    }
    // Multi upload form field selection.
    $fields = $this->entityFieldManager->getFieldDefinitions('photos_image', 'photos_image');
    $fieldOptions = [];
    foreach ($fields as $key => $fieldData) {
      $fieldType = $fieldData->getType();
      // Check image fields.
      if ($fieldType == 'image') {
        $fieldOptions[$key] = $this->t('Image: :fieldKey', [
          ':fieldKey' => $key,
        ]);
      }
      // Check media fields.
      if ($fieldType == 'entity_reference') {
        // Check if media field allows image.
        $fieldSettings = $fieldData->getSettings();
        if ($fieldSettings['handler'] == 'default:media'
          && isset($fieldSettings['handler_settings']['target_bundles'])
          && !empty($fieldSettings['handler_settings']['target_bundles'])) {
          // Check all media bundle fields for image.
          foreach ($fieldSettings['handler_settings']['target_bundles'] as $mediaBundle) {
            $mediaFields = $this->entityFieldManager->getFieldDefinitions('media', $mediaBundle);
            foreach ($mediaFields as $mediaFieldKey => $mediaFieldData) {
              $fieldType = $mediaFieldData->getType();
              // Check all image fields in media bundle.
              if ($fieldType == 'image' && $mediaFieldKey != 'thumbnail') {
                $fieldOptions[$key . ':' . $mediaFieldKey . ':' . $mediaBundle] = $this->t('Media: :fieldKey::mediaFieldKey::mediaBundle', [
                  ':fieldKey' => $key,
                  ':mediaFieldKey' => $mediaFieldKey,
                  ':mediaBundle' => $mediaBundle,
                ]);
              }
            }
          }
        }
      }
    }
    if (!empty($fieldOptions)) {
      $form['upload']['multi_upload_default_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Default multi-upload field'),
        '#description' => $this->t('The default value is field_image.'),
        '#options' => $fieldOptions,
        '#default_value' => $config->get('multi_upload_default_field'),
      ];
    }
    $form['upload']['photos_size_max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum image resolution'),
      '#default_value' => $config->get('photos_size_max'),
      '#description' => $this->t('The maximum image resolution example:
        800x600. If an image toolkit is available the image will be scaled to
        fit within the desired maximum dimensions. Make sure this size is larger
        than any image styles used. Leave blank for no restrictions.'),
      '#size' => '40',
    ];
    $form['upload']['photos_upzip'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow zip upload'),
      '#default_value' => $config->get('photos_upzip') ?: 0,
      '#description' => $this->t('Users will be allowed to upload images
        compressed into a zip folder.'),
      '#options' => [
        $this->t('Disabled'),
        $this->t('Enabled'),
      ],
    ];
    $form['upload']['photos_clean_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clean image titles'),
      '#description' => $this->t('This will remove the file extension and
        replace dashes and underscores with spaces when the filename is used for
        the image title.'),
      '#default_value' => $config->get('photos_clean_title'),
    ];

    // Album limit per role.
    $form['num'] = [
      '#title' => $this->t('Album limit'),
      '#type' => 'details',
      '#description' => $this->t('The number of albums a user is allowed to
        create. User 1 is not limited.'),
      '#tree' => TRUE,
      '#group' => 'settings',
    ];
    // @todo test if administrator is not limited?
    $roles = user_roles(TRUE);
    foreach ($roles as $key => $role) {
      $form['num']['photos_pnum_' . $key] = [
        '#type' => 'number',
        '#title' => $role->label(),
        '#required' => TRUE,
        '#default_value' => $config->get('photos_pnum_' . $key) ? $config->get('photos_pnum_' . $key) : 20,
        '#min' => 1,
        '#step' => 1,
        '#prefix' => '<div class="photos-admin-inline">',
        '#suffix' => '</div>',
        '#size' => 10,
      ];
    }

    // Count settings.
    $form['count'] = [
      '#title' => $this->t('Statistics'),
      '#type' => 'details',
      '#group' => 'settings',
    ];
    $form['count']['photos_image_count'] = [
      '#type' => 'radios',
      '#title' => $this->t('Count image views'),
      '#default_value' => $config->get('photos_image_count') ?: 0,
      '#description' => $this->t('Increment a counter each time image is viewed.'),
      '#options' => [$this->t('Enabled'), $this->t('Disabled')],
    ];
    $form['count']['photos_user_count_cron'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image quantity statistics'),
      '#default_value' => $config->get('photos_user_count_cron') ?: 0,
      '#description' => $this->t('Users/Site images and albums quantity statistics.'),
      '#options' => [$this->t('Update count when cron runs (affect the count update).'), $this->t('Update count when image is uploaded (affect the upload speed).')],
    ];

    // Legacy view mode and other advanced settings.
    $legacyViewModeDefault = $config->get('photos_legacy_view_mode');
    $advancedDescription = $this->t('Warning: advanced settings can
      dramatically change the way all photos content appears on this site.
      Please test thoroughly before changing these settings on a live site.
      Site cache might need to be cleared after changing these settings.');
    $form['advanced'] = [
      '#type' => 'details',
      '#group' => 'settings',
      '#title' => $this->t('Advanced'),
      '#description' => $advancedDescription,
      '#open' => $legacyViewModeDefault,
    ];
    if ($this->moduleHandler->moduleExists('views')) {
      // @todo how do we only get views that are type photos?
      $displays = Views::getViewsAsOptions(FALSE, 'enabled', NULL, TRUE, TRUE);
      // @todo add template option instead of views?
      $form['advanced']['node_field_album_photos_list_view'] = [
        '#title' => $this->t('Album photos image list view'),
        '#type' => 'select',
        '#options' => $displays,
        '#description' => $this->t('This view is embedded in the "Album photos" field that appears on the @manage_display_link content type.', [
          '@manage_display_link' => Link::fromTextAndUrl($this->t('photo album'), Url::fromRoute('entity.entity_view_display.node.default', [
            'node_type' => 'photos',
          ], [
            'attributes' => [
              'target' => '_blank',
            ],
          ]))->toString(),
        ]),
        '#default_value' => $config->get('node_field_album_photos_list_view') ?: 'photos_album:block_1',
      ];
      $overrideOptions = ['' => 'Photo album node'];
      $overrideOptions += $displays;
      $form['advanced']['album_link_override'] = [
        '#title' => $this->t('Override default album link'),
        '#type' => 'select',
        '#options' => $overrideOptions,
        '#description' => $this->t('The default album cover link. Currently only views with %node as a contextual argument are supported here.'),
        '#default_value' => $config->get('album_link_override') ?: '',
      ];
      $overrideOptions = ['' => ''];
      $overrideOptions += $displays;
      $form['advanced']['user_albums_link_override'] = [
        '#title' => $this->t('Override default user albums link'),
        '#type' => 'select',
        '#options' => $overrideOptions,
        '#description' => $this->t('The default user albums link found on the user profile page.'),
        '#default_value' => $config->get('user_albums_link_override') ?: '',
      ];
      $form['advanced']['user_images_link_override'] = [
        '#title' => $this->t('Override default user images link'),
        '#type' => 'select',
        '#options' => $overrideOptions,
        '#description' => $this->t('The default user images link found on the user profile page.'),
        '#default_value' => $config->get('user_images_link_override') ?: '',
      ];
    }
    // Legacy view mode.
    $form['advanced']['legacy'] = [
      '#type' => 'details',
      '#open' => $legacyViewModeDefault,
      '#title' => $this->t('Legacy settings'),
      '#description' => $this->t('Legacy view mode is to help
        preserve image and album layouts that were configured pre 8.x-5.x. Only
        use this setting if you need to. It will be enabled by default for sites
        upgrading or migrating from older versions of this module. It is safe
        to disable if you have common site-wide settings for image sizes and
        display settings for all albums and images. It is now recommended to use
        the custom display settings for view modes found in the "Manage display"
        tab.'),
    ];
    $form['advanced']['legacy']['photos_legacy_view_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable legacy view mode'),
      '#description' => $this->t('Changing this setting will clear the
        site cache when the form is saved.'),
      '#default_value' => $legacyViewModeDefault,
    ];

    // Thumb settings.
    if ($size = \Drupal::config('photos.settings')->get('photos_size')) {
      $num = (count($size) + 3);
      $sizes = [];
      foreach ($size as $style => $label) {
        $sizes[] = [
          'style' => $style,
          'label' => $label,
        ];
      }
      $size = $sizes;
    }
    else {
      // @todo remove else or use $size_options?
      $num = 3;
      $size = [
        [
          'style' => 'medium',
          'label' => 'Medium',
        ],
        [
          'style' => 'large',
          'label' => 'Large',
        ],
        [
          'style' => 'thumbnail',
          'label' => 'Thumbnail',
        ],
      ];
    }
    $form['advanced']['legacy']['photos_thumb_count'] = [
      '#type' => 'hidden',
      '#default_value' => $num,
    ];
    $form['advanced']['legacy']['thumb'] = [
      '#title' => $this->t('Image sizes'),
      '#type' => 'details',
      '#description' => $this->t('Default image sizes. Note: if an image style is deleted after it has been in use for some
        time that may result in broken external image links.'),
    ];
    $thumb_options = image_style_options();
    if (empty($thumb_options)) {
      $image_style_link = Link::fromTextAndUrl($this->t('add image styles'), Url::fromRoute('entity.image_style.collection'))->toString();
      $form['advanced']['legacy']['thumb']['image_style'] = [
        '#markup' => '<p>One or more image styles required: ' . $image_style_link . '.</p>',
      ];
    }
    else {
      $form['advanced']['legacy']['thumb']['photos_pager_imagesize'] = [
        '#type' => 'select',
        '#title' => 'Pager size',
        '#default_value' => $config->get('photos_pager_imagesize'),
        '#description' => $this->t('Default pager block image style.'),
        '#options' => $thumb_options,
        '#required' => TRUE,
      ];
      $form['advanced']['legacy']['thumb']['photos_cover_imagesize'] = [
        '#type' => 'select',
        '#title' => 'Cover size',
        '#default_value' => $config->get('photos_cover_imagesize'),
        '#description' => $this->t('Default album cover image style.'),
        '#options' => $thumb_options,
        '#required' => TRUE,
      ];
      $form['advanced']['legacy']['thumb']['photos_name_0'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => isset($size[0]['label']) ? $size[0]['label'] : NULL,
        '#size' => '10',
        '#required' => TRUE,
        '#prefix' => '<div class="photos-admin-inline">',
      ];

      $form['advanced']['legacy']['thumb']['photos_size_0'] = [
        '#type' => 'select',
        '#title' => 'Thumb size',
        '#default_value' => isset($size[0]['style']) ? $size[0]['style'] : NULL,
        '#options' => $thumb_options,
        '#required' => TRUE,
        '#suffix' => '</div>',
      ];
      $empty_option = ['' => ''];
      $thumb_options = $empty_option + $thumb_options;
      $form['advanced']['legacy']['thumb']['additional_sizes'] = [
        '#markup' => '<p>Additional image sizes ' . Link::fromTextAndUrl($this->t('add more image styles'), Url::fromRoute('entity.image_style.collection'))->toString() . '.</p>',
      ];

      $additional_sizes = 0;
      for ($i = 1; $i < $num; $i++) {
        $form['advanced']['legacy']['thumb']['photos_name_' . $i] = [
          '#type' => 'textfield',
          '#title' => $this->t('Name'),
          '#default_value' => isset($size[$i]['label']) ? $size[$i]['label'] : NULL,
          '#size' => '10',
          '#prefix' => '<div class="photos-admin-inline">',
        ];
        $form['advanced']['legacy']['thumb']['photos_size_' . $i] = [
          '#type' => 'select',
          '#title' => $this->t('Size'),
          '#default_value' => isset($size[$i]['style']) ? $size[$i]['style'] : NULL,
          '#options' => $thumb_options,
          '#suffix' => '</div>',
        ];
        $additional_sizes = $i;
      }

      $form['advanced']['legacy']['thumb']['photos_additional_sizes'] = [
        '#type' => 'hidden',
        '#value' => $additional_sizes,
      ];
    }
    // End thumb settings.
    // Display settings.
    $form['advanced']['legacy']['display'] = [
      '#title' => $this->t('Display settings'),
      '#type' => 'details',
    ];

    $form['advanced']['legacy']['display']['global'] = [
      '#type' => 'details',
      '#title' => $this->t('Global Settings'),
      '#description' => $this->t('Albums basic display settings'),
    ];
    $form['advanced']['legacy']['display']['page'] = [
      '#type' => 'details',
      '#title' => $this->t('Page Settings'),
      '#description' => $this->t('Page (e.g: node/[nid]) display settings'),
      '#prefix' => '<div id="photos-form-page">',
      '#suffix' => '</div>',
    ];
    $form['advanced']['legacy']['display']['teaser'] = [
      '#type' => 'details',
      '#title' => $this->t('Teaser Settings'),
      '#description' => $this->t('Teaser display settings'),
      '#prefix' => '<div id="photos-form-teaser">',
      '#suffix' => '</div>',
    ];
    $form['advanced']['legacy']['display']['global']['photos_album_display_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Album display'),
      '#required' => TRUE,
      '#default_value' => $config->get('photos_album_display_type') ?: 'list',
      '#options' => [
        'list' => $this->t('List'),
        'grid' => $this->t('Grid'),
      ],
    ];
    $form['advanced']['legacy']['display']['global']['photos_display_viewpager'] = [
      '#type' => 'number',
      '#default_value' => $config->get('photos_display_viewpager'),
      '#title' => $this->t('How many images show in each page?'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
    ];
    $form['advanced']['legacy']['display']['global']['photos_album_column_count'] = [
      '#type' => 'number',
      '#default_value' => $config->get('photos_album_column_count') ?: 2,
      '#title' => $this->t('Number of columns'),
      '#description' => $this->t('When using album grid view.'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
    ];
    $form['advanced']['legacy']['display']['global']['photos_display_imageorder'] = [
      '#type' => 'select',
      '#title' => $this->t('Image display order'),
      '#required' => TRUE,
      '#default_value' => $config->get('photos_display_imageorder'),
      '#options' => PhotosAlbum::orderLabels(),
    ];
    $list_imagesize = $config->get('photos_display_list_imagesize');
    $view_imagesize = $config->get('photos_display_view_imagesize');
    $size_options = \Drupal::config('photos.settings')->get('photos_size');;
    $form['advanced']['legacy']['display']['global']['photos_display_list_imagesize'] = [
      '#type' => 'select',
      '#title' => $this->t('Image display size (list)'),
      '#required' => TRUE,
      '#default_value' => $list_imagesize,
      '#description' => $this->t('Displayed in the list (e.g: photos/[nid]) of image size.'),
      '#options' => $size_options,
    ];
    $form['advanced']['legacy']['display']['global']['photos_display_view_imagesize'] = [
      '#type' => 'select',
      '#title' => $this->t('Image display size (page)'),
      '#required' => TRUE,
      '#default_value' => $view_imagesize,
      '#description' => $this->t('Displayed in the page (e.g: photos/{node}/{photos_image}) of image size.'),
      '#options' => $size_options,
    ];
    $form['advanced']['legacy']['display']['global']['photos_display_user'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to modify this setting when they create a new album.'),
      '#default_value' => $config->get('photos_display_user') ?: 0,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
    ];
    if ($colorbox) {
      $form['advanced']['legacy']['display']['global']['photos_display_colorbox_max_height'] = [
        '#type' => 'number',
        '#default_value' => $config->get('photos_display_colorbox_max_height') ?: 100,
        '#title' => $this->t('Colorbox gallery maxHeight percentage.'),
        '#required' => TRUE,
        '#min' => 1,
        '#step' => 1,
      ];
      $form['advanced']['legacy']['display']['global']['photos_display_colorbox_max_width'] = [
        '#type' => 'number',
        '#default_value' => $config->get('photos_display_colorbox_max_width') ?: 50,
        '#title' => $this->t('Colorbox gallery maxWidth percentage.'),
        '#required' => TRUE,
        '#min' => 1,
        '#step' => 1,
      ];
    }
    $display_options = [
      $this->t('Do not display'),
      $this->t('Display cover'),
      $this->t('Display thumbnails'),
    ];
    if ($colorbox) {
      $display_options[3] = $this->t('Cover with colorbox gallery');
    }
    $form['advanced']['legacy']['display']['page']['photos_display_page_display'] = [
      '#type' => 'radios',
      '#default_value' => $config->get('photos_display_page_display'),
      '#title' => $this->t('Display setting'),
      '#required' => TRUE,
      '#options' => $display_options,
    ];
    $form['advanced']['legacy']['display']['page']['photos_display_full_viewnum'] = [
      '#type' => 'number',
      '#default_value' => $config->get('photos_display_full_viewnum'),
      '#title' => $this->t('Display quantity'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
      '#prefix' => '<div class="photos-form-count">',
    ];
    $form['advanced']['legacy']['display']['page']['photos_display_full_imagesize'] = [
      '#type' => 'select',
      '#title' => $this->t('Image display size'),
      '#required' => TRUE,
      '#default_value' => $config->get('photos_display_full_imagesize'),
      '#options' => $size_options,
      '#suffix' => '</div>',
    ];
    $form['advanced']['legacy']['display']['page']['photos_display_page_user'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to modify this setting when they create a new album.'),
      '#default_value' => $config->get('photos_display_page_user') ?: 0,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
    ];
    $form['advanced']['legacy']['display']['teaser']['photos_display_teaser_display'] = [
      '#type' => 'radios',
      '#default_value' => $config->get('photos_display_teaser_display'),
      '#title' => $this->t('Display setting'),
      '#required' => TRUE,
      '#options' => $display_options,
    ];
    $form['advanced']['legacy']['display']['teaser']['photos_display_teaser_viewnum'] = [
      '#type' => 'number',
      '#default_value' => $config->get('photos_display_teaser_viewnum'),
      '#title' => $this->t('Display quantity'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
      '#prefix' => '<div class="photos-form-count">',
    ];
    $form['advanced']['legacy']['display']['teaser']['photos_display_teaser_imagesize'] = [
      '#type' => 'select',
      '#title' => $this->t('Image display size'),
      '#required' => TRUE,
      '#default_value' => $config->get('photos_display_teaser_imagesize'),
      '#options' => $size_options,
      '#suffix' => '</div>',
    ];
    $form['advanced']['legacy']['display']['teaser']['photos_display_teaser_user'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow users to modify this setting when they create a new album.'),
      '#default_value' => $config->get('photos_display_teaser_user') ?: 0,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
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
    // Build $photos_size array.
    $size = [];
    for ($i = 0; $i < $form_state->getValue('photos_thumb_count'); $i++) {
      if ($form_state->getValue('photos_size_' . $i)) {
        $size[$form_state->getValue('photos_size_' . $i)] = $form_state->getValue('photos_name_' . $i);
      }
    }
    $photos_size = $size;

    // Set number of albums per role.
    $num = $form_state->getValue('num');
    foreach ($num as $rnum => $rcount) {
      $this->config('photos.settings')->set($rnum, $rcount);
    }

    // Check current legacy setting and clear cache if changed.
    $currentLegacySetting = $this->config('photos.settings')->get('photos_legacy_view_mode');
    // Check current album photos image list view.
    $currentImageListView = $this->config('photos.settings')->get('node_field_album_photos_list_view');

    $this->config('photos.settings')
      ->set('album_link_override', $form_state->getValue('album_link_override'))
      ->set('multi_upload_default_field', $form_state->getValue('multi_upload_default_field'))
      ->set('node_field_album_photos_list_view', $form_state->getValue('node_field_album_photos_list_view'))
      ->set('photos_additional_sizes', $form_state->getValue('photos_additional_sizes'))
      ->set('photos_album_column_count', $form_state->getValue('photos_album_column_count'))
      ->set('photos_album_display_type', $form_state->getValue('photos_album_display_type'))
      ->set('photos_cover_imagesize', $form_state->getValue('photos_cover_imagesize'))
      ->set('photos_display_colorbox_max_height', $form_state->getValue('photos_display_colorbox_max_height'))
      ->set('photos_display_colorbox_max_width', $form_state->getValue('photos_display_colorbox_max_width'))
      ->set('photos_display_full_imagesize', $form_state->getValue('photos_display_full_imagesize'))
      ->set('photos_display_full_viewnum', $form_state->getValue('photos_display_full_viewnum'))
      ->set('photos_display_imageorder', $form_state->getValue('photos_display_imageorder'))
      ->set('photos_display_list_imagesize', $form_state->getValue('photos_display_list_imagesize'))
      ->set('photos_display_page_display', $form_state->getValue('photos_display_page_display'))
      ->set('photos_display_page_user', $form_state->getValue('photos_display_page_user'))
      ->set('photos_display_teaser_display', $form_state->getValue('photos_display_teaser_display'))
      ->set('photos_display_teaser_imagesize', $form_state->getValue('photos_display_teaser_imagesize'))
      ->set('photos_display_teaser_user', $form_state->getValue('photos_display_teaser_user'))
      ->set('photos_display_teaser_viewnum', $form_state->getValue('photos_display_teaser_viewnum'))
      ->set('photos_display_user', $form_state->getValue('photos_display_user'))
      ->set('photos_display_view_imagesize', $form_state->getValue('photos_display_view_imagesize'))
      ->set('photos_display_viewpager', $form_state->getValue('photos_display_viewpager'))
      ->set('photos_image_count', $form_state->getValue('photos_image_count'))
      ->set('photos_legacy_view_mode', $form_state->getValue('photos_legacy_view_mode'))
      ->set('photos_num', $form_state->getValue('photos_num'))
      ->set('photos_pager_imagesize', $form_state->getValue('photos_pager_imagesize'))
      ->set('photos_plupload_status', $form_state->getValue('photos_plupload_status'))
      ->set('photos_size', $photos_size)
      ->set('photos_size_max', $form_state->getValue('photos_size_max'))
      ->set('photos_clean_title', $form_state->getValue('photos_clean_title'))
      ->set('photos_upzip', $form_state->getValue('photos_upzip'))
      ->set('photos_user_count_cron', $form_state->getValue('photos_user_count_cron'))
      ->set('user_albums_link_override', $form_state->getValue('user_albums_link_override'))
      ->set('user_images_link_override', $form_state->getValue('user_images_link_override'))
      ->save();

    if ($currentLegacySetting != $form_state->getValue('photos_legacy_view_mode')) {
      $this->messenger->addMessage($this->t('Cache cleared.'));
      drupal_flush_all_caches();
    }

    if ($currentImageListView != $form_state->getValue('node_field_album_photos_list_view')) {
      $this->messenger->addMessage($this->t('Views node_list and photos_image_list cache cleared.'));
      // Clear views cache.
      Cache::invalidateTags(['node_list', 'photos_image_list']);
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
