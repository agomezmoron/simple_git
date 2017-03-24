<?php

/**
 * @file
 * Contains \Drupal\simple_git\BusinessLogic\SimpleGitRepositoriesBusinessLogic.
 */

namespace Drupal\simple_git\BusinessLogic;

use \Drupal\simple_git\Service;

/**
 * Class SimpleGitRepositoriesBusinessLogic.
 *
 * @package Drupal\simple_git\BusinessLogic
 */
class SimpleGitRepositoriesBusinessLogic {

  /**
   * Get multiple repositories.
   *
   * @param int $account_id
   *    A id account.
   *
   * @param array $user
   *   An associative array containing structure user.
   *
   * @return array $repositories
   *   Contains user's repository.
   */
  function getRepositories($user, $account_id) {
    $repositories = array();
    $account = SimpleGitAccountBusinessLogic::getAccountByAccountId($user, $account_id);
    if (!empty($account)) {
      $params['userInfo'] = $account;
      $git_service = Service\SimpleGitConnectorFactory::getConnector($account['type']);
      $repositories = $git_service->getRepositoriesList($params);
    }
    return $repositories;
  }


  /**
   * Get repository.
   *
   * @param int $account_id
   *    A id account.
   *
   * @param string $repo
   *   A string with URL of the repositories.
   *
   * @param array $user
   *   An associative array containing structure user.
   *
   * @return array $repository
   *   Contains user's repository.
   */
  function getRepository($account_id, $repo, $user) {
    $repository = array();
    $account = SimpleGitAccountBusinessLogic::getAccountByAccountId($user, $account_id);
    if (!empty($account)) {
      $params['userInfo'] = $account;
      $params['repo'] = $repo;
      $git_service = Service\SimpleGitConnectorFactory::getConnector($account['type']);
      $repository = $git_service->getRepositoriesList($params);
    }
    return $repository;
  }

}