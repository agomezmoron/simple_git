<?php

namespace Drupal\simple_git\BusinessLogic;

use Drupal\simple_git\Service;

/**
 * Class SimpleGitCollaboratorsBusinessLogic.
 *
 * @package Drupal\simple_git\BusinessLogic
 */
class SimpleGitCollaboratorsBusinessLogic {

  /**
   * Get collaborators.
   *
   * @param int $account
   *   An associative array containing structure account.
   * @param string $repo
   *   An associative array containing structure repository.
   * @param string $collaborator
   *   An collaborator name.
   *
   * @return array
   *   Contains collaborator's repository.
   */
  public static function getCollaborators($account, $repo) {
    $collaborators = [];
    if (!empty($account)) {
      $params['userInfo'] = $account;
      $params['repository'] = $repo;
      //$params['collaborator']['username'] = $collaborator;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $account['type']
      );
      $collaborators = $git_service->getCollaboratorsList($params);

    }
    return $collaborators;
  }

  /**
   * It checks if exists a collaborator with the provided info.
   *
   * @param array $account
   *   An associative array containing structure account.
   * @param array $repository_info
   *   The repository info to be checked. The key "name" is needed.
   * @param string $collaborator
   *   An collaborator name.
   *
   * @return bool
   *   true if the repository exists.
   */
  public static function exists($account, $repository_info, $collaborator) {
    $exists = false;
    if (!empty($account) && !empty($repository_info)
      && isset($repository_info['name'])
    ) {
      $params = [];
      $params['userInfo'] = $account;
      $params['repository'] = $repository_info;
      $params['collaborator']['username'] = $collaborator;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $account['type']
      );
      $exists = $git_service->checkIfUserCollaborator($params);

    }
    return $exists;
  }

  /**
   * It creates a collaborator in the provided repository.
   *
   * @param array $user
   *   An associative array containing structure account.
   * @param array $repository_info
   *   An associative array containing structure repository.
   * @param string $collaborator
   *   An collaborator name
   *
   * @return array
   *   With the created collaborators
   */
  static function addCollaborators($account, $repository, $collaborator) {
      if (!empty($account) && !empty($repository)
        && isset($repository['name'])
      ) {
        $params = [];
        $params['userInfo'] = $account;
        $params['repository'] = $repository;
        $params['collaborator']['username'] = $collaborator;
        $git_service = Service\SimpleGitConnectorFactory::getConnector(
          $account['type']
        );
        $existsRepo = SimpleGitRepositoriesBusinessLogic::exists($account,
          $params);
        if ($existsRepo) {
          $collaborator = $git_service->addCollaborator($params);
        }
      }
      return $collaborator;
  }

  /**
   * Delete a collaborators.
   *
   * @param array $user
   *   An associative array containing structure account.
   * @param array $repositories
   *   An associative array containing structure repository.
   * @param string $collaborator
   *   An collaborator name
   *
   * @return array
   *   An associative array containing structure accounts
   */
  public static function deleteCollaborators(
    $account,
    $repository,
    $collaborator
  ) {
      if (!empty($account) && !empty($repository)
        && isset($repository['name'])
      ) {
        $params = [];
        $params['userInfo'] = $account;
        $params['repository'] = $repository;
        $params['collaborator']['username'] = $collaborator;
        $git_service = Service\SimpleGitConnectorFactory::getConnector(
          $account['type']
        );
        $existsRepo = SimpleGitRepositoriesBusinessLogic::exists($account,
          $params);
        if ($existsRepo) {
          $collaborator = $git_service->deleteCollaborator($params);
        }
      }
      return $collaborator;
    }

}
