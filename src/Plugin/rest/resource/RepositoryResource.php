<?php

/**
 * @file
 * Contains \Drupal\simple_git\Plugin\rest\resource\RepositoryResource.php
 */

namespace Drupal\simple_git\Plugin\rest\resource;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\simple_git\BusinessLogic\SimpleGitRepositoriesBusinessLogic;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\simple_git\BusinessLogic\SimpleGitAuthorizationBusinessLogic;
use Drupal\simple_git\BusinessLogic\SimpleGitAccountBusinessLogic;

/**
 * Provides a Repository Resource.
 *
 * @RestResource(
 *   id = "simple_git_repository_resource",
 *   label = @Translation("Git Repository Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/simple_git/repository/{$account_id}/{$repository_id}",
 *     "https://www.drupal.org/link-relations/create" = "/api/simple_git/repository",
 *   }
 * )
 */
class RepositoryResource extends ResourceBase {

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
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   *
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @param array $serializer_formats
   *   The available serialization formats.
   *
   * @param \Psr\Log\ $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, $logger, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->current_user = $current_user;
  }

  /**
   * Responds to POST requests.
   *
   * It creates a repository in the associated Git service.
   *
   * @param array $data
   *  Request data.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the Git account data.
   */
  public function post(array $data = []) {
    if (!isset($data['account_id'])) {
      throw new HttpException(404, t('Missing account_id'));
    }

    $account = SimpleGitAccountBusinessLogic::getAccountByAccountId($this->current_user,$data['account_id']);

    if (empty($account)) {
      throw new HttpException(404, t('The account doesn\'t exist.'));
    }

    if (SimpleGitRepositoriesBusinessLogic::exists($account, $data['repository'])) {
      throw new HttpException(401, t('There is a repository with the provided name in the current account.'));
    } else {
      $repository = SimpleGitRepositoriesBusinessLogic::create($account, $data['repository']);

      if (empty($repository)) {
        throw new HttpException(500, t('An error occurred creating the repository'));
      } else {
        return new ResourceResponse($repository);
      }
    }
  }

  /**
   * Responds to DELETE requests.
   *
   * It deletes the sent repository from the provided account.
   *
   * @param $account_id
   *  An id of account
   * @param $repository_id
   *  A repository id.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response with the result status.
   */
  public function delete($account_id, $repository_id) {
    // TODO: Check if it works


    return new ResourceResponse();
  }

  /**
   * Responds to the GET request.
   *
   * @param $account_id
   *  An id of account
   * @param $repository_id
   *  A repository id.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing all the repositoryes or a requested one.
  */
  public function get($account_id = NULL, $repository_id = NULL ) {
    $repositories = [];

    if ($account_id == REST_ALL_OPTION) {
      // should be reviewed once it is pushed!
      $repositories = SimpleGitRepositoriesBusinessLogic::getRepositories(SimpleGitAccountBusinessLogic::getAccounts($this->current_user));
    } else {
      if ($repository_id == REST_ALL_OPTION) {
        $account = SimpleGitAccountBusinessLogic::getAccountByAccountId($this->current_user, $account_id);
        if (!empty($account)) {
          $repositories = SimpleGitRepositoriesBusinessLogic::getRepositories(array($account));
        } else {
          throw new \HttpException(404, t('The provided account doesn\'t exist'));
        }
      } else {
        $repositories = SimpleGitRepositoriesBusinessLogic::getRepository($account_id, $repository_id, $this->current_user);
      }
    }

    return new ResourceResponse($repositories);
  }

}