<?php

/**
 * @file
 * Contains \Drupal\simple_git\Service\SimpleGitHubConnectorService.
 * @author  Alejandro Gómez Morón <amoron@emergya.com>
 * @author  Estefania Barrrera Berengeno <ebarrera@emergya.com>
 * @version PHP: 7
 */

namespace Drupal\simple_git\Service;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\simple_git\Plugin\rest\resource\PullRequestResource;
use Drupal\simple_git\Service\SimpleGitConnectorInterface;
use Drupal\user\UserDataInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\simple_git\Interfaces\ModuleConstantInterface;

/**
 * This service manage the requests to the github's API.
 *
 * Class SimpleGitHubConnectorService.
 *
 * @package Drupal\simple_git\Service
 */
class SimpleGitHubConnectorService extends SimpleGitConnector {

  const BASE_URL = 'https://api.github.com/';

  /**
   * Items per page. By default the GitHub API has 30 elements.
   */
  const PER_PAGE = 500;

  /**
   * SimpleGitHubConnectorService constructor.
   *
   * It calls to the parent to configure the mappings.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *  In this case the needed params are the sent state to login and the code
   *  returned from login.
   *
   * @return mixed the access token to perform the requests
   */
  public function authorize($params) {
    if ($params['code'] && $params['state']) {
      $code = $params['code'];
      $state = $params['state'];
      $settings = $this->getConnectorConfig();
      //Url to user
      $url = 'https://github.com/login/oauth/access_token';
      //Set parameters
      $parameters = array(
        'client_id' => $settings['app_id'],
        'client_secret' => $settings['app_secret'],
        'code' => $code,
        'state' => $state
      );

      //Open curl stream
      $ch = $this->getConfiguredCURL($url);

      //set the url, number of POST vars, POSTdata
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, count($parameters));
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
      $status_code = curl_getinfo($ch, CURLINFO_HTTP_COD);   //get status code
      $response = $this->performCURL($ch);
      //Exposing the access token if it's necessary
      $access_token = $response['access_token'];
      //Return the obtained access_token
      return $access_token;
    }
  }

  /**
   * Configure a basic curl request.
   *
   * @param      $url the attacked endpoint
   *
   * @param null $username
   *
   * @param null $token
   *                  These params are 'optional'. (By the moment the only exception is the
   *                  authorize method).
   *
   * @return resource
   */
  protected function getConfiguredCURL($url, $user = NULL) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if (!is_null($user) && !is_null($user['access_info'])
      && isset($user['access_info']['token'])
    ) {
      $headers = $this->buildHeaders($user['access_info']['token']);
    }
    else {
      $headers = $this->buildHeaders();
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    return $ch;
  }

  /**
   * Include the headers into the curl request.
   *
   * @return array
   */
  protected function buildHeaders($token = NULL) {
    $headers = [];
    $headers[] = 'Accept: application/json';
    $headers[] = 'Accept: application/vnd.github.v3+json';

    // by default name
    $app_name = 'GitHub Dashboard';
    $connector_config = $this->getConnectorConfig();
    if (!empty($connector_config) && isset($connector_config['app_name'])) {
      $app_name = $connector_config['app_name'];
    }
    $headers[] = 'User-Agent: ' . $app_name;

    // if we have the security token
    if (!is_null($token)) {
      $headers[] = 'Authorization: token ' . $token;
    }
    return $headers;
  }

  /**
   * Perform the curl request, close the stream and return the response.
   *
   * @param $ch
   *
   * @return mixed
   */
  protected function performCURL(&$ch) {
    $data = curl_exec($ch);
    curl_close($ch);
    if (!is_array($data) && is_string($data)) {
      $data = json_decode($data, TRUE);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *  It needs the userInfo
   *
   * @return array
   */
  public function getRepositoriesList($params) {
    $response = array();
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . 'user/repos?per_page=' . PER_PAGE;
      $ch = $this->getConfiguredCURL($url, $user);
      $repositories = $this->performCURL($ch);
      foreach ($repositories as $repo) {
        $repo['parent'] = $repo['parent'] ? TRUE : FALSE;
        $repo = $this->buildResponse($repo, self::REPOSITORY);
        $repo['account'] = $user['username'];
        array_push($response, $repo);
      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *  It needs the userInfo and the name of the repository requested.
   *
   * @return mixed
   */
  public function getRepository($params) {
    $response = [];
    if ($params['userInfo'] && $params['repository']) {
      $user = $params['userInfo'];
      $repository = $params['repository'];
      $url = self::BASE_URL . $repository['username'] . '/'
        . $repository['name'];
      $ch = $this->getConfiguredCURL($url, $user);
      $repo = $this->performCURL($ch);
      $response = $this->configureRepositoryFields($repo);
      $response['account'] = $user['username'];
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *  It needs the userInfo and the name of the repository to see its associated
   *  pull requests.
   *
   * @return array $pull_requests
   *  With the Pull Requests of the provided repository.
   */
  public function getPullRequestsList($params) {
    $pull_requests = [];
    if ($params['userInfo'] && $params['repositories']) {
      $user = $params['userInfo'];
      $repositories = $params['repositories'];

      foreach ($repositories as $repository) {
        $url = self::BASE_URL . 'repos/' . $repository['username'] . '/'
          . $repository['name'] . '/pulls?per_page=' . PER_PAGE;
        $ch = $this->getConfiguredCURL($url, $user);
        $prs = $this->performCURL($ch);
        foreach ($prs as $pr) {
          $pull_requests[] = $this->buildResponse($pr, self::PULL_REQUEST);
        }
      }
    }

    return $pull_requests;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *  It needs the userInfo, the name of accessed repo and the id of the concrete
   *  pull request.
   *
   * @return array
   */
  public function getPullRequest($params) {
    if ($params['userInfo'] && $params['repository'] && $params['id']) {
      $user = $params['userInfo'];
      $repository = $params['repository'];
      $pr_id = $params['id'];
      $url = self::BASE_URL . 'repos/' . $repository['username'] . '/'
        . $repository['name'] . '/pulls/' . $pr_id;
      $ch = $this->getConfiguredCURL($url, $user);
      $pr = $this->performCURL($ch);
      return $this->buildResponse($pr, self::PULL_REQUEST);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\simple_git\Service\it $params
   *  It needs the userInfo.
   *
   * @return array
   */
  public function getAccount($params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . 'user';
      $ch = $this->getConfiguredCURL($url, $user);
      $account = $this->performCURL($ch);
      $account['number_of_repos'] = $account['total_private_repos']
        + $account['public_repos'];
      return $this->buildResponse($account, self::ACCOUNT);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   */
  public function getConnectorType() {
    return ModuleConstantInterface::GIT_TYPE_GITHUB;
  }

  /**
   * Obtain the user detail of a non-logged user.
   *
   * @param $params
   *  It needs the userName
   *
   * @return mixed
   */
  protected function getUserDetail($params) { //Non-logged user
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . 'users/' . $user->username;
      $ch = $this->getConfiguredCURL($url, $user);
      $response = $this->performCURL($ch);
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCustomMappings() {
    $this->mappings[self::PULL_REQUEST] = array(
      'id' => 'number',
      'title' => 'title',
      'description' => 'body',
      'username' => 'user->login',
      'date' => 'created_at',
      'updated' => 'updated_at',
      'commits' => 'commits',
      'comments' => 'comments',
      'from' => 'head->label',
      'from_repo_id' => 'head->repo->id',
      'from_repo_name' => 'head->repo->name',
      'to' => 'base->label',
      'to_repo_id' => 'base->repo->id',
      'to_repo_name' => 'base->repo->name'
    );
    $this->mappings[self::ACCOUNT] = array(
      'fullname' => array('name', 'login'),
      'username' => 'login',
      'photoUrl' => 'avatar_url',
      'id' => 'id',
      'email' => 'email',
      'location' => 'location',
      'organization' => 'company',
      'repoNumber' => 'number_of_repos'
      // it is autocalculated on getAccount method.
    );
    $this->mappings[self::REPOSITORY] = array(
      'id' => 'id',
      'name' => 'name',
      'username' => 'owner->login',
      'issues' => 'open_issues_count',
      'language' => 'language',
      'updated' => 'pushed_at',
      'age' => 'created_at'
    );
  }

  /**
   * Obtain the commit list from a concrete pull request.
   *
   * @param $user  the userInfo
   *
   * @param $repo  the name of accessed repository
   *
   * @param $pr_id the pull request id
   *
   * @return mixed
   */
  protected function getPullRequestCommits($user, $repo, $pr_id) {
    $url = self::BASE_URL . 'repos/' . $user->usermname . '/' . $repo
      . '/pulls/' . $pr_id . '/commits';
    $ch = $this->getConfiguredCURL($url, $user);
    $response = $this->performCURL($ch);
    return $response;
  }

  /**
   * Obtain the comment list from a concrete pull request.
   *
   * @param $user  the userInfo
   *
   * @param $repo  the name of accessed repository
   *
   * @param $pr_id the pull request id
   *
   * @return mixed
   */
  protected function getPullRequestComments($user, $repo, $pr_id) {
    $url = self::BASE_URL . 'repos/' . $user->usermname . '/' . $repo
      . '/pulls/' . $pr_id . '/comments?per_page=' . PER_PAGE;
    $ch = $this->getConfiguredCURL($url, $user);
    $response = $this->performCURL($ch);
    return $response;
  }
}