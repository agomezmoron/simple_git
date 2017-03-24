<?php
/**
* @file
* Contains \Drupal\simple_git\BusinessLogic\SimpleGitPullRequestsBusinessLogic
*/
namespace Drupal\simple_git\BusinessLogic;

use \Drupal\simple_git\Service;

class SimpleGitPullRequestsBusinessLogic {

  /**
   * @param $account_id
   * @param $repo
   * @return array
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
   * @param $account_id
   * @param $repo
   * @param $id
   * @return array
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