<?php

namespace Drupal\simple_git\Service;

use Drupal\simple_git\Service\SimpleGitConnectorInterface;

/**
 * Class SimpleGitLabConnectorService.
 *
 * @package Drupal\simple_git\Service
 */
class SimpleGitLabConnectorService extends SimpleGitConnector {

  /**
   * URL of GitLab API.
   */
  const BASE_URL = 'https://gitlab.com/';

  /**
   * SimpleGitLabConnectorService constructor.
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
   *
   *   parameters =
   *   'client_id=APP_ID&client_secret=APP_SECRET&code=RETURNED_CODE&
   *   grant_type=authorization_code&redirect_uri=REDIRECT_URI'
   *   https://gitlab.example.com/oauth/authorize?client_id=APP_ID&
   *   redirect_uri=REDIRECT_URI&response_type=code&state=your_unique_state_hash
   */
  public function authorize($params) {
    if ($params['code'] && $params['state']) {
      $code = $params['code'];
      $state = $params['state'];
      $settings = $this->getConnectorConfig();
      // Url to attack.
      $url = self::BASE_URL . '/oauth/authorize';
      // Set parameters.
      $parameters = array(
        'client_id' => $settings['app_id'],
        'client_secret' => $settings['app_secret'],
        'redirect_uri' => $settings['redirect_uri'],
        'response_type' => $code,
        'state' => $state,
      );
      // Open curl stream.
      $ch = $this->getConfiguredCURL($url);
      // Set the url, number of POST vars, POSTdata.
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, count($parameters));
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
      curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $response = $this->performCURL($ch);
      // Exposing the access token if it's necessary.
      $access_token = $response['access_token'];
      $token_type = $response['token_type'];

      // Return the obtained token.
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
   * @return mixed
   *   Configured CURL
   */
  protected function getConfiguredCURL($url, $user = NULL) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if (!is_null($user) && !is_null($user['access_info'])
      && isset($user['access_info']['token'])
    ) {
      $headers = $this->getHeaders($user['access_info']['token']);
    }
    else {
      $headers = $this->getHeaders();
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
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
  protected function getHeaders($token = NULL) {
    $headers = [];
    $headers[] = 'Accept: application/json';
    // If we have the security token.
    if (is_null($token)) {
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
    curl_close($ch);
    return $data;
  }

  /**
   * List repository branches.
   *
   * @param array $params
   *   To retrieves the available branches.
   *
   * @return array
   *   Array with all the branches.
   */
  public function getBranchesList(array $params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      // The ID of a project.
      $id = $params['id_proyect'];
      $url = self::BASE_URL . '/projects/' . $id . '/repository/branches';
      $ch = $this->getConfiguredCURL($url, $user);
      $repositories = $this->performCURL($ch);
      $response = array();
      foreach ($repositories as $repo) {
        $repo['parent'] = $repo['parent'] ? TRUE : FALSE;
        array_push(
          $response, $this->buildResponse($repo, self::BRANCH)
        );
      }
      return $response;
    }
  }

  /**
   * Gets all the branches with detailed information.
   *
   * @param array $params
   *   To retrieve all the branches.
   *
   * @return array
   *   With all the branches.
   */
  public function getBranches(array $params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      // The ID of a project.
      $id = $params['id_proyect'];
      // The name of the branch.
      $branch = $params['branch'];
      $url = self::BASE_URL . '/projects/' . $id . '/repository/branches/'
        . $branch;
      $ch = $this->getConfiguredCURL($url, $user);
      $repos = $this->performCURL($ch);
      $response = array();
      foreach ($repos as $repo) {
        $repo['parent'] = $repo['parent'] ? TRUE : FALSE;
        array_push(
          $response, $this->buildResponse($repo, self::BRANCH)
        );
      }
      return $response;
    }
  }

  /**
   * It retrieves a commit information.
   *
   * @param array $params
   *   To retrieve the commit.
   *
   * @return array
   *   With the commit information.
   */
  public function getCommit(array $params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      // The ID of a project.
      $id = $params['id_proyect'];
      $url = self::BASE_URL . '/projects/' . $id . '/repository/commits';
      $ch = $this->getConfiguredCURL($url, $user);
      $repos = $this->performCURL($ch);
      $response = array();
      foreach ($repos as $repo) {
        $repo['parent'] = $repo['parent'] ? TRUE : FALSE;
        array_push(
          $response, $this->buildResponse($repo, self::COMMIT)
        );
      }
      return $response;
    }
  }

