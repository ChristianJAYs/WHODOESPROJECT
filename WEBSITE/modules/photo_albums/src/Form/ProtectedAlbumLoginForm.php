<?php

namespace Drupal\photo_albums\Form;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides login screen to access a protected photo album.
 */
class ProtectedAlbumLoginForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'protected_album_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $album_nid = $this->getRequest()->query->get('album_nid');
    if ($node = \Drupal::entityTypeManager()->getStorage('node')->load($album_nid)) {
      $album_name = $node->getTitle();
    }
    else {
      throw new AccessDeniedHttpException();
    }

    $form['intro'] = [
      '#markup' => $album_name,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Enter Password'),
      '#size' => 20,
      '#required' => TRUE,
    ];

    $form['album_nid'] = [
      '#type' => 'hidden',
      '#value' => $album_nid,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('View Album'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get the nid of the album being accessed.
    $album_nid = $form_state->getValue('album_nid');

    // Get the password record for the album node.
    $pass = \Drupal::database()->select('photo_albums_protected', 'p')
      ->fields('p', ['pass'])
      ->condition('nid', $album_nid, '=')
      ->execute()
      ->fetchField();

    // Get the password entered by the user.
    $supplied_pass = $form_state->getValue('password');

    // Get the two way hashing service.
    $tw_hash = \Drupal::service('photo_albums.twowayhash');

    // Use the service to check the supplied password
    // against the stored hash.
    if (!$tw_hash->check($supplied_pass, $pass)) {
      $form_state->setErrorByName('password', $this->t('You have entered an incorrect password. Please try again.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the nid for the album being viewed and the password
    // entered by the user.
    $album_nid = $form_state->getValue('album_nid');
    $supplied_pass = $form_state->getValue('password');

    // Get the encypted password and store it in a cookie
    // we do this so that in future we compare the stored
    // hash against the DB hash to invalidate the cookie
    // if the password is changed or the encryption key/
    // method is changed
    // get the password record for the album node.
    $pass = \Drupal::database()->select('photo_albums_protected', 'p')
      ->fields('p', ['pass'])
      ->condition('nid', $album_nid, '=')
      ->execute()
      ->fetchField();

    $_SESSION['_photo_albums_protected']['passwords'][$album_nid] = $pass;
  }

}
