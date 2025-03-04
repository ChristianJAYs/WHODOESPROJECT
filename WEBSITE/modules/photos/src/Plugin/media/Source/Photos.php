<?php

namespace Drupal\photos\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;

/**
 * Photos album media source.
 *
 * @see \Drupal\photos\PhotosAlbum
 *
 * @MediaSource(
 *   id = "photos",
 *   label = @Translation("Photos"),
 *   description = @Translation("Use photos albums as reusable media."),
 *   allowed_field_types = {"entity_reference"},
 *   default_thumbnail_filename = "no-thumbnail.png"
 * )
 */
class Photos extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    // @todo check default album settings?
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    // @todo default thumbnail?
    $source_field = $this->configuration['source_field'];

    // If the source field is not required, it may be empty.
    if (!$source_field) {
      return parent::getMetadata($media, $attribute_name);
    }
    switch ($attribute_name) {
      case 'default_name':
        $nid = $media->get($source_field)->target_id;
        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        if ($node) {
          return $node->getTitle();
        }
        return parent::getMetadata($media, $attribute_name);

      case 'thumbnail_uri':
        // @todo check cover_id?
      default:
        return parent::getMetadata($media, $attribute_name);
    }
  }

}
