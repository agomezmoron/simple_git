<?php

/**
 * @file
 * Contains \Drupal\simple_git\Plugin\rest\resource\PullRequestResource.php
 */

namespace Drupal\simple_git\Plugin\rest\resource;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a Pull Request Resource
 *
 * @RestResource(
 *   id = "simple_git_pull_request_resource",
 *   label = @Translation("Git Pull Request Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/simple_git/pull_request"
 *   }
 * )
 */
class PullRequestResource extends ResourceBase {

  /**
   *  A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $current_user;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\ $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, $logger, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->current_user = $current_user;
  }

  /*
  * Responds to the GET request.
  *
  * @return \Drupal\rest\ResourceResponse
  *   The response containing all the available Pull Requests.
  */
  public function get() {
    $accounts = array();



    $accounts[] = array(
      'id' => 1,
      'fullname' => 'Alejandro Gómez Morón',
      'username' => 'agomezmoron',
      'email' => 'agommor@gmail.com',
      'type' => 'GITHUB',
      'photoUrl' => 'http://lorempixel.com/200/200/',
      'repoNumber' => 10,
      'organization' => 'Emergya',
      'location' => 'Sevilla',
    );

    $accounts[] = array(
      'id' => 3,
      'fullname' => 'Alejandro Gómez Morón',
      'username' => 'agomezmoron',
      'email' => 'agommor@gmail.com',
      'type' => 'BITBUCKET',
      'photoUrl' => 'http://lorempixel.com/200/200/',
      'repoNumber' => 10,
      'organization' => 'Emergya',
      'location' => 'Sevilla',
    );


    return new ResourceResponse($accounts);
  }

}