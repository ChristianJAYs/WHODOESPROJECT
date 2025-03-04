
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Photo Albums is a module that provides everything you need to create
albums on your Drupal 8 website. It will create a content type, media
type, image styles, album index page and album pages.

It will also allo wyou to password protect individual albums.


FEATURES:
---------

The Photo Albums module:

* Provide the "photo album" content type and all the necessary fields and
  settings.
* Enables password protecting an album by checking a box (also provides a
  reset password facility).
* Provides media type for photo album images.
* Provides default image styles for album index pages and album pages.


REQUIREMENTS
------------

This module has the following dependencies:

* menu_ui (core)
* node (core)
* user (core)
* media (core)
* media_library (core)
* crop
* pathauto
* field_group
* focal_point
* colorbox


INSTALLATION
------------

1. Install the module as normal, see link for instructions.
   Link: https://www.drupal.org/documentation/install/modules-themes/modules-8


CONFIGURATION
-------------

The only configuration needed for this module is to add an encrytion
key and method to your settings.php file. You can choose any encryption
method supported by OpenSSL (see below for a list of supported methods - 
this list is subject to change).

To add these values to your settings.php file, add them as follows:

$settings['two_way_hashing_key'] = '3F4428472B4B6EE0655368566D597133C43677397A576826452948404D635166';
$settings['two_way_hashing_method'] = 'aes-256-ctr';

The values above are just examples, but you must make sure you match the
key length to the method chosen, in this case aes-256-ctr expects 64
hexadecimal characters.


MAINTAINERS
-----------

Current maintainers:

 * Scot Hubbard - https://www.drupal.org/u/scothubbard

Requires - Drupal 8
License - GPL (see LICENSE)


SUPPORTED ENCRYPTION METHODS
----------------------------

AES-128-CBC
id-aes128-CCM
AES-128-CFB
AES-128-CFB1
AES-128-CFB8
AES-128-CTR
AES-128-ECB
id-aes128-GCM
AES-128-OCB
AES-128-OFB
AES-128-XTS
AES-192-CBC
id-aes192-CCM
AES-192-CFB
AES-192-CFB1
AES-192-CFB8
AES-192-CTR
AES-192-ECB
id-aes192-GCM
AES-192-OCB
AES-192-OFB
AES-256-CBC
id-aes256-CCM
AES-256-CFB
AES-256-CFB1
AES-256-CFB8
AES-256-CTR
AES-256-ECB
id-aes256-GCM
AES-256-OCB
AES-256-OFB
AES-256-XTS
aes128 => AES-128-CBC
aes128-wrap => id-aes128-wrap
aes192 => AES-192-CBC
aes192-wrap => id-aes192-wrap
aes256 => AES-256-CBC
aes256-wrap => id-aes256-wrap
ARIA-128-CBC
ARIA-128-CCM
ARIA-128-CFB
ARIA-128-CFB1
ARIA-128-CFB8
ARIA-128-CTR
ARIA-128-ECB
ARIA-128-GCM
ARIA-128-OFB
ARIA-192-CBC
ARIA-192-CCM
ARIA-192-CFB
ARIA-192-CFB1
ARIA-192-CFB8
ARIA-192-CTR
ARIA-192-ECB
ARIA-192-GCM
ARIA-192-OFB
ARIA-256-CBC
ARIA-256-CCM
ARIA-256-CFB
ARIA-256-CFB1
ARIA-256-CFB8
ARIA-256-CTR
ARIA-256-ECB
ARIA-256-GCM
ARIA-256-OFB
aria128 => ARIA-128-CBC
aria192 => ARIA-192-CBC
aria256 => ARIA-256-CBC
bf => BF-CBC
BF-CBC
BF-CFB
BF-ECB
BF-OFB
blowfish => BF-CBC
CAMELLIA-128-CBC
CAMELLIA-128-CFB
CAMELLIA-128-CFB1
CAMELLIA-128-CFB8
CAMELLIA-128-CTR
CAMELLIA-128-ECB
CAMELLIA-128-OFB
CAMELLIA-192-CBC
CAMELLIA-192-CFB
CAMELLIA-192-CFB1
CAMELLIA-192-CFB8
CAMELLIA-192-CTR
CAMELLIA-192-ECB
CAMELLIA-192-OFB
CAMELLIA-256-CBC
CAMELLIA-256-CFB
CAMELLIA-256-CFB1
CAMELLIA-256-CFB8
CAMELLIA-256-CTR
CAMELLIA-256-ECB
CAMELLIA-256-OFB
camellia128 => CAMELLIA-128-CBC
camellia192 => CAMELLIA-192-CBC
camellia256 => CAMELLIA-256-CBC
cast => CAST5-CBC
cast-cbc => CAST5-CBC
CAST5-CBC
CAST5-CFB
CAST5-ECB
CAST5-OFB
ChaCha20
ChaCha20-Poly1305
des => DES-CBC
DES-CBC
DES-CFB
DES-CFB1
DES-CFB8
DES-ECB
DES-EDE
DES-EDE-CBC
DES-EDE-CFB
des-ede-ecb => DES-EDE
DES-EDE-OFB
DES-EDE3
DES-EDE3-CBC
DES-EDE3-CFB
DES-EDE3-CFB1
DES-EDE3-CFB8
des-ede3-ecb => DES-EDE3
DES-EDE3-OFB
DES-OFB
des3 => DES-EDE3-CBC
des3-wrap => id-smime-alg-CMS3DESwrap
desx => DESX-CBC
DESX-CBC
id-aes128-CCM
id-aes128-GCM
id-aes128-wrap
id-aes128-wrap-pad
id-aes192-CCM
id-aes192-GCM
id-aes192-wrap
id-aes192-wrap-pad
id-aes256-CCM
id-aes256-GCM
id-aes256-wrap
id-aes256-wrap-pad
id-smime-alg-CMS3DESwrap
rc2 => RC2-CBC
rc2-128 => RC2-CBC
rc2-40 => RC2-40-CBC
RC2-40-CBC
rc2-64 => RC2-64-CBC
RC2-64-CBC
RC2-CBC
RC2-CFB
RC2-ECB
RC2-OFB
RC4
RC4-40
RC4-HMAC-MD5
seed => SEED-CBC
SEED-CBC
SEED-CFB
SEED-ECB
SEED-OFB
sm4 => SM4-CBC
SM4-CBC
SM4-CFB
SM4-CTR
SM4-ECB
SM4-OFB
