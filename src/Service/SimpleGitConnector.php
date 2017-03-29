<?php

/**
 * Abstract class SimpleGitConnector.
 *
 * @file
 * Contains \Drupal\simple_git\Service\SimpleGitConnector.php.
 */

namespace Drupal\simple_git\Service;

/**
 * This abstract class is a management contract for the different connection types.
 *
 * Class SimpleGitConnector.
 *
 * @package Drupal\simple_git\Service
 */
abstract class SimpleGitConnector {

  protected $mappings = array();

  /**
   * Constants to determine to output mapping.
   */
  const PULL_REQUEST = 'PULL_REQUEST';
  const ACCOUNT = 'ACCOUNT';
  const REPOSITORY = 'REPOSITORY';
  const BRANCH = 'BRANCH';
  const PROJECTS = 'PROJECTS';
  const COMMIT = 'COMMIT';

  /**
   * SimpleGitConnector constructor.
   *
   * As first task must to configure the mappings for ensure the response format.
   */
  public function __construct() {
    $this->buildCustomMappings();
  }

  /**
   * Obtain the connection config based in the connection type(Github, Gitlab..).
   *
   * @return string with the connector type associated.
   */
  protected final function getConnectorConfig() {
    $git_settings =  \Drupal::config('simple_git.settings');
    return $git_settings->get($this->getConnectorType());
  }

  /**
   * Create the mappings for ensure the response format.
   *
   */
  protected abstract function buildCustomMappings();

  /**
   * Get token from login params for the authorization.
   *
   * @param $params
   *  Tt's an array that content depends on implementation
   *
   * @return
   *  Mixed The return is the response of the first 'user detail' request to serve the complete account
   */
  public abstract function authorize($params);

  /**
   * Get the list of repositories associated to the selected account.
   *
   * @param $params
   *  It's an array that content depends on implementation
   *
   * @return array
   *  With all the repositories available.
   */
  public abstract function getRepositoriesList($params);

  /**
   * Get a concrete repository.
   *
   * @param $params
   *  It's an array that content depends on implementation
   *
   * @return mixed
   *  With the repository information.
   */
  public abstract function getRepository($params);

  /**
   * Get the list of pull request associated to the selected repository.
   *
   * @param $params
   *  It's an array that content depends on implementation
   *
   * @return array
   *  With all Pull Requests.
   */
  public abstract function getPullRequestsList($params);

  /**
   * Get a concrete pull request.
   *
   * @param $params
   *  It's an array that content depends on implementation
   *
   * @return mixed
   *  With the Pull Request information.
   */
  public abstract function getPullRequest($params);

  /**
   * Get the logged user account details.
   *
   * @param $params
   *  It's an array that content depends on implementation
   *
   * @return mixed
   *  With the account information.
   */
  public abstract function getAccount($params);

  /**
   * Return the connection type(Github, Gitlab...) defined as constant.
   *
   * @return mixed with the conenctor type.
   */
  public abstract function getConnectorType();

  /**
   * Configure the response, based in the corresponding mapping. For multi node
   * elements we're using the -> separator as custom convention
   * inside of the string.
   *
   * @param $data
   *  The original response from repository without filtering
   *
   * @param $entity_type
   *  The response mapping type (PullRequest, Repository, Account)
   *
   * @return array
   *  With the correct format to send to client apps
   */
  protected final function buildResponse($data, $entity_type) {
    $response = array();


    if (isset($this->mappings[$entity_type]) && is_array($this->mappings[$entity_type])) {

      foreach ($this->mappings[$entity_type] as $responseKey => $connectorKey) {

        // multiple options
        if (is_array($connectorKey)) {
          foreach($connectorKey as $key) {
            $value = $this->getMappingBySingleKey($data, $key);
            $response[$responseKey] = $value;
            if (!empty($response[$responseKey])) {
              break;
            }
          }
        } else {
          // single mapping
          $response[$responseKey] = $this->getMappingBySingleKey($data, $connectorKey);
        }

      }
      $response['type'] = $this->getConnectorType();
    }

    return $response;
  }

  protected final function getMappingBySingleKey($data, $connectorKey) {
    $value = '';
    // we check if it is a multi-node element
    if (strpos($connectorKey, '->')) {
      $node_names = explode('->', $connectorKey);
      $finalValue = $data[$node_names[0]]; // $data['milestone']
      for ($i = 1; $i < sizeof($node_names); $i++) {
        $finalValue = $finalValue[$node_names[$i]];

      }
      $value = $finalValue;
    } else {
      $value = $data[$connectorKey];
    }
    return $value;
  }

}
