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
 * Class SimpleGitHubConnectorService
 * @package Drupal\simple_git\Service
 */
class SimpleGitHubConnectorService extends SimpleGitConnector {

  const BASE_URL = "https://github.com/";

  /**
   * SimpleGitHubConnectorService constructor.
   * It calls to the parent to configure the mappings
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\simple_git\Service\it $params
   * In this case the needed params are the sent state to login and the code returned from login
   * @return mixed the access token to perform the requests
   */
  public function authorize($params) {
    if ($params['code'] && $params['state']) {
      $code = $params['code'];
      $state = $params['state'];
      $settings = $this->getConnectorConfig();
//Url to attack
      $url = self::BASE_URL . "login/oauth/access_token";

//Set parameters
      $parameters = array(
        "client_id" => $settings['app_id'],
        "client_secret" => $settings['app_secret'],
        "code" => $code,
        "redirect_uri" => "",
        "state" => $state
      );
//Open curl stream
      $ch = $this->getConfiguredCURL($url);
//set the url, number of POST vars, POSTdata
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, count($parameters));
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
      $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
      $response = $this->performCURL($ch);
//Exposing the access token if it's necessary
      $access_token = $response['access_token'];
      $token_type = $response['token_type'];
//    error_log('>>>'.print_r(json_decode($access_token), true));
//Return the obtained token3
      return $access_token;
    }
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\simple_git\Service\it $params it needs the userInfo
   * @return array
   */
  public function getRepositoriesList($params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . "user/repos";
      $ch = $this->getConfiguredCURL($url, $user);
      $repos = $this->performCURL($ch);
      $response = array();
      foreach ($repos as $repo) {
        $repo['parent'] = $repo['parent'] ? TRUE : FALSE;
        array_push($response, $this->buildResponse($repo, self::REPOSITORY));
      }
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\simple_git\Service\it $params
   * It needs the userInfo and the name of the repository requested
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
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\simple_git\Service\it $params
   * It needs the userInfo and the name of the repository to see its associated pull requests
   * @return array
   */
  public function getPullRequestsList($params) {
    if ($params['userInfo'] && $params['repo']) {
      $user = $params['userInfo'];
      $repo = $params['repo'];
      $url = self::BASE_URL . "repos/" . $user->username . "/" . $repo . "/pulls";
      $ch = $this->getConfiguredCURL($url, $user);
      $prs = $this->performCURL($ch);
      $response = array();
      foreach ($prs as $pr) {
        array_push($response, $this->getPullRequest(
          array(
            "userInfo" => $user,
            "repo" => $repo,
            "id" => $pr['id']
          )
        )
        );
      }
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\simple_git\Service\it $params
   * It needs the userInfo, the name of accessed repo and the id of the concrete pull request
   * @return array
   */
  public function getPullRequest($params) {
    if ($params['userInfo'] && $params['repo'] && $params['id']) {
      $user = $params['userInfo'];
      $repo = $params['repo'];
      $id = $params['id'];
      $url = self::BASE_URL . "repos/" . $user->username . "/" . $repo . "/pulls/" . $id;
      $ch = $this->getConfiguredCURL($url, $user);
      $pr = $this->performCURL($ch);
      return $this->buildResponse($pr, self::PULL_REQUEST);
    }
  }

  /**
   * Obtain the user detail of a non-logged user
   * @param $params
   * It needs the userName
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
   * @param \Drupal\simple_git\Service\it $params
   * It needs the userInfo
   * @return array
   */
  public function getAccount($params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . "user/";
      $ch = $this->getConfiguredCURL($url, $user);
      $account = $this->performCURL($ch);
      $account['number_of_repos'] = $account['total_private_repos'] + $account['public_repos'];
      return $this->buildResponse($account, self::ACCOUNT);
    }
  }

  /**
   * {@inheritdoc}
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
      'pr_number' => 'number',
      'pr_name' => 'title',
      'pr_desc' => 'body',
      'username' => 'milestone->creator->login',
      'date' => 'milestone->updated_at',
      'commits' => 'commits',
      'comments' => 'comments',
      'from_branch' => 'head->label',
      'to_branch' => 'base->label',
    );
    $this->mappings[self::ACCOUNT] = array(
      'name' => 'name',
      'user' => 'login',
      'photo' => 'avatar_url',
      'id' => 'id',
      'location' => 'location',
      'company' => 'company',
      'repos' => 'number_of_repos' // it is autocalculated on getAccount method.
    );
    $this->mappings[self::REPOSITORY] = array(
      'repo_name' => 'name',
      'owner_user' => 'owner->login',
      'issues' => 'open_issues_count',
      'language' => 'language',
      'updated' => 'pushed_at',
      'forked_origin' => 'parent'
    );
  }

  /** Obtain the commit list from a concrete pull request.
   * @param $user the userInfo
   * @param $repo the name of accessed repository
   * @param $pr_id the pull request id
   * @return mixed
   */
  protected function getPullRequestCommits($user, $repo, $pr_id) {
    $url = self::BASE_URL . "repos/" . $user->usermname . "/" . $repo . "/pulls/" . $pr_id . "/commits";
    $ch = $this->getConfiguredCURL($url, $user);
    $response = $this->performCURL($ch);
    return $response;
  }

  /** Obtain the comment list from a concrete pull request
   * @param $user the userInfo
   * @param $repo the name of accessed repository
   * @param $pr_id the pull request id
   * @return mixed
   */
  protected function getPullRequestComments($user, $repo, $pr_id) {
    $url = self::BASE_URL . "repos/" . $user->usermname . "/" . $repo . "/pulls/" . $pr_id . "/comments";
    $ch = $this->getConfiguredCURL($url, $user);
    $response = $this->performCURL($ch);
    return $response;
  }

  /** Include the headers into the curl request
   * @return array
   */
  protected function getHeaders($token = NULL) {
    $headers = [];
    $headers[] = 'Accept: application/json';
// if we have the security token

    if (is_null($token)) {
      $headers[] = 'Authorization: token ' . $token;
    }
    return $headers;
  }

  /** Configure a basic curl request
   * @param $url the attacked endpoint
   * @param null $username
   * @param null $token
   * These params are 'optional'. (By the moment the only exception is the authorize method).
   * @return resource
   */
  protected function getConfiguredCURL($url, $user = NULL) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if (!is_null($user) && !is_null($user['access_info']) && isset($user['access_info']['token'])) {
      $headers = $this->getHeaders($user['access_info']['token']);
    }
    else {
      $headers = $this->getHeaders();
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    return $ch;
  }

  /** Perform the curl request, close the stream and return the response
   * @param $ch
   * @return mixed
   */
  protected function performCURL(&$ch) {
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }
}