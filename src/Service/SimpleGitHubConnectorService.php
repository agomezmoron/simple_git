<?php

/**
 * @file
 * Contains \Drupal\simple_git\Service\SimpleGitHubConnectorService.
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

/**
 * This service manage the requests to the github's API.
 *
 * Class SimpleGitHubConnectorService.
 *
 * @package Drupal\simple_git\Service
 */
class SimpleGitHubConnectorService extends SimpleGitConnector {

  const BASE_URL = "https://api.github.com/";

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
   * @param \Drupal\simple_git\Service\it $params *
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
      $url = "https://github.com/login/oauth/access_token";
      //Set parameters
      $parameters = array(
        "client_id" => $settings['app_id'],
        "client_secret" => $settings['app_secret'],
        "code" => $code,
        "state" => $state
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
   * {@inheritdoc}
   *
   * @param \Drupal\simple_git\Service\it $params it needs the userInfo
   *
   * @return array
   */
  public function getRepositoriesList($params) {
    $response = array();
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . "user/repos?per_page=500";
      $ch = $this->getConfiguredCURL($url, $user);
      $repositories = $this->performCURL($ch);
      foreach ($repositories as $repo) {
        error_log($user['username'].':::'.$repo['name']);
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
   * @param \Drupal\simple_git\Service\it $params *
   *  It needs the userInfo and the name of the repository requested.
   *
   * @return mixed
   */
  public function getRepository($params) {
    if ($params['userInfo'] && $params['repo']) {
      $user = $params['userInfo'];
      $name = $params['repo'];
      $url = self::BASE_URL . $user->username . "/" . $name;
      $ch = $this->getConfiguredCURL($url, $user);
      $repo = $this->performCURL($ch);
      $response = $this->configureRepositoryFields($repo);
      $response['account'] = $user['username'];
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\simple_git\Service\it $params *
   *  It needs the userInfo and the name of the repository to see its associated
   *  pull requests.
   *
   * @return array
   */
  public function getPullRequestsList($params) {
    $pull_requests = [];
    if ($params['userInfo'] &&  $params['repositories']) {
      $user = $params['userInfo'];
      $repositories = $params['repositories'];

      foreach($repositories as $repository) {
        $url = self::BASE_URL . "repos/" . $repository['username'] . "/" . $repository['name'] . "/pulls";
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
   * @param \Drupal\simple_git\Service\it $params
   *  It needs the userInfo, the name of accessed repo and the id of the concrete
   *  pull request.
   *
   * @return array
   */
  public function getPullRequest($params) {
    if ($params['userInfo'] && $params['repo'] && $params['id']) {
      $user = $params['userInfo'];
      $repo = $params['repo'];
      $id = $params['id'];
      $url = self::BASE_URL . "repos/" . $user['username'] . "/" . $repo . "/pulls/" . $id;
      $ch = $this->getConfiguredCURL($url, $user);
      $pr = $this->performCURL($ch);
      return $this->buildResponse($pr, self::PULL_REQUEST);
    }
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
      $url = self::BASE_URL . "users/" . $user->username;
      $ch = $this->getConfiguredCURL($url, $user);
      $response = $this->performCURL($ch);
      return $response;
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
      $url = self::BASE_URL . "user";
      $ch = $this->getConfiguredCURL($url, $user);
      $account = $this->performCURL($ch);
      $account['number_of_repos'] = $account['total_private_repos'] + $account['public_repos'];
      return $this->buildResponse($account, self::ACCOUNT);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   */
  public function getConnectorType() {
    return GIT_TYPE_GITHUB;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCustomMappings() {
    $this->mappings[self::PULL_REQUEST] = array(
      'id' => 'id',
      'title' => 'title',
      'description' => 'body',
      'username' => 'user->login',
      'date' => 'created_at',
      'updated' => 'updated_at',
      'commits' => 'commits',
      'comments' => 'comments',
      'from' => 'head->label',
      'to' => 'base->label',
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
   * @param $user the userInfo
   *
   * @param $repo the name of accessed repository
   *
   * @param $pr_id the pull request id
   *
   * @return mixed
   */
  protected function getPullRequestCommits($user, $repo, $pr_id) {
    $url = self::BASE_URL . "repos/" . $user->usermname . "/" . $repo . "/pulls/" . $pr_id . "/commits";
    $ch = $this->getConfiguredCURL($url, $user);
    $response = $this->performCURL($ch);
    return $response;
  }

  /**
   * Obtain the comment list from a concrete pull request.
   *
   * @param $user the userInfo
   *
   * @param $repo the name of accessed repository
   *
   * @param $pr_id the pull request id
   *
   * @return mixed
   */
  protected function getPullRequestComments($user, $repo, $pr_id) {
    $url = self::BASE_URL . "repos/" . $user->usermname . "/" . $repo . "/pulls/" . $pr_id . "/comments";
    $ch = $this->getConfiguredCURL($url, $user);
    $response = $this->performCURL($ch);
    return $response;
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
    $headers[] = 'User-Agent: GitHub Dashboard';
// if we have the security token

    if (!is_null($token)) {
      $headers[] = 'Authorization: token ' . $token;
    }
    return $headers;
  }

  /**
   * Configure a basic curl request.
   *
   * @param $url the attacked endpoint
   *
   * @param null $username
   *
   * @param null $token
   *  These params are 'optional'. (By the moment the only exception is the
   *  authorize method).
   *
   * @return resource
   */
  protected function getConfiguredCURL($url, $user = NULL) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if (!is_null($user) && !is_null($user['access_info']) && isset($user['access_info']['token'])) {
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
}