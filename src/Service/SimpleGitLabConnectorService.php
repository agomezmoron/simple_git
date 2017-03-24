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
 * Class SimpleGitLabConnectorService
 * @package Drupal\simple_git\Service
 */
class SimpleGitLabConnectorService extends SimpleGitConnector {

  const BASE_URL = "https://gitlab.com/";

  /**
   * SimpleGitLabConnectorService constructor.
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
   *
   * parameters = 'client_id=APP_ID&client_secret=APP_SECRET&code=RETURNED_CODE&grant_type=authorization_code&redirect_uri=REDIRECT_URI'
   * https://gitlab.example.com/oauth/authorize?client_id=APP_ID&redirect_uri=REDIRECT_URI&response_type=code&state=your_unique_state_hash
   */
  public function authorize($params) {
    if ($params['code'] && $params['state']) {
      $code = $params['code'];
      $state = $params['state'];
      $settings = $this->getConnectorConfig();
//Url to attack
      $url = self::BASE_URL . "/oauth/authorize";

//Set parameters
      $parameters = array(
        "client_id" => $settings['app_id'],
        "client_secret" => $settings['app_secret'],
        "redirect_uri" => $settings['redirect_uri'],
        "response_type" => $code,
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
   * List repository branches
   * @param $params
   * @return array
   */
  public function getBranchesList($params) {
    if($params['userInfo']){
    $user = $params['userInfo'];
    $id = $params['id_proyect'];// The ID of a project
    $url = self::BASE_URL . '/projects/' . $id . '/repository/branches';
      $ch = $this->getConfiguredCURL($url, $user);
      $repos = $this->performCURL($ch);
      $response = array();
      foreach ($repos as $repo) {
        $repo['parent'] = $repo['parent'] ? TRUE : FALSE;
        array_push($response, $this->buildResponse($repo, self::BRANCH));
      }
      return $response;
    }
  }


  /**
   * Get single repository branch
   * @param $params
   * @return array
   */
  public function getBranches($params) {
    if($params['userInfo']){
      $user = $params['userInfo'];
      $id = $params['id_proyect'];// The ID of a project
      $branch = $params['branch']; //The name of the branch
      $url = self::BASE_URL . '/projects/' . $id . '/repository/branches/'.$branch;
      $ch = $this->getConfiguredCURL($url, $user);
      $repos = $this->performCURL($ch);
      $response = array();
      foreach ($repos as $repo) {
        $repo['parent'] = $repo['parent'] ? TRUE : FALSE;
        array_push($response, $this->buildResponse($repo, self::BRANCH));
      }
      return $response;
    }
  }

  public function getCommit($params) {
    if($params['userInfo']){
      $user = $params['userInfo'];
      $id = $params['id_proyect'];// The ID of a project
      $url = self::BASE_URL . '/projects/' . $id . '/repository/commits';
      $ch = $this->getConfiguredCURL($url, $user);
      $repos = $this->performCURL($ch);
      $response = array();
      foreach ($repos as $repo) {
        $repo['parent'] = $repo['parent'] ? TRUE : FALSE;
        array_push($response, $this->buildResponse($repo, self::COMMIT));
      }
      return $response;
    }
  }


  /**
   * List projects
   * @param $params
   * @return array
   */
  public function getProjectsList($params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . "/projects";
      $ch = $this->getConfiguredCURL($url, $user);
      $account = $this->performCURL($ch);
      return $this->buildResponse($account, self::PROJECTS);
    }
  }

  /**
   * Get single project
   * @param $params
   * @return array
   */
  public function getProjects($params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $id=$params['id'];//The ID or NAMESPACE/PROJECT_NAME of the project
      $url = self::BASE_URL . "/projects/".$id;
      $ch = $this->getConfiguredCURL($url, $user);
      $account = $this->performCURL($ch);
      return $this->buildResponse($account, self::PROJECTS);
    }
  }

  /**
   * List repository tree
   * {@inheritdoc}
   * @param \Drupal\simple_git\Service\it $params it needs the userInfo
   * @return array
   */
  public function getRepositoriesList($params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $id = $params['id']; //The ID of a project
      $url = self::BASE_URL . '/projects/'.$id . '/repository/tree';
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

  public function getRepository($params) {
    /*if ($params['userInfo'] && $params['repo']) {
      $user = $params['userInfo'];
      $name = $params['repo'];
      $url = self::BASE_URL . $user->username . "/" . $name;
      $ch = $this->getConfiguredCURL($url, $user);
      $repo = $this->performCURL($ch);
      $response = $this->configureRepositoryFields($repo);
      return $response;
    }*/
    return null;
  }

  /**
   * List merge requests
   * {@inheritdoc}
   * @param \Drupal\simple_git\Service\it $params
   * It needs the userInfo and the name of the repository to see its associated pull requests
   * @return array
   */
  public function getPullRequestsList($params) {
    if ($params['userInfo'] && $params['repo']) {
      $user = $params['userInfo'];
      $id = $params['id'];
      $url = self::BASE_URL . '/projects/'.$id.'/merge_requests';
      $ch = $this->getConfiguredCURL($url, $user);
      $prs = $this->performCURL($ch);
      return $this->buildResponse($prs, self::PULL_REQUEST);
    }

  }

  /**
   *
   * {@inheritdoc}
   * @param \Drupal\simple_git\Service\it $params
   * It needs the userInfo, the name of accessed repo and the id of the concrete pull request
   * @return array
   */
  public function getPullRequest($params) {
    if ($params['userInfo'] && $params['repo'] && $params['id']) {
      $user = $params['userInfo'];
      $id = $params['id'];//The ID of a project
      $merge_request_iid= $params['merge_request_iid'];//The internal ID of the merge request
      $url = self::BASE_URL . 'projects/'.$id.'/merge_requests/'.$merge_request_iid;
      $ch = $this->getConfiguredCURL($url, $user);
      $pr = $this->performCURL($ch);
      return $this->buildResponse($pr, self::PULL_REQUEST);
    }
  }

  /**
   *
   * Obtain the user detail of a non-logged user
   * @param $params
   * It needs the userName
   * @return mixed
   */
  protected function getUserDetail($params) { //Non-logged user
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . "users/" . $user->id;
      $ch = $this->getConfiguredCURL($url, $user);
      $response = $this->performCURL($ch);
      return $response;
    }
  }

  /**
   * ------
   * {@inheritdoc}
   * @param \Drupal\simple_git\Service\it $params
   * It needs the userInfo
   * @return array
   */
  public function getAccount($params) {
    if ($params['userInfo']) {
      $user = $params['userInfo'];
      $url = self::BASE_URL . "users/";
      $ch = $this->getConfiguredCURL($url, $user);
      $account = $this->performCURL($ch);
      // $account['number_of_repos'] = $account['total_private_repos'] + $account['public_repos'];
      return $this->buildResponse($account, self::ACCOUNT);
    }
  }



  /**
   * {@inheritdoc}
   * @return string
   */
  public function getConnectorType() {
    return GIT_TYPE_GITLAB;
  }



  /** Obtain the commit list from a concrete pull request.
   * @param $user the userInfo
   * @param $repo the name of accessed repository
   * @param $pr_id the pull request id
   * @return mixed
   */
  protected function getPullRequestCommits($user, $merge_request_iid, $pr_id) {
    $url = self::BASE_URL . '/projects/'.$pr_id.'./merge_requests/'.$merge_request_iid.'/commits';
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
//    $url = self::BASE_URL . "repos/" . $user->usermname . "/" . $repo . "/pulls/" . $pr_id . "/comments";
//    $ch = $this->getConfiguredCURL($url, $user);
//    $response = $this->performCURL($ch);
//    return $response;
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
      //'repos' => 'number_of_repos' // it is autocalculated on getAccount method.
    );
    $this->mappings[self::REPOSITORY] = array(
      'id' => 'id',
    'name'=> 'name',
    'type' => 'type',
    'path' => 'path'
    );
    $this->mappings[self::BRANCH] = array(
      'name_branch' =>'name',
      'commit_author' => 'commit->author_name',
      'commit_title' => 'commit->author_title',
      'commit_parentsIds' => 'commit->parent_ids'
    );
    $this->mappings[self::COMMIT] = array(
      'id' =>'id',
      'title' => 'title',
      'author' => 'author',
      'committed_date' =>'committed_date',
      'created_at' => 'created_at',
      'parent_ids' => 'parent_ids'
    );
    $this->mappings[self::PROJECTS] = array(
      'id_project' => 'id',
      'description' => 'description',
      'visibility_level' => 'visibility',
      'ssh_url_to_repo' => 'ssh_url_to_repo',
      'http_url_to_repo' => 'http_url_to_repo',
      'web_url' => 'web_url',
      'tag_list'=> 'tag_list',
      'owner_id' => 'owner->id',
      'owner_name' => 'owner->name'
    );
  }

}