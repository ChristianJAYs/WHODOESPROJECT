<?php

namespace Drupal\photo_albums\EventSubscriber;

use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects user to protected page login screen.
 */
class ProtectedAlbumSubscriber implements EventSubscriberInterface {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected $aliasManager;

  /**
   * The account proxy service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The current path stack service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestination
   */
  protected $destination;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * A policy evaluating to static::DENY when the kill switch was triggered.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * Constructs a new ProtectedPagesSubscriber.
   *
   * @param \Drupal\path_alias\AliasManager $aliasManager
   *   The path alias manager.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The account proxy service.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   The current path stack service.
   * @param \Drupal\Core\Routing\RedirectDestination $destination
   *   The redirect destination service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $pageCacheKillSwitch
   *   The cache kill switch service.
   */
  public function __construct(AliasManager $aliasManager, AccountProxy $currentUser, CurrentPathStack $currentPathStack, RedirectDestination $destination, RequestStack $requestStack, KillSwitch $pageCacheKillSwitch) {
    $this->aliasManager = $aliasManager;
    $this->currentUser = $currentUser;
    $this->currentPath = $currentPathStack;
    $this->destination = $destination;
    $this->requestStack = $requestStack;
    $this->pageCacheKillSwitch = $pageCacheKillSwitch;
  }

  /**
   * Redirects user to protected page login screen.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function checkProtectedAlbum(FilterResponseEvent $event) {
    if ($this->currentUser->hasPermission('bypass album password protection')) {
      return;
    }

    // Get the current path and the internal path.
    $current_path = $this->aliasManager->getAliasByPath($this->currentPath->getPath());
    $normal_path = mb_strtolower($this->aliasManager->getPathByAlias($current_path));

    // Get the route parameters from the derived path.
    $url_object = \Drupal::service('path.validator')->getUrlIfValid($normal_path);
    if ($url_object !== FALSE) {
      $route_name = $url_object->getRouteName();
      $route_parameters = $url_object->getrouteParameters();
    }

    // If the path starts with "node" and the second element is numeric.
    if (isset($route_parameters['node']) && (isset($route_name) && $route_name !== 'entity.node.edit_form')) {
      // Check the user's session to see if they have already
      // authenticated this album.
      if (isset($_SESSION['_photo_albums_protected']['passwords'][$route_parameters['node']])) {
        // Get the password record for the album node.
        $pass = \Drupal::database()->select('photo_albums_protected', 'p')
          ->fields('p', ['pass'])
          ->condition('nid', $route_parameters['node'], '=')
          ->execute()
          ->fetchField();

        // Compare the database hashed password with the cookie.
        if ($pass === $_SESSION['_photo_albums_protected']['passwords'][$route_parameters['node']]) {
          return;
        }
      }

      // Check to see if the node ID is in the protected
      // albums.
      $results = \Drupal::database()->select('photo_albums_protected', 'p')
        ->fields('p', ['nid'])
        ->condition('nid', $route_parameters['node'], '=')
        ->execute()
        ->fetchAll();

      // If results are returned then the album is protected
      // so we redirect to the login page.
      if (count($results)) {
        $query = \Drupal::destination()->getAsArray();
        $query['album_nid'] = $route_parameters['node'];
        $this->pageCacheKillSwitch->trigger();
        $response = new RedirectResponse(Url::fromUri('internal:/albums/login', ['query' => $query])->toString());
        $response->send();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['checkProtectedAlbum'];
    return $events;
  }

}
