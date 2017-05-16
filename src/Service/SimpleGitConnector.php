<?php

namespace Drupal\simple_git\Service;

/**
 * This abstract class is a management contract for the different connection
 * types.
 *
 * Class SimpleGitConnector.
 *
 * @package Drupal\simple_git\Service
 */
abstract class SimpleGitConnector {

  /**
   * Constants to determine to output mapping.
   *
   * @var PULL_REQUEST
   */
  const PULL_REQUEST = 'PULL_REQUEST';

  /**
   * Constants to determine to output mapping.
   *
   * @var ACCOUNT
   */
  const ACCOUNT = 'ACCOUNT';

  /**
   * Constants to determine to output mapping.
   *
   * @var REPOSITOY
   */
  const REPOSITORY = 'REPOSITORY';

  /**
   * Constants to determine to output mapping.
   *
   * @var REPOSITOY
   */
  const COLLABORATOR = 'COLLABORATOR';

  /**
   * Constants to determine to output mapping.
   *
   * @var PULL_REQUEST
   */
  const USER = 'USER';

  /**
   * Constants to determine to output mapping.
   *
   * @var mappings
   */
  protected $mappings = [];

  /*const BRANCH = 'BRANCH';
  const PROJECTS = 'PROJECTS';
  const COMMIT = 'COMMIT';
  */

  /**
   * SimpleGitConnector constructor.
   *
   * As first task must to configure the mappings for ensure the response
   * format.
   */
  public function __construct() {
    $this->buildCustomMappings();
  }

  /**
   * Create the mappings for ensure the response format.
   */
  protected abstract function buildCustomMappings();

  /**
   * Get token from login params for the authorization.
   *
   * @param array $params
   *   Tt's an array that content depends on implementation.
   *
   * @return array
   *   Mixed The return is the response of the first 'user detail' request
   *   to serve the complete account
   */
  public abstract function authorize($params);

  /**
   * Get the list of repositories associated to the selected account.
   *
   * @param array $params
   *   It's an array that content depends on implementation.
   *
   * @return array
   *   With all the repositories available.
   */
  public abstract function getRepositoriesList($params);

  /**
   * Post create repository.
   *
   * @param array $params
   *   It needs the userInfo and repository.
   *
   * @return array
   *   Response if user has been added.
   */
  public abstract function addRepository($params);

  /**
   * Delete user as a repository.
   *
   * @param array $params
   *   It needs the userInfo and repository.
   *
   * @return array
   *   Response if user has been removed.
   */
  public abstract function deleteRepository($params);

  /**
   * Get the list of pull request associated to the selected repository.
   *
   * @param array $params
   *   It's an array that content depends on implementation.
   *
   * @return array
   *   With all Pull Requests.
   */
  public abstract function getPullRequestsList($params);

  /**
   * Get a concrete pull request.
   *
   * @param array $params
   *   It's an array that content depends on implementation.
   *
   * @return mixed
   *   With the Pull Request information.
   */
  public abstract function getPullRequest($params);

  /**
   * Get a concrete pull request.
   *
   * @param array $params
   *   It needs the userInfo.
   *
   * @return array
   *   Collaborators information.
   */
  public abstract function getCollaboratorsList($params);

  /**
   * Get a check a user is a collaborator.
   *
   * @param array $params
   *   It needs the userInfo and repository.
   *
   * @return array
   *   Response if user is a collaborator.
   */
  public abstract function isCollaborator($params);

  /**
   * Put user as a collaborator.
   *
   * @param array $params
   *   It needs the userInfo and repository.
   *
   * @return array
   *   Response if user has been added.
   * Note that to prevent abuse you are limited to 50 invitations per 24 hour
   *   period
   */
  public abstract function addCollaborator($params);

  /**
   * Delete user as a collaborator.
   *
   * @param array $params
   *   It needs the userInfo and repository.
   *
   * @return array
   *   Response if user has been removed.
   */
  public abstract function deleteCollaborator($params);

  /**
   * Get the logged user  account details.
   *
   * @param array $params
   *   It's an array that content depends on implementation.
   *
   * @return mixed
   *   With the account information.
   */
  public abstract function getAccount($params);

  /**
   * Get a single user details.
   *
   * @param string $user
   *   It's an array that content depends on implementation.
   *
   * @return mixed
   *   With the account information.
   */
  public abstract function getUserDetail($account, $user);

  /**
   * It checks if the repository exists.
   *
   * @param $params
   *   It's an array that content depends on implementation.
   *
   * @return bool
   *   True if the repository exists
   */
  public function existsRepository($params) {
    return !empty($this->getRepository($params));
  }

  /**
   * Get a concrete repository.
   *
   * @param array $params
   *   It's an array that content depends on implementation.
   *
   * @return mixed
   *   With the repository information.
   */
  public abstract function getRepository($params);

  /**
   * Obtain the connection config based in the connection type(Github,Gitlab).
   *
   * @return string
   *   With the connector type associated.
   */
  protected final function getConnectorConfig($params) {
    $git_settings = \Drupal::config('simple_git.settings');
    return $git_settings->get($this->getConnectorType($params));
  }

  /**
   * Return the connection type(Github, Gitlab...) defined as constant.
   *
   * @return mixed
   *   With the conenctor type.
   */
  public abstract function getConnectorType($params);

  /**
   * Configure the response, based in the corresponding mapping.
   *
   * For multi node elements we're using the -> separator.
   * As custom convention inside of the string.
   *
   * @param mixed $data
   *   The original response from repository without filtering.
   * @param mixed $entity_type
   *   The response mapping type (PullRequest, Repository, Account).
   *
   * @return array
   *   With the correct format to send to client apps.
   */
  protected final function buildResponse($data, $entity_type) {
    $response = [];

    if (isset($this->mappings[$entity_type])
      && is_array(
        $this->mappings[$entity_type]
      )
    ) {

      foreach (
        $this->mappings[$entity_type] as $responseKey => $connectorKey
      ) {

        // Multiple options.
        if (is_array($connectorKey)) {
          foreach ($connectorKey as $key) {
            $value = $this->getMappingBySingleKey($data, $key);
            $response[$responseKey] = $value;
            if (!empty($response[$responseKey])) {
              break;
            }
          }
        }
        else {
          // Single mapping.
          $response[$responseKey] = $this->getMappingBySingleKey(
            $data, $connectorKey
          );
        }

      }
      $response['type'] = $this->getConnectorType();
    }

    return $response;
  }

  /**
   * Configure the response, based in the corresponding mapping.
   *
   * @param mixed $data
   *   The original response from repository without filtering.
   * @param string $connectorKey
   *   The value connector.
   *
   * @return string
   *   With the correct format to send to client apps.
   */
  protected final function getMappingBySingleKey($data, $connectorKey) {
    $value = '';
    // We check if it is a multi-node element:
    if (strpos($connectorKey, '->')) {
      $node_names = explode('->', $connectorKey);
      // $data['milestone'].
      $finalValue = $data[$node_names[0]];
      for ($i = 1; $i < count($node_names); $i++) {
        $finalValue = $finalValue[$node_names[$i]];

      }
      $value = $finalValue;
    }
    else {
      $value = $data[$connectorKey];
    }
    return $value;
  }

}
