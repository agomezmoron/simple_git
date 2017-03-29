<?php

/**
* @file
* Contains \Drupal\simple_git\BusinessLogic\SimpleGitPullRequestsBusinessLogic.
*/
namespace Drupal\simple_git\BusinessLogic;

use \Drupal\simple_git\Service;

/**
 * Class SimpleGitPullRequestsBusinessLogic.
 *
 * Generate a Pull Requests.
 *
 * @package Drupal\simple_git\BusinessLogic
 */
class SimpleGitPullRequestsBusinessLogic {

  /**
   * Get List pull requests.
   *
   * @param array $accounts
   *   Account information.
   *
   * @param string $repositories
   *   An array with all the repositories associated.
   *
   * @return array $pull_requests
   *  Contains user's pull requests.
   */
  static function getPullRequests($accounts, $repositories) {
    $pull_requests = [];

    // group repositories by $account
    foreach($accounts as &$account) {
      $params = [];
      $params['repositories'] = SimpleGitRepositoriesBusinessLogic::filterRepositoriesByAccount($account, $repositories);
      $params['userInfo'] = $account;
      $git_service = Service\SimpleGitConnectorFactory::getConnector($account['type']);
      $pull_requests_by_account = $git_service->getPullRequestsList($params);
      if (!empty($pull_requests_by_account)) {
        $pull_requests = array_merge($pull_requests, $pull_requests_by_account);
      }
    }

    // removing duplicated pull requests
    $filtered_pull_requests = [];
    $added_prs = [];

    foreach ($pull_requests as $pull_request) {
      if (!in_array($pull_request['id'], $added_prs)) {
        $filtered_pull_requests[] = $pull_request;
        $added_prs[] = $pull_request['id'];
      }
    }

    return $filtered_pull_requests;
  }

  /**
   * Get pull request.
   *
   * @param int $account_id
   *   A id account.
   *
   * @param string $repo
   *   A string with URL of the repositories.
   *
   * @param int $id
   *
   * @param array $user
   *   An associative array containing structure user.
   *
   * @return array $pr
   *   Contains user's pull request.
   */
  static function getPullRequest($account_id, $repo, $id, $user) {
    $pr = array();
    $account = SimpleGitAccountBusinessLogic::getAccountByAccountId($user,$account_id);
    if (!empty($account)) {
      $params['userInfo'] = $account;
      $params['repo'] = $repo;
      $params['id'] = $id;
      $git_service = Service\SimpleGitConnectorFactory::getConnector($account['type']);
      $pr = $git_service->getPullRequest($params);
    }
    return $pr;
  }


}