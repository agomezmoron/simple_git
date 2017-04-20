<?php

namespace Drupal\simple_git\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\simple_git\Plugin\rest\resource\response\ResourceResponseNonCached;
use Drupal\simple_git\BusinessLogic\SimpleGitAccountBusinessLogic;
use Drupal\simple_git\BusinessLogic\SimpleGitRepositoriesBusinessLogic;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\simple_git\Interfaces\ModuleConstantInterface;

/**
 * Provides a Repository Resource.
 *
 * @RestResource(
 *   id = "simple_git_repository_resource",
 *   label = @Translation("Git Repository Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/simple_git/repository/{account_id}/{repository_id}",
 *     "https://www.drupal.org/link-relations/create" =
 *   "/api/simple_git/repository",
 *   }
 * )
 */
class RepositoryResource extends ResourceBase {

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
   * Responds to POST requests.
   *
   * It creates a repository in the associated Git service.
   *
   * @param array $data
   *   Request data.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the Git account data.
   */
  public function post($data = []) {
    if (!isset($data['account_id'])) {
      throw new HttpException(404, t('Missing account_id'));
    }

    $account = SimpleGitAccountBusinessLogic::getAccountByAccountId(
      $this->currentUser, $data['account_id']
    );

    if (empty($account)) {
      throw new HttpException(404, t('The account "doesn\'t" exist.'));
    }

    if (SimpleGitRepositoriesBusinessLogic::exists(
      $account, $data['repository'])) {
        $response = new ResourceResponse(null,401);
    }
    else {
      $repository = SimpleGitRepositoriesBusinessLogic::create(
        $account, $data['repository']
      );

      if (empty($repository)) {
          $response = new ResourceResponse(null,500);
      }
      else {
          $response = new ResourceResponse($repository);
      }
    }
      return $response;
  }

  /**
   * Responds to DELETE requests.
   *
   * It deletes the sent repository from the provided account.
   *
   * @param mixed $accountId
   *   An id of account.
   * @param int $repository
   *   A repository name.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response with the result status.
   */
  public function delete($accountId, $repository) {
      $isDeleted = false;
      $isDeleted = SimpleGitRepositoriesBusinessLogic::deleteRepository(
          SimpleGitAccountBusinessLogic::getAccountByAccountId(
              $this->currentUser, $accountId), $repository);
      if(!$isDeleted){
          $response = new ResourceResponse(null, 404);
      }else{
          $response = new ResourceResponse();
      }
      return $response;
  }

  /**
   * Responds to the GET request.
   *
   * @param int $accountId
   *   An id of account.
   * @param int $repository
   *   A repository id.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing all the repositoryes or a requested one.
   */
  public function get($accountId = NULL, $repository = NULL) {
    $repositories = [];

    if ($accountId == ModuleConstantInterface::REST_ALL_OPTION) {
      // Should be reviewed once it is pushed!
      $repositories = SimpleGitRepositoriesBusinessLogic::getRepositories(
        SimpleGitAccountBusinessLogic::getAccounts($this->currentUser)
      );
    }
    else {
      if ($repository == ModuleConstantInterface::REST_ALL_OPTION) {
        $account = SimpleGitAccountBusinessLogic::getAccountByAccountId(
          $this->currentUser, $accountId
        );
        if (!empty($account)) {
          $repositories = SimpleGitRepositoriesBusinessLogic::getRepositories(
            [$account]
          );
        }
        else {
          throw new \HttpException(
            404, t('The provided account "doesn\'t" exist')
          );
        }
      }
      else {
        $repositories = SimpleGitRepositoriesBusinessLogic::getRepository(
          $accountId, $repository, $this->currentUser
        );
      }
    }
    return new ResourceResponseNonCached($repositories);
  }

    /**
     * Responds to the PUT request.
     *
     * It add the sent repository.
     *
     * @param int $accountId
     *   An id of account.
     * @param string $repository
     *   An associative array containing structure user.
     * @param string $collaborator
     *   A collaborator name.
     *
     * @return \Drupal\rest\ResourceResponse
     *   The response containing all the linked accounts.
     */
    public function put($accountId, $repository) {
        $isCollaborator = SimpleGitRepositoriesBusinessLogic::create(
            SimpleGitAccountBusinessLogic::getAccountByAccountId(
                $this->currentUser, $accountId), $repository);
        if($isCollaborator){
            //The server successfully processed the request and is not returning any content
            $response = new ResourceResponseNonCached(null, 204);
        }else{
            $response = new ResourceResponseNonCached(null, 404);
        }
        return $response;
    }

}
