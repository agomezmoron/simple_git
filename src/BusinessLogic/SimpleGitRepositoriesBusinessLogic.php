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
  static function getRepositories($accounts) {
    //    $repositories = [];
    foreach ($accounts as $account) {
      $params['userInfo'] = $account;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $params['userInfo']['type']
      );
      $repositories = $git_service->getRepositoriesList($params);
      foreach ($repositories as &$repository) {
        $repository['accountId'] = $account['account_id'];
      }
      /*$repositories = array_merge(
        $repositories, $repositoriesByAccount*/
      //);
    }

    // Removing duplicated repositories.
    $filtered_repositories = [];
    $added_repos = [];

    foreach ($repositories as $repository) {
      $to_be_added = FALSE;
      if (!in_array($repository['id'], $added_repos)) {


        $to_be_added = TRUE;
        $added_repos[] = $repository['id'];

        $filtered_repositories[] = $repository;
      }
      if ($repository['canAdmin'] == TRUE) {

        // if the repositoy is duplicated, we add the next account with its admin permisisons
        if (!$to_be_added) {
          $position = array_search($repository['id'], $added_repos);
          if ($filtered_repositories[$position]['canAdmin'] == FALSE &&
            $repository['canAdmin'] == TRUE
          ) {
            $filtered_repositories[$position]['accountId'] =
              $repository['accountId'];
            $filtered_repositories[$position]['canAdmin'] =
              $repository['canAdmin'];
          }
        }
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
  static function getRepository($account_id, $repo, $user) {
    $repository = [];
    $account = SimpleGitAccountBusinessLogic::getAccountByAccountId(
      $user, $account_id
    );
    if (!empty($account)) {
      $params = [];
      $params['userInfo'] = $account;
      $params['repository'] = ['name' => $repo];
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $account['type']
      );
      $repository = $git_service->getRepository($params);
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
  static function filterRepositoriesByAccount($account, &$repositories) {
    return array_filter(
      $repositories, function ($repository) use ($account) {
      return $repository['username'] == $account['username']
        || $repository['account'] == $account['username'];
    }
    );
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
  static function exists($account, $repository_info) {
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
      $exists = $git_service->existsRepository($params);
    }
    return $exists;
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
  function create($account, $repository_info) {
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
      // @TODO: add this to the git service interface
      // $repository = $git_service->createRepository($params);
    }
    return $repository;
  }

}
