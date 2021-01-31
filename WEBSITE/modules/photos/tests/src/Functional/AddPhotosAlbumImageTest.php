<?php

namespace Drupal\Tests\photos\Functional;

use Drupal\node\Entity\Node;
use Drupal\photos\PhotosAlbum;
use Drupal\Tests\BrowserTestBase;

/**
 * Test creating a new album, adding an image and updating the image.
 *
 * @group photos
 */
class AddPhotosAlbumImageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'file',
    'image',
    'comment',
    'photos',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user account for testing.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create user with permissions to edit own photos.
    $this->account = $this->drupalCreateUser([
      'view photo',
      'create photo',
      'edit own photo',
      'delete own photo',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Test adding an image to an album and accessing the image edit page.
   */
  public function testAccessPhotosImageEditForm() {

    // Create a test album node.
    $albumTitle = $this->randomMachineName();
    $album = Node::create([
      'type' => 'photos',
      'title' => $albumTitle,
    ]);
    $album->save();

    // Get test image file.
    $testPhotoUri = drupal_get_path('module', 'photos') . '/tests/images/photos-test-picture.jpg';
    $fileSystem = \Drupal::service('file_system');

    // Post image upload form.
    $edit = [
      'files[images_0]' => $fileSystem->realpath($testPhotoUri),
      'title_0' => 'Test photo title',
      'des_0' => 'Test photos description',
    ];
    $this->drupalGet('node/' . $album->id() . '/photos');
    $this->submitForm($edit, 'Confirm upload');

    // Get album images.
    $photosAlbum = new PhotosAlbum($album->id());
    $albumImages = $photosAlbum->getImages(1);
    $photosImage = $albumImages[0]['photos_image'];
    $this->assertEquals($edit['title_0'], $photosImage->getTitle());

    // Access image edit page.
    $this->drupalGet('photos/' . $photosImage->getAlbumId() . '/' . $photosImage->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Post image edit form.
    $edit = [
      'title[0][value]' => 'Test new title',
    ];
    $this->submitForm($edit, 'Save');

    // Confirm that image title has been updated.
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('photos_image');
    // Must explicitly clear cache to see new title.
    // @see https://www.drupal.org/project/drupal/issues/3040878
    $storage->resetCache([$photosImage->id()]);
    $photosImage = $storage->load($photosImage->id());
    $this->assertEquals($edit['title[0][value]'], $photosImage->getTitle());

    // Test recent albums content overview.
    $this->drupalGet('photos');
    $this->assertResponse(200);
    $this->assertText($albumTitle);

  }

}
