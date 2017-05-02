<?php

namespace Drupal\simple_git\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\simple_git\Plugin\rest\resource\response\ResourceResponseNonCached;
use Drupal\simple_git\BusinessLogic\SimpleGitAccountBusinessLogic;
use Drupal\simple_git\BusinessLogic\SimpleGitUserBusinessLogic;
use Drupal\simple_git\Interfaces\ModuleConstantInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Connector Resource.
 *
 * @package Drupal\simple_git\Plugin\rest\resource
 * @RestResource(
 *   id = "simple_git_user_resource",
 *   label = @Translation("Git User Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/simple_git/user/{account_id}/{user}",
 *     "https://www.drupal.org/link-relations/create" = "/api/simple_git/user",
 *   }
 * )
 */
class UserResource extends ResourceBase {

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
   * @return \Drupal\simple_git\Plugin\rest\resource\response
   * \ResourceResponseNonCached
   *   The response containing all the linked accounts.
   */
  public function get($accountId = NULL, $user) {
    $accounts = [];
    $userInfo = [];

    if ($accountId == ModuleConstantInterface::REST_ALL_OPTION) {
      $accounts = SimpleGitAccountBusinessLogic::getAccounts(
        $this->currentUser
      );
      $userInfo = SimpleGitUserBusinessLogic::getUser($accounts, $user);
    }
    else {
      $accounts = SimpleGitAccountBusinessLogic::getAccountByAccountId(
        $this->currentUser, $accountId
      );
      $userInfo = SimpleGitUserBusinessLogic::getUser($accounts, $user);

    }
    if (in_array(NULL, $userInfo)) {
      $response = new ResourceResponseNonCached(NULL, 404);
    }elseif (in_array('Bad Request',$userInfo)){
      $response = new ResourceResponseNonCached(NULL, 400);
    }
    else {
      $response = new ResourceResponseNonCached($userInfo);
    }

    return $response;

  }

}
