<?php

namespace Drupal\simple_git\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\simple_git\Plugin\rest\resource\response\ResourceResponseNonCached;
use Drupal\simple_git\BusinessLogic\SimpleGitAccountBusinessLogic;
use Drupal\simple_git\BusinessLogic\SimpleGitPullRequestsBusinessLogic;
use Drupal\simple_git\BusinessLogic\SimpleGitRepositoriesBusinessLogic;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Pull Request Resource.
 *
 * @package Drupal\simple_git\Plugin\rest\resource
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
   *   The response containing all the available Pull Requests.
   */
  public function get() {
    $accounts = [];
    $accounts = SimpleGitAccountBusinessLogic::getAccounts(
      $this->currentUser
    );
    $repositories = SimpleGitRepositoriesBusinessLogic::getRepositories(
      $accounts
    );
    $pr = SimpleGitPullRequestsBusinessLogic::getPullRequests(
      $accounts, $repositories
    );
    return new ResourceResponseNonCached($pr);
  }

}
