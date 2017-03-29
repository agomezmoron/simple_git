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
   * @param array $user
   *   An associative array containing structure user.
   *
   * @return array $repositories
   *   Contains user's repository.
   */
  static function getRepositories($accounts) {
    $repositories = array();
    foreach ($accounts as $account) {
      $params['userInfo'] = $account;
      $git_service = Service\SimpleGitConnectorFactory::getConnector( $params['userInfo']['type']);
      $repositories = array_merge($repositories, $git_service->getRepositoriesList($params));
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
  static function getRepository($account_id, $repo, $user) {
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




  static function filterRepositoriesByAccount($account, &$repositories) {
    return array_filter($repositories, function($repository) use($account){
      if ($repository['name'] == 'simple_git') {
        error_log('ALOHA!!'.print_r($repository, true));
        error_log(print_r($account, true));
      }
      return $repository['username'] == $account['username'] || $repository['account'] == $account['username'] ;
    });
  }

}