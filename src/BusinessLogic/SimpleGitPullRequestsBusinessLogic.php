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
   * @return array $prs
   *  Contains user's pull requests.
   */
  static function getPullRequests($accounts, $repositories) {
    //drupal_flush_all_caches();
    $pull_requests = array();
    // agrupar repos por $account

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

    // removing duplicated PRs
    $pull_requests = array_unique($pull_requests, SORT_REGULAR);

    return $pull_requests;
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