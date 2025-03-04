<?php

namespace Drupal\photos\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\photos\PhotosAlbum;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to edit images.
 */
class PhotosImageAddForm extends ContentEntityForm {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, RendererInterface $renderer, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository, TimeInterface $time = NULL, AccountInterface $current_user) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Save changes.
    $photosImage = $this->entity;
    $photosImage->save();

    // Process image cropping data.
    $form_state_values = $form_state->getValues();
    $album_id = $form_state_values['album_id'][0]['target_id'];
    $uid = $form_state_values['uid'][0]['target_id'];

    // Clear image page cache.
    Cache::invalidateTags(['photos:image:' . $photosImage->id()]);
    if ($nid = $form_state->getValue('nid')) {
      // Clear album page and node cache.
      Cache::invalidateTags(['photos:album:' . $nid, 'node:' . $nid]);
    }

    if ($album_id) {
      // Update album count.
      PhotosAlbum::setCount('node_album', $album_id);
      // Clear album page and node cache.
      Cache::invalidateTags(['photos:album:' . $album_id, 'node:' . $album_id]);
    }

    if ($uid) {
      // Update user count.
      PhotosAlbum::setCount('user_image', $uid);
    }

    // @todo link album and display title?
    $this->messenger->addMessage($this->t('Entity saved to album.'));
  }

}
