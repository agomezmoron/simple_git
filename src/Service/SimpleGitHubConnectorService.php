<?php
/**
 * @file
 * Contains \Drupal\simple_git\Service\SimpleGitHubConnectorService.
 */

namespace Drupal\simple_git\Service;

use Drupal\simple_git\Interfaces\ModuleConstantInterface;
use Drupal\simple_git\Service\SimpleGitConnectorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation;

/**
 * This service manage the requests to the github's API.
 *
 * Class SimpleGitHubConnectorService.
 *
 * @package Drupal\simple_git\Service
 */
class SimpleGitHubConnectorService extends SimpleGitConnector {

  /**
   * URL of GitHub API.
   */
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
   *   In this case the needed params are the sent state to login and the code
   *   returned from login.
   *
   * @return mixed
   *   The access token to perform the requests.
   */
  public function authorize($params) {
    if ($params['code'] && $params['state']) {
      $code = $params['code'];
      $state = $params['state'];
      $settings = $this->getConnectorConfig();
      // Url to user.
      $url = 'https://github.com/login/oauth/access_token';
      // Set parameters.
      $parameters = [
        'client_id' => $settings['app_id'],
        'client_secret' => $settings['app_secret'],
        'code' => $code,
        'state' => $state,
      ];
      // Open curl stream.
      $ch = $this->getConfiguredCURL($url);
      // Set the url, number of POST vars, POSTdata.
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, count($parameters));
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
      // Get status code.
      $status_code = curl_getinfo($ch, CURLINFO_HTTP_COD);
      $response = $this->performCURL($ch);
      // Exposing the access token if it's necessary.
      $access_token = $response['data']['access_token'];
      // Return the obtained access_token.
      return $access_token;
    }
  }

  /**
   * Configure a basic curl request.
   *
   * @param mixed $url
   *   The attacked endpoint.
   * @param mixed $user
   *   In this case the needed $user are the sent access_info.
   *
   * @return resource
   *   Configured CURL
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
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    return $ch;
  }

  /**
   * Include the headers into the curl request.
   *
   * @param mixed $token
   *   The token from the user.
   *
   * @return array
   *   The response headers.
   */
  protected function buildHeaders($token = NULL) {
    $headers = [];
    $headers[] = 'Accept: application/json';
    $headers[] = 'Accept: application/vnd.github.v3+json';
    //    $headers[] = 'Accept: application/vnd.github.swamp-thing-preview+json';
    // By default name.
    $default_app_name = 'GitHub Dashboard';
    $app_name = $default_app_name;
    $connector_config = $this->getConnectorConfig();
    if (!empty($connector_config) && isset($connector_config['app_name'])) {
      $app_name = $connector_config['app_name'];
      if (empty(trim($app_name))) {
        $app_name = $default_app_name;
      }
    }
    $headers[] = 'User-Agent: ' . $app_name;
    // If we have the security token.
    if (!is_null($token)) {
      $headers[] = 'Authorization: token ' . $token;
    }
    return $headers;
  }

  /**
   * Perform the curl request, close the stream and return the response.
   *
   * @param mixed $ch
   *   Request curl.
   *
   * @return mixed
   *   The response.
   */
  protected function performCURL(&$ch) {
    $data = curl_exec($ch);
    $response = [];
    list($header, $body) = explode("\r\n\r\n", $data, 2);
    $header = curl_getinfo($ch);
    curl_close($ch);
    $response['header'] = $header;
    if (!is_array($body) && is_string($body)) {
      $response['data'] = json_decode($body, TRUE);
    }
    else {
      $response['data'] = $body;
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo.
   *
   * @return array
   *   Repositories information.
   */
  public function getRepositoriesList($params) {
    $response = [];
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . 'user/repos?per_page=' . self::PER_PAGE;
      $ch = $this->getConfiguredCURL($url, $user);
      $repositories = $this->performCURL($ch);
      $repositories = $repositories['data'];
      foreach ($repositories as $repo) {
        $repo['parent'] = $repo['parent'] ? TRUE : FALSE;
        $repo = $this->buildResponse($repo, self::REPOSITORY);
        //$repo['account'] = $user['username'];
        array_push($response, $repo);
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo and the name of the repository requested.
   *
   * @return array
   *   Information about the repository.
   */
  public function getRepository($params) {
    $response = [];
    if ($params['userInfo'] && $params['repository']) {
      $user = $params['userInfo'];
      $repository = $params['repository'];
      $url = self::BASE_URL . 'repos/' . $user['username'] . '/'
        . $repository['name'];
      $ch = $this->getConfiguredCURL($url, $user);
      $repo = $this->performCURL($ch);
      $repo = $repo['data'];
      $response = $this->buildResponse($repo, self::REPOSITORY);
      $response['account'] = $user['username'];
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo and the name of the repository requested.
   *
   * @return array
   *   Information about the repository.
   */
  public function addRepository($params) {
    $response = [];
    if ($params['userInfo'] && $params['repository']) {
      $user = $params['userInfo'];
      $repository = $params['repository'];
      $url = self::BASE_URL . $repository['username'] . '/'
        . $repository['name'];
      $ch = $this->getConfiguredCURL($url, $user);
      $repo = $this->performCURL($ch);
      $repo = $repo['data'];
      $response = $this->buildResponse($repo, self::REPOSITORY);
      $response['account'] = $user['username'];
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo and the name of the repository requested.
   *
   * @return array
   *   Information about the repository.
   */
  public function deleteRepository($params) {
    $isDeleted = FALSE;
    if ($params['userInfo'] && $params['repository']) {
      $user = $params['userInfo'];
      $repository = $params['repository'];
      $url = self::BASE_URL . $repository['username'] . '/'
        . $repository['name'];
      $ch = $this->getConfiguredCURL($url, $user);
      $repo = $this->performCURL($ch);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
      $response = $this->performCURL($ch);
      if ($response['header']['http_code'] == 204) {
        $isDeleted = TRUE;
      }
    }
    return $isDeleted;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo and the name of the repository
   *   to see its associated pull requests.
   *
   * @return array
   *   With the Pull Requests of the provided repository.
   */
  public function getPullRequestsList($params) {
    $pull_requests = [];
    if ($params['userInfo'] && $params['repositories']) {
      $user = $params['userInfo'];
      $repositories = $params['repositories'];
      foreach ($repositories as $repository) {
        $url = self::BASE_URL . 'repos/' . $repository['username'] . '/'
          . $repository['name'] . '/pulls?per_page=' . self::PER_PAGE;
        $ch = $this->getConfiguredCURL($url, $user);
        $prs = $this->performCURL($ch);
        $prs = $prs['data'];
        foreach ($prs as $pr) {
          $pull_requests[] = $this->buildResponse($pr,
            self::PULL_REQUEST);
        }
      }
    }
    return $pull_requests;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo,the name of accessed repo and the id of the
   *   concrete pull request.
   *
   * @return array
   *   Information about the pull request.
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
      $pr = $pr['data'];
      return $this->buildResponse($pr, self::PULL_REQUEST);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo.
   *
   * @return array
   *   Information about the account.
   */
  public function getAccount($params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . 'user';
      $ch = $this->getConfiguredCURL($url, $user);
      $account = $this->performCURL($ch);
      $account = $account['data'];
      $account['number_of_repos'] = $account['total_private_repos']
        + $account['public_repos'];
      return $this->buildResponse($account, self::ACCOUNT);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   *   Information about the type connector
   */
  public function getConnectorType() {
    return ModuleConstantInterface::GIT_TYPE_GITHUB;
  }

  /**
   * Obtain the user detail of a non-logged user.
   *
   * @param array[] $account
   *   It needs the userName.
   * @param array[] $user
   *   It needs the user.
   *
   * @return mixed
   *   Information about the user.(Non-logged user).
   */
  public function getUserDetail($account, $user) {
    $url = self::BASE_URL . 'users/' . $user;
    $ch = $this->getConfiguredCURL($url, $account);
    $response = $this->performCURL($ch);
    if ($response['header']['http_code'] != 404 && $response['header']['http_code'] != 400) {
      $response = $response['data'];
      $response = $this->buildResponse($response, self::USER);
    }elseif ($response['header']['http_code'] == 400){
      $response = 'Bad Request';
    }
    else {
      $response = NULL;
    }
error_log('response'.print_r($response,TRUE));
    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo.
   *
   * @return array
   *   Collaborators information.
   */
  public function getCollaboratorsList($params) {
    $response = [];
    if ($params['userInfo'] && $params['repository']) {
      $user = $params['userInfo'];
      $repository = $params['repository'];
      $url = self::BASE_URL . 'repos/' . $repository['username'] . '/' .
        $repository['name'] . '/collaborators';
      $ch = $this->getConfiguredCURL($url, $user);
      $collaborators = $this->performCURL($ch);
      $collaborators = $collaborators['data'];
      foreach ($collaborators as $collaborator) {
        $response[] = $this->buildResponse($collaborator, self::COLLABORATOR);
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo and repository.
   *
   * @return integer
   *   Response if user is a collaborator.
   */
  public function isCollaborator($params) {
    $isCollaborator = FALSE;
    if ($params['userInfo'] && $params['repository']) {
      $user = $params['userInfo'];
      $repository = $params['repository'];
      $collaborator = $params['collaborator'];
      $url = self::BASE_URL . 'repos/' . $repository['username'] . '/' .
        $repository['name'] . '/collaborators/' . $collaborator['username'];
      $ch = $this->getConfiguredCURL($url, $user);
      $response = $this->performCURL($ch);
      if ($response['header']['http_code'] == 204) {
        $isCollaborator = TRUE;
      }
    }
    return $isCollaborator;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo and repository.
   *
   * @return array
   *   Response if user has been added.
   * Note that to prevent abuse you are limited to 50 invitations per 24 hour
   *   period
   */
  public function addCollaborator($params) {
    $response = [];
    $isCollaborator = FALSE;
    if ($params['userInfo'] && $params['repository']) {
      $user = $params['userInfo'];
      $repository = $params['repository'];
      $collaborator = $params['collaborator'];
      $url = self::BASE_URL . 'repos/' . $repository['username'] . '/' .
        $repository['name'] . '/collaborators/' . $collaborator;
      $ch = $this->getConfiguredCURL($url, $user);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
      $response = $this->performCURL($ch);
      if ($response['header']['http_code'] == 204) {
        $isCollaborator = TRUE;
      }
    }
    return $isCollaborator;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo and repository.
   *
   * @return array
   *   Collaborators information.
   */
  public function deleteCollaborator($params) {
    $isCollaborator = FALSE;
    $response = [];
    if ($params['userInfo'] && $params['repository']) {
      $user = $params['userInfo'];
      $repository = $params['repository'];
      $collaborator = $params['collaborator'];
      $url = self::BASE_URL . 'repos/' . $repository['username'] . '/' .
        $repository['name']
        . '/collaborators/' . $collaborator['username'];
      $ch = $this->getConfiguredCURL($url, $user);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
      $response = $this->performCURL($ch);
      if ($response['header']['http_code'] == 204) {
        $isCollaborator = TRUE;
      }
    }
    return $isCollaborator;
  }

  /**
   * Obtain the commit list from a concrete pull request.
   *
   * @param mixed $user
   *   The userInfo.
   * @param mixed $repo
   *   The name of accessed repository.
   * @param mixed $pr_id
   *   The pull request id.
   *
   * @return mixed
   *   Information about the commits of the pull requests
   */
  protected function getPullRequestCommits($user, $repo, $pr_id) {
    $url = self::BASE_URL . 'repos/' . $user['username'] . '/' . $repo
      . '/pulls/' . $pr_id . '/commits';
    $ch = $this->getConfiguredCURL($url, $user);
    $response = $this->performCURL($ch);
    $response = $response['data'];
    return $response;
  }

  /**
   * Obtain the comment list from a concrete pull request.
   *
   * @param mixed $user
   *   The userInfo.
   * @param mixed $repo
   *   The name of accessed repository.
   * @param mixed $pr_id
   *   The pull request id.
   *
   * @return mixed
   *   Information about the comments of the pull requests
   */
  protected function getPullRequestComments($user, $repo, $pr_id) {
    $url = self::BASE_URL . 'repos/' . $user['username'] . '/' . $repo
      . '/pulls/' . $pr_id . '/comments?per_page=' . self::PER_PAGE;
    $ch = $this->getConfiguredCURL($url, $user);
    $response = $this->performCURL($ch);
    $response = $response['data'];
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCustomMappings() {
    $this->mappings[self::PULL_REQUEST] = [
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
      'to_repo_name' => 'base->repo->name',
    ];
    $this->mappings[self::ACCOUNT] = [
      'fullname' => ['name', 'login'],
      'username' => 'login',
      'photoUrl' => 'avatar_url',
      'id' => 'id',
      'email' => 'email',
      'location' => 'location',
      'organization' => 'company',
      'repoNumber' => 'number_of_repos',
      // It is autocalculated on getAccount method.
    ];
    $this->mappings[self::USER] = [
      'id' => 'id',
      'fullname' => ['name', 'login'],
      'username' => 'login',
      'photoUrl' => 'avatar_url',
      'email' => 'email',
    ];
    $this->mappings[self::REPOSITORY] = [
      'id' => 'id',
      'name' => 'name',
      'username' => 'owner->login',
      'issues' => 'open_issues_count',
      'language' => 'language',
      'updated' => 'pushed_at',
      'age' => 'created_at',
      'fork' => 'fork',
      'canAdmin' => 'permissions->admin',
    ];
    $this->mappings[self::COLLABORATOR] = [
      'id' => 'id',
      'username' => 'login',
      'photoUrl' => 'avatar_url',
    ];
  }
}