  /**
   * List projects.
   *
   * @param array $params
   *   To retrieve the list project.
   *
   * @return array
   *   Array With the projects information.
   */
  public function getProjectsList(array $params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . '/projects';
      $ch = $this->getConfiguredCURL($url, $user);
      $account = $this->performCURL($ch);
      return $this->buildResponse($account, self::PROJECTS);
    }
  }

  /**
   * Get single project.
   *
   * @param array $params
   *   To retrieve the project.
   *
   * @return array
   *   Array With the projects information.
   */
  public function getProjects(array $params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      // The ID or NAMESPACE/PROJECT_NAME of the project.
      $id = $params['id'];
      $url = self::BASE_URL . '/projects/' . $id;
      $ch = $this->getConfiguredCURL($url, $user);
      $account = $this->performCURL($ch);
      return $this->buildResponse($account, self::PROJECTS);
    }
  }

  /**
   * List repository tree.
   *
   * {@inheritdoc}
   *
   * @param mixed[] $params
   *   It needs the userInfo.
   *
   * @return mixed[]
   *   Array With the repositories information.
   */
  public function getRepositoriesList($params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      // The ID of a project.
      $id = $params['id'];
      $url = self::BASE_URL . '/projects/' . $id . '/repository/tree';
      $ch = $this->getConfiguredCURL($url, $user);
      $repos = $this->performCURL($ch);
      $response = array();
      foreach ($repos as $repo) {
        $repo['parent'] = $repo['parent'] ? TRUE : FALSE;
        array_push(
          $response, $this->buildResponse($repo, self::REPOSITORY)
        );
      }
      return $response;
    }

  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $params
   *   It needs the userInfo and the name of the repository requested.
   *
   * @return mixed[]
   *   Information about the repository.
   */
  public function getRepository($params) {
    /*if ($params['userInfo'] && $params['repo']) {
    $user = $params['userInfo'];
    $name = $params['repo'];
    $url = self::BASE_URL . $user->username . '/' . $name;
    $ch = $this->getConfiguredCURL($url, $user);
    $repo = $this->performCURL($ch);
    $response = $this->configureRepositoryFields($repo);
    return $response;
    }*/
    return NULL;
  }

  /**
   * List merge requests.
   *
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo and the name of the repository to see
   *   its associated pull requests.
   *
   * @return array
   */
  public function getPullRequestsList($params) {
    if ($params['userInfo'] && $params['repo']) {
      $user = $params['userInfo'];
      $id = $params['id'];
      $url = self::BASE_URL . '/projects/' . $id . '/merge_requests';
      $ch = $this->getConfiguredCURL($url, $user);
      $prs = $this->performCURL($ch);
      return $this->buildResponse($prs, self::PULL_REQUEST);
    }

  }

  /**
   * {@inheritdoc}
   *
   * @param array $params
   *   It needs the userInfo, the name of accessed repo and the id
   *   of the concrete pull request.
   *
   * @return array
   *   Information about the pull request.
   */
  public function getPullRequest($params) {
    if ($params['userInfo'] && $params['repo'] && $params['id']) {
      $user = $params['userInfo'];
      // The ID of a project.
      $id = $params['id'];
      // The internal ID of the merge request.
      $merge_request_iid = $params['merge_request_iid'];
      $url = self::BASE_URL . 'projects/' . $id . '/merge_requests/'
        . $merge_request_iid;
      $ch = $this->getConfiguredCURL($url, $user);
      $pr = $this->performCURL($ch);
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
      $url = self::BASE_URL . 'users/';
      $ch = $this->getConfiguredCURL($url, $user);
      $account = $this->performCURL($ch);
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
    return ModuleConstantInterface::GIT_TYPE_GITLAB;
  }

  /**
   * Obtain the user detail of a non-logged user.
   *
   * @param mixed[] $params
   *   It needs the userName.
   *
   * @return mixed
   *   Information about the user.(Non-logged user).
   */
  protected function getUserDetail($params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . "users/" . $user->id;
      $ch = $this->getConfiguredCURL($url, $user);
      $response = $this->performCURL($ch);
      return $response;
    }
  }

  /**
   * Obtain the commit list from a concrete pull request.
   *
   * @param mixed $user
   *   The userInfo.
   * @param mixed $merge_request_iid
   *   The name of merge request.
   * @param mixed $pr_id
   *   The pull request id.
   *
   * @return mixed
   *   Information about the commits of the pull requests
   */
  protected function getPullRequestCommits($user, $merge_request_iid, $pr_id) {
    $url = self::BASE_URL . '/projects/' . $pr_id . './merge_requests/'
      . $merge_request_iid . '/commits';
    $ch = $this->getConfiguredCURL($url, $user);
    $response = $this->performCURL($ch);
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
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCustomMappings() {
    $this->mappings[self::PULL_REQUEST] = array(
      'pr_id' => 'id',
      'pr_iid' => 'iid',
      'pr_target_branch' => 'target_branch',
      'project_id' => 'project_id',
      'title' => 'title',
      'author_id' => 'author->id',
      'author_username' => 'author->username',
      'author_name' => 'author->name',
      'author_created_at' => 'author->created_at',
      'assignee_id' => 'assignee->id',
      'assignee_username' => 'assignee->username',
      'assignee_name' => 'assignee->name',
      'assignee_created_at' => 'assignee->created_at',
    );
    $this->mappings[self::ACCOUNT] = array(
      'name' => 'name',
      'user' => 'username',
      'photo' => 'avatar_url',
      'id' => 'id',
      'location' => 'location',
    );
    $this->mappings[self::REPOSITORY] = array(
      'id' => 'id',
      'name' => 'name',
      'type' => 'type',
      'path' => 'path',
    );
    $this->mappings[self::BRANCH] = array(
      'name_branch' => 'name',
      'commit_author' => 'commit->author_name',
      'commit_title' => 'commit->author_title',
      'commit_parentsIds' => 'commit->parent_ids',
    );
    $this->mappings[self::COMMIT] = array(
      'id' => 'id',
      'title' => 'title',
      'author' => 'author',
      'committed_date' => 'committed_date',
      'created_at' => 'created_at',
      'parent_ids' => 'parent_ids',
    );
    $this->mappings[self::PROJECTS] = array(
      'id_project' => 'id',
      'description' => 'description',
      'visibility_level' => 'visibility',
      'ssh_url_to_repo' => 'ssh_url_to_repo',
      'http_url_to_repo' => 'http_url_to_repo',
      'web_url' => 'web_url',
      'tag_list' => 'tag_list',
      'owner_id' => 'owner->id',
      'owner_name' => 'owner->name',
    );
  }

}
