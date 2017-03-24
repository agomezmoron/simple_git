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
   * Get pull requests.
   *
   * @param int $account_id
   *   A id account.
   *
   * @param string $repo
   *   A string with URL of the repositories.
   *
   * @param array $user
   *   An associative array containing structure user.
   *
   * @return array $pr
   *  Contains user's pull request.
   */
  function getPullRequests($account_id, $repo, $user) {
    $pr = array();
    $account = SimpleGitAccountBusinessLogic::getAccountByAccountId($user, $account_id);
    if (!empty($account)) {
      $params['userInfo'] = $account;
      $params['repo'] = $repo;
      $git_service = Service\SimpleGitConnectorFactory::getConnector($account['type']);
      $pr = $git_service->getPullRequestsList($params);
    }
    return $pr;
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
  function getPullRequest($account_id, $repo, $id, $user) {
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