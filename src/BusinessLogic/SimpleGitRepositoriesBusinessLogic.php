<?php

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
   * @return array
   *   Contains user's repository.
   */
  public static function getRepositories(array $accounts) {
    $repositories = [];
    foreach ($accounts as $account) {
      $params['userInfo'] = $account;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $params['userInfo']['type']
      );
      $repositories = array_merge(
        $repositories, $git_service->getRepositoriesList($params)
      );
    }

    // Removing duplicated repositories.
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
   * @param int $account_id
   *   A id account.
   * @param string $repo
   *   A string with URL of the repositories.
   * @param array $user
   *   An associative array containing structure user.
   *
   * @return array
   *   Contains user's repository.
   */
  public static function getRepository($account_id, $repo, array $user) {
    $repository = [];
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
   * It filters and return an array with the repositories.
   *
   * @param array $account
   *   To get his/her repositories.
   * @param array $repositories
   *   To be filtered.
   *
   * @return array
   *   With the repositories associated to the given $account.
   */
  public static function filterRepositoriesByAccount(
    array $account,
    array &$repositories
  ) {
    return array_filter(
      $repositories, function ($repository) use ($account) {
        return $repository['username'] == $account['username']
        || $repository['account'] == $account['username'];
      }
    );
  }

  /**
   * It creates a repository in the provided account.
   *
   * @param array $account
   *   Account information.
   * @param array $repository_info
   *   The repository info to create the repository.
   *   At least,the keys should be.
   *
   * @var 'name' => string
   * @var 'collaborators' => array with the keys:
   * @var 'username' => 'username 1'
   *
   * @return array
   *   With the created repository
   */
  static function create($account, $repository_info) {
    $repository = [];
    if (!empty($account) && !empty($repository_info)
      && isset($repository_info['name'])
    ) {
      $params = [];
      $params['userInfo'] = $account;
      $params['repository'] = $repository_info;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $account['type']
      );
      // @TODO: add this to the git service interface $repository = $git_service->createRepository($params);
    }
    return $repository;
  }

  /**
   * It checks if exists a repository with the provided info.
   *
   * @param array $account
   *   Account information.
   * @param array $repository_info
   *   The repository info to be checked. The key "name" is needed.
   *
   * @return bool
   *   true if the repository exists.
   */
  public static function exists($account, $repository_info) {
    $exists = FALSE;
    if (!empty($account) && !empty($repository_info)
      && isset($repository_info['name'])
    ) {
      $params = [];
      $params['userInfo'] = $account;
      $params['repository'] = $repository_info;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $account['type']
      );
      $exists = $git_service->existsRepository($params);
    }
    return $exists;
  }

}
