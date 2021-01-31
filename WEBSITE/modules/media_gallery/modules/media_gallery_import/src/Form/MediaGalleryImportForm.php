<?php

namespace Drupal\media_gallery_import\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Media Gallery Import Form.
 */
class MediaGalleryImportForm extends FormBase {

  /**
   * FileSystemInterface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystemInterface;

  /**
   * EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a new MediaGalleryImportForm object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system_interface
   *   FileSystemInterface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityTypeManagerInterface.
   */
  public function __construct(FileSystemInterface $file_system_interface, EntityTypeManagerInterface $entity_type_manager) {
    $this->fileSystemInterface = $file_system_interface;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_gallery_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Import media galleries'),
      '#description' => $this->t('Galleries will be imported from sites/default/files/media_gallery_export'),
    ];
    $form['fieldset']['submit'] = [
      '#value' => $this->t('Import'),
      '#type' => 'submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $import_dir = 'public://media_gallery_export';

    if (!$this->fileSystemInterface->prepareDirectory($import_dir)) {
      $this->messenger()->addMessage($this->t('Directory media_gallery_export does not exists! Aborting.'));
      return;
    }

    $import_dir_path = $this->fileSystemInterface->realpath($import_dir);
    $galleries_storage = $this->entityTypeManager->getStorage('media_gallery');

    $galleries_csv = fopen($import_dir_path . '/galleries.csv', 'r');

    while (($galleries_data = fgetcsv($galleries_csv, 0, ';')) !== FALSE) {
      $gallery_id = $galleries_data[0];
      $gallery_title = $galleries_data[1];
      $gallery_desc = $galleries_data[2];

      // Create Gallery if doesn't exists.
      $gallery = $galleries_storage->create([
        'title' => $gallery_title,
        'description' => $gallery_desc,
      ]);
      $gallery->save();

      $gallery_dir = $import_dir . '/' . $gallery_id;
      $gallery_dir_path = $this->fileSystemInterface->realpath($gallery_dir);
      $gallery_csv = fopen($gallery_dir_path . '/gallery.csv', 'r');
      $files_dir = 'public://' . date('Y') . '-' . date('m');

      if (!$this->fileSystemInterface->prepareDirectory($files_dir, FileSystemInterface::CREATE_DIRECTORY)) {
        $this->messenger()->addMessage($this->t('Can not create directory to import files! Aborting.'));
        return;
      }

      while (($gallery_data = fgetcsv($gallery_csv, 255, ';')) !== FALSE) {
        $filename = $gallery_data[0];
        $file_title = $gallery_data[1];
        $file_path = $gallery_dir . '/' . $filename;
        $file_new_path = $files_dir . '/' . $filename;
        copy($file_path, $file_new_path);

        // Create file entity.
        $file = File::create([
          'filename' => $filename,
          'uri' => $file_new_path,
          'status' => 1,
        ]);
        $file->setPermanent();
        $file->save();

        // Create media image entity.
        $image_media = Media::create([
          'bundle' => 'image',
          'field_media_image' => [
            'target_id' => $file->id(),
            'alt' => $file_title,
          ],
        ]);
        $image_media->save();

        // Attach media to gallery.
        $gallery->images[] = $image_media;
        $gallery->save();
      }

      fclose($gallery_csv);
    }

    fclose($galleries_csv);

    $this->messenger()->addMessage($this->t('Your galleries were imported!'));
  }

}
