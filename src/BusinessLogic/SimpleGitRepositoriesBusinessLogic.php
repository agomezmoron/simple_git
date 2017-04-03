<?php

/**
 * @file
 * Contains \Drupal\simple_git\BusinessLogic\SimpleGitRepositoriesBusinessLogic.
 * @author  Alejandro Gómez Morón <amoron@emergya.com>
 * @author  Estefania Barrrera Berengeno <ebarrera@emergya.com>
 * @version PHP: 7
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

  /**
   * It creates a repository in the provided account.
   *
   * @param array $account
   *  Account information.
   * @param array $repository_info
   *  The repository info to create the repository. At least,the keys should be:
   *    'name' => string
   *    'collaborators' => array with the keys:
   *      'username' => 'username 1'
   * @return array $repository
   *  With the created repository
   */
  static function create($account, $repository_info) {
    $repository = [];
    if (!empty($account) && !empty($repository_info) && isset($repository_info['name'])) {
      $params = [];
      $params['userInfo'] = $account;
      $params['repository'] = $repository_info;
      $git_service = Service\SimpleGitConnectorFactory::getConnector($account['type']);
      // TODO: add this to the git service interface $repository = $git_service->createRepository($params);
    }
    return $repository;
  }

  /**
   * It checks if exists a repository with the provided info.
   *
   * @param array $account
   *  Account information.
   * @param array $repository_info
   *  The repository info to be checked. The key "name" is needed.
   * @return boolean exists
   *  true if the repository exists.
   */
  static function exists($account, $repository_info) {
    $exists = false;
    if (!empty($account)&& !empty($repository_info) && isset($repository_info['name'])) {
      $params = [];
      $params['userInfo'] = $account;
      $params['repository'] = $repository_info;
      $git_service = Service\SimpleGitConnectorFactory::getConnector($account['type']);
      $exists = $git_service->existsRepository($params);
    }
    return $exists;
  }

}