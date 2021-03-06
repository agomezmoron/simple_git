<?php

namespace Drupal\simple_git\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\simple_git\Plugin\rest\resource\response\ResourceResponseNonCached;
use Drupal\simple_git\BusinessLogic\SimpleGitAccountBusinessLogic;
use Drupal\simple_git\BusinessLogic\SimpleGitRepositoriesBusinessLogic;
use Drupal\simple_git\BusinessLogic\SimpleGitCollaboratorsBusinessLogic;
use Drupal\simple_git\Interfaces\ModuleConstantInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a Collaborator Resource.
 *
 * @package Drupal\simple_git\Plugin\rest\resource
 * @RestResource(
 *   id = "simple_git_collaborator_resource",
 *   label = @Translation("Git Collaborator Resource"),
 *   uri_paths = {"canonical" =
 *   "/api/simple_git/collaborator/{accountId}/{owner}/{repository}/{collaborator}",
 *     "https://www.drupal.org/link-relations/create" =
 *   "/api/simple_git/collaborator",
 *   }
 * )
 */
class CollaboratorResource extends ResourceBase {

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
   * Responds to DELETE requests.
   *
   * It deletes the sent collaborator.
   *
   * @param int $accountId
   *   An id of account.
   * @param string owner
   *   An name of owner
   * @param string $repository
   *   A repository name.
   * @param string $collaborator
   *   A collaborator name.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response with the result status.
   */
  public function delete($accountId, $owner, $repository, $collaborator) {
    $isDeleted = FALSE;

    $isDeleted = SimpleGitCollaboratorsBusinessLogic::deleteCollaborators(
      SimpleGitAccountBusinessLogic::getAccountByAccountId(
        $this->currentUser, $accountId), $owner, $repository, $collaborator);
    if ($isDeleted) {
      $response = new ResourceResponse();
    }
    else {
      $response = new ResourceResponse(NULL, 204);
    }

    return $response;
  }

  /**
   * Responds to the GET request.
   *
   * @param int $accountId
   *   An id of account.
   * @param string owner
   *   An name of owner
   * @param string $repository
   *   A collaborator name.
   * @param string $collaborator
   *   A collaborator name.
   *
   * @return \Drupal\simple_git\Plugin\rest\resource\response
   * \ResourceResponseNonCached
   *   The response containing all the collaborators or a requested one.
   */
  public function get($accountId, $owner, $repository, $collaborator) {

    $isCollaborator = FALSE;
    $collaborators = [];
    if ($collaborator == ModuleConstantInterface::REST_ALL_OPTION) {
      $accounts = SimpleGitAccountBusinessLogic::getAccountByAccountId(
        $this->currentUser, $accountId
      );
      $collaborators = SimpleGitCollaboratorsBusinessLogic::getCollaborators
      ($accounts, $owner, $repository);
    }
    else {
      $accounts = SimpleGitAccountBusinessLogic::getAccountByAccountId(
        $this->currentUser, $accountId
      );
      $isCollaborator = SimpleGitCollaboratorsBusinessLogic::exists
      ($accounts, $owner, $repository, $collaborator);
      $collaborators = ['status' => $isCollaborator];
    }
    return new ResourceResponseNonCached($collaborators);
  }

  /**
   * Responds to the PUT request.
   *
   * It add the sent collaborator.
   *
   * @param int $accountId
   *   An id of account.
   * @param string owner
   *   An name of owner.
   * @param string $repository
   *   An associative array containing structure user.
   * @param string $collaborator
   *   A collaborator name.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing all the linked accounts.
   */
  public function put($accountId, $owner, $repository, $collaborator) {
    $isAdded = FALSE;
    $isAdded = SimpleGitCollaboratorsBusinessLogic::addCollaborators
    (SimpleGitAccountBusinessLogic::getAccountByAccountId($this->currentUser,
      $accountId), $owner, $repository, $collaborator);
    if ($isAdded) {
      $response = new ResourceResponse();
    }
    else {
      $response = new ResourceResponse(NULL, 204);
    }
    return $response;
  }

}
