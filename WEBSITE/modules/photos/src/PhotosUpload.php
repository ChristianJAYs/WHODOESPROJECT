<?php

namespace Drupal\photos;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\photos\Entity\PhotosImage;

/**
 * Functions to help with uploading images to albums.
 */
class PhotosUpload implements PhotosUploadInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

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
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Creates a new AliasCleaner.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_manager, FileSystem $file_system, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, StreamWrapperManagerInterface $stream_wrapper_manager, TransliterationInterface $transliteration) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_manager;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanTitle($title = '') {
    if (\Drupal::config('photos.settings')->get('photos_clean_title')) {
      // Remove extension.
      $title = pathinfo($title, PATHINFO_FILENAME);
      // Replace dash and underscore with spaces.
      $title = preg_replace("/[\-_]/", " ", $title);
      // Trim leading and trailing spaces.
      $title = trim($title);
    }
    return $title;
  }

  /**
   * {@inheritdoc}
   */
  public function path($schemaType = 'default') {
    $path[] = 'photos';
    switch ($schemaType) {
      case 'private':
        $scheme = 'private';
        break;

      case 'public':
        $scheme = 'public';
        break;

      case 'default':
      default:
        $scheme = \Drupal::config('system.file')->get('default_scheme');
        break;
    }
    $dirs = [];
    $finalPath = FALSE;
    // Prepare directory.
    foreach ($path as $folder) {
      $dirs[] = $folder;
      $finalPath = $scheme . '://' . implode('/', $dirs);
      if (!$this->fileSystem->prepareDirectory($finalPath, FileSystemInterface::CREATE_DIRECTORY)) {
        return FALSE;
      }
    }
    if ($finalPath) {
      // Make sure the path does not end with a forward slash.
      $finalPath = rtrim($finalPath, '/');
    }
    return $finalPath;
  }

  /**
   * {@inheritdoc}
   */
  public function saveImage(File $file) {
    // @todo maybe pass file object and array of other vars.
    if ($file->id() && isset($file->album_id)) {
      $fid = $file->id();
      $album_id = $file->album_id;

      // Prep image title.
      if (isset($file->title) && !empty($file->title)) {
        $title = $file->title;
      }
      else {
        // Cleanup filename and use as title.
        $title = $this->cleanTitle($file->getFilename());
      }

      // Create photos_image entity.
      /* @var \Drupal\Core\Image\Image $image */
      $image = \Drupal::service('image.factory')->get($file->getFileUri());
      if ($image->isValid()) {
        $newPhotosImageEntity = [
          'album_id' => $file->album_id,
          'title' => $title,
          'weight' => isset($file->weight) ? $file->weight : 0,
          'description' => isset($file->des) ? $file->des : '',
        ];
        // Check if photos_image has default field_image.
        $uploadField = \Drupal::config('photos.settings')->get('multi_upload_default_field');
        $uploadFieldParts = explode(':', $uploadField);
        $field = isset($uploadFieldParts[0]) ? $uploadFieldParts[0] : 'field_image';
        $allBundleFields = \Drupal::service('entity_field.manager')->getFieldDefinitions('photos_image', 'photos_image');
        if (isset($allBundleFields[$field])) {
          $fieldType = $allBundleFields[$field]->getType();
          if ($fieldType == 'image') {
            $newPhotosImageEntity[$field] = [
              'target_id' => $fid,
              'alt' => $title,
              'title' => $title,
              'width' => $image->getWidth(),
              'height' => $image->getHeight(),
            ];
          }
          else {
            // Check media fields.
            if ($fieldType == 'entity_reference') {
              $mediaField = isset($uploadFieldParts[1]) ? $uploadFieldParts[1] : '';
              $mediaBundle = isset($uploadFieldParts[2]) ? $uploadFieldParts[2] : '';
              if ($mediaField && $mediaBundle) {
                // Create new media entity.
                $values = [
                  'bundle' => $mediaBundle,
                  'uid' => \Drupal::currentUser()->id(),
                ];
                $values[$mediaField] = [
                  'target_id' => $file->id(),
                ];
                $media = Media::create($values);
                // @todo media name?
                $media->setName('Photo ' . $file->id())->setPublished(TRUE)->save();
                // Set photos_image media reference field.
                $newPhotosImageEntity[$field] = [
                  'target_id' => $media->id(),
                ];
              }
            }
          }
        }
        $photosImage = PhotosImage::create($newPhotosImageEntity);
        try {
          $photosImage->save();
          if ($photosImage && $photosImage->id()) {
            if (\Drupal::config('photos.settings')->get('photos_user_count_cron')) {
              $user = \Drupal::currentUser();
              PhotosAlbum::setCount('user_image', ($photosImage->getOwnerId() ? $photosImage->getOwnerId() : $user->id()));
              PhotosAlbum::setCount('node_album', $file->album_id);
            }
            // Save file and add file usage.
            $file_usage = \Drupal::service('file.usage');
            $file_usage->add($file, 'photos', 'node', $album_id);
            // Check admin setting for maximum image resolution.
            if ($photos_size_max = \Drupal::config('photos.settings')->get('photos_size_max')) {
              // Will scale image if needed.
              file_validate_image_resolution($file, $photos_size_max);
            }
            return TRUE;
          }
        }
        catch (EntityStorageException $e) {
          watchdog_exception('photos', $e);
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function saveExistingMedia($mediaId, $albumId) {
    /* @var \Drupal\media\MediaInterface $mediaItem */
    $mediaItem = NULL;
    try {
      $mediaItem = $this->entityTypeManager->getStorage('media')
        ->load($mediaId);
    }
    catch (InvalidPluginDefinitionException $e) {
    }
    catch (PluginNotFoundException $e) {
    }
    if ($mediaItem) {
      $newPhotosImageEntity = [
        'album_id' => $albumId,
        'title' => $mediaItem->getName(),
      ];
      // Check default media field.
      $uploadField = \Drupal::config('photos.settings')->get('multi_upload_default_field');
      $uploadFieldParts = explode(':', $uploadField);
      $field = isset($uploadFieldParts[0]) ? $uploadFieldParts[0] : 'field_image';
      $allBundleFields = \Drupal::service('entity_field.manager')->getFieldDefinitions('photos_image', 'photos_image');
      if (isset($allBundleFields[$field])) {
        $fieldType = $allBundleFields[$field]->getType();
        if ($fieldType == 'entity_reference') {
          $mediaField = isset($uploadFieldParts[1]) ? $uploadFieldParts[1] : '';
          $mediaBundle = isset($uploadFieldParts[2]) ? $uploadFieldParts[2] : '';
          if ($mediaField && $mediaBundle) {
            // Set photos_image media reference field.
            $newPhotosImageEntity[$field] = [
              'target_id' => $mediaId,
            ];
          }
          // Save PhotosImage entity.
          $photosImage = PhotosImage::create($newPhotosImageEntity);
          try {
            $photosImage->save();
            if ($photosImage && $photosImage->id()) {
              if (\Drupal::config('photos.settings')->get('photos_user_count_cron')) {
                $user = \Drupal::currentUser();
                PhotosAlbum::setCount('user_image', ($photosImage->getOwnerId() ? $photosImage->getOwnerId() : $user->id()));
                PhotosAlbum::setCount('node_album', $albumId);
              }
              return TRUE;
            }
          }
          catch (EntityStorageException $e) {
            watchdog_exception('photos', $e);
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function unzip($source, $params, $scheme = 'default') {
    $file_count = 0;
    if (version_compare(PHP_VERSION, '5') >= 0) {
      if (!is_file($source)) {
        $this->messenger->addMessage($this->t('Compressed file does not exist, please check the path: @src', [
          '@src' => $source,
        ]));
        return 0;
      }
      $fileType = ['jpg', 'gif', 'png', 'jpeg', 'JPG', 'GIF', 'PNG', 'JPEG'];
      $zip = new \ZipArchive();
      // Get relative path.
      $default_scheme = \Drupal::config('system.file')->get('default_scheme');
      $relative_path = $this->fileSystem->realpath($default_scheme . "://") . '/';
      $source = str_replace($default_scheme . '://', $relative_path, $source);
      // Open zip archive.
      if ($zip->open($source) === TRUE) {
        for ($x = 0; $x < $zip->numFiles; ++$x) {
          $image = $zip->statIndex($x);
          $filename_parts = explode('.', $image['name']);
          $ext = end($filename_parts);
          if (in_array($ext, $fileType)) {
            $path = $this->fileSystem->createFilename($image['name'], $this->path($scheme));
            if ($temp_file = file_save_data($zip->getFromIndex($x), $path)) {
              // Update file values.
              $temp_file->album_id = $params['album_id'];
              $temp_file->nid = $params['nid'];
              // Use image file name as title.
              $temp_file->title = $image['name'];
              $temp_file->des = $params['des'];
              // Prepare file entity.
              $file = $temp_file;
              try {
                // Save image.
                $file->save();
                if ($this->saveImage($file)) {
                  $file_count++;
                }
              }
              catch (EntityStorageException $e) {
                watchdog_exception('photos', $e);
              }
            }
          }
        }
        $zip->close();
        // Delete zip file.
        $this->fileSystem->delete($source);
      }
      else {
        $this->messenger->addWarning($this->t('Compressed file does not exist, please try again: @src', [
          '@src' => $source,
        ]));
      }
    }

    return $file_count;
  }

}
