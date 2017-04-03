<?php

/**
 * @file
 * Contains \Drupal\simple_git\BusinessLogic\SimpleGitRepositoriesBusinessLogic.
 */

namespace Drupal\simple_git\BusinessLogic;

use Drupal\simple_git\Service;

/**
 * Class SimpleGitRepositoriesBusinessLogic.
 *
 * @package Drupal\simple_git\BusinessLogic
 */
class SimpleGitRepositoriesBusinessLogic {

  /**
   * Get multiple repositories.
   *
   * @param array $accounts
   *   An associative array containing structure user.
   *
   * @return array $repositories
   *   Contains user's repository.
   */
  static function getRepositories($accounts) {
    $repositories = array();
    foreach ($accounts as $account) {
      $params['userInfo'] = $account;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $params['userInfo']['type']
      );
      $repositories = array_merge(
        $repositories, $git_service->getRepositoriesList($params)
      );
    }

    // removing duplicated repositories
    $filtered_repositories = [];
    $added_repos = [];

    foreach ($repositories as $repository) {
      if (!in_array($repository['id'], $added_repos)) {
        $filtered_repositories[] = $repository;
        $added_repos[] = $repository['id'];
      }
    }

    return $filtered_repositories;
  }


  /**
   * Get repository.
   *
   * @param int    $account_id
   *    A id account.
   *
   * @param string $repo
   *    A string with URL of the repositories.
   *
   * @param array  $user
   *    An associative array containing structure user.
   *
   * @return array $repository
   *   Contains user's repository.
   */
  static function getRepository($account_id, $repo, $user) {
    $repository = array();
    $account = SimpleGitAccountBusinessLogic::getAccountByAccountId(
      $user, $account_id
    );
    if (!empty($account)) {
      $params['userInfo'] = $account;
      $params['repo'] = $repo;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $account['type']
      );
      $repository = $git_service->getRepositoriesList($params);
    }
    return $repository;
  }


  /**
   * It filters and return an array with the repositories where the $account is
   * owner or collaborator.
   *
   * @param array $account
   *  To get his/her repositories.
   * @param array $repositories
   *  To be filtered.
   *
   * @return array $repositories
   *  With the repositories associated to the given $account.
   */
  static function filterRepositoriesByAccount($account, &$repositories) {
    return array_filter(
      $repositories, function ($repository) use ($account) {
      return $repository['username'] == $account['username']
        || $repository['account'] == $account['username'];
    }
    );
  }

}