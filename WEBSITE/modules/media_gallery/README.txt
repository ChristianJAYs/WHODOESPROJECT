CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

A simple gallery of media.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/media_gallery

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/media_gallery


REQUIREMENTS
------------

 * media (drupal core)
 * media_library (drupal core)
 * colorbox - https://drupal.org/project/colorbox
 * photoswipe (optional) - https://drupal.org/project/photoswipe


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Extend and enable the module.
    2. Add new galleries: /admin/content/media-gallery
    3. Add own fields to galleries (if you want):
       /admin/structure/media-gallery/fields
    4. All galleries view: /galleries
    5. Change ColorBox to PhotoSwipe (if you want):
      * Add new media view mode:
        /admin/structure/display-modes/view/add/media
      * Enable new view mode and set formatter as PhotoSwipe:
        /admin/structure/media/manage/image/display/media_photoswipe
      * Set view mode as PhotoSwipe for the galleries:
        /admin/structure/media-gallery/display


MAINTAINERS
-----------

Current maintainers:
 * Andrei Ivnitskii - https://www.drupal.org/u/ivnish
