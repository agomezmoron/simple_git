<?php

namespace Drupal\simple_git\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\simple_git\Plugin\rest\resource\response\ResourceResponseNonCached;
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
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */

  protected $currentUser;

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
   * @param \Psr\Log $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current account.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    $logger,
    AccountProxyInterface $current_user
  ) {
    parent::__construct(
      $configuration, $plugin_id, $plugin_definition, $serializer_formats,
      $logger
    );
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
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

  /**
   * Responds to the GET request.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The configured connectors.
   */
  public function get() {
    $connectors = [];

    $git_settings = \Drupal::config('simple_git.settings');

    // GitHub Web connector
    if (!empty(
    $git_settings->get(
      ModuleConstantInterface::GIT_TYPE_GITHUB
    )['app_id']
    )
    ) {
      $connectors[] = [
        'client_id' => $git_settings->get(
          ModuleConstantInterface::GIT_TYPE_GITHUB
        )['app_id'],
        'type' => ModuleConstantInterface::GIT_TYPE_GITHUB,
      ];
    }

    // GitLab Web connector.
    if (!empty(
    $git_settings->get(
      ModuleConstantInterface::GIT_TYPE_GITLAB
    )['app_id']
    )
    ) {
      $connectors[] = [
        'client_id' => $git_settings->get(
          ModuleConstantInterface::GIT_TYPE_GITLAB
        )['app_id'],
        'type' => ModuleConstantInterface::GIT_TYPE_GITLAB,
      ];
    }

    // GitHub Mobile connector
    if (!empty(
    $git_settings->get(
      ModuleConstantInterface::GIT_TYPE_GITHUBM
    )['app_id']
    )
    ) {
      $connectors[] = [
        'client_id' => $git_settings->get(
          ModuleConstantInterface::GIT_TYPE_GITHUBM
        )['app_id'],
        'type' => ModuleConstantInterface::GIT_TYPE_GITHUBM,
      ];
    }

    // GitLab Mobile connector.
    if (!empty(
    $git_settings->get(
      ModuleConstantInterface::GIT_TYPE_GITLABM
    )['app_id']
    )
    ) {
      $connectors[] = [
        'client_id' => $git_settings->get(
          ModuleConstantInterface::GIT_TYPE_GITLABM
        )['app_id'],
        'type' => ModuleConstantInterface::GIT_TYPE_GITLABM,
      ];
    }
    return new ResourceResponseNonCached($connectors);
  }

}
