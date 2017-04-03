<?php

/**
 * File for account resource
 *
 * @file
 * Contains \Drupal\simple_git\Plugin\rest\resource\ConnectorResource.php
 * @author  Alejandro Gómez Morón <amoron@emergya.com>
 * @author  Estefania Barrrera Berengeno <ebarrera@emergya.com>
 * @version PHP: 7
 */

namespace Drupal\simple_git\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\simple_git\Interfaces\ModuleConstantInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Connector Resource.
 *
 * @package Drupal\simple_git\Plugin\rest\resource
 * @RestResource(
 *   id = "simple_git_connector_resource",
 *   label = @Translation("Git Connector Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/simple_git/connector"
 *   }
 * )
 */
class ConnectorResource extends ResourceBase {

  /**
   *  A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $current_user;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array     $configuration
   *   A configuration array containing information about the plugin instance.
   *
   * @param string    $plugin_id
   *   The plugin_id for the plugin instance.
   *
   * @param mixed     $plugin_definition
   *   The plugin implementation definition.
   *
   * @param array     $serializer_formats
   *   The available serialization formats.
   *
   * @param \Psr\Log\ $logger
   *   A logger instance.
   */
  public function __construct(
    array $configuration, $plugin_id, $plugin_definition,
    array $serializer_formats, $logger, AccountProxyInterface $current_user
  ) {
    parent::__construct(
      $configuration, $plugin_id, $plugin_definition, $serializer_formats,
      $logger
    );
    $this->current_user = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container, array $configuration, $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user')
    );
  }

  /*
   * Responds to the GET request.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The configured connectors.
   */

  public function get() {
    $connectors = array();

    $git_settings = \Drupal::config('simple_git.settings');

    // GitHub connector
    if (!empty(
    $git_settings->get(
      ModuleConstantInterface::GIT_TYPE_GITHUB
    )['app_id']
    )
    ) {
      $connectors[] = array(
        'client_id' => $git_settings->get(
          ModuleConstantInterface::GIT_TYPE_GITHUB
        )['app_id'],
        'type' => ModuleConstantInterface::GIT_TYPE_GITHUB
      );
    }

    // GitLab connector
    if (!empty(
    $git_settings->get(
      ModuleConstantInterface::GIT_TYPE_GITLAB
    )['app_id']
    )
    ) {
      $connectors[] = array(
        'client_id' => $git_settings->get(
          ModuleConstantInterface::GIT_TYPE_GITLAB
        )['app_id'],
        'type' => ModuleConstantInterface::GIT_TYPE_GITLAB
      );
    }

    return new ResourceResponse($connectors);
  }

}