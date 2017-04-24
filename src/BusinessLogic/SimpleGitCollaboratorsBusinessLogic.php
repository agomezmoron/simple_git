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
   * @param string $repository
   *   An repository name.
   * @param string $owner
   *   An owner name.
   *
   * @return array
   *   Contains collaborator's repository.
   */
  public static function getCollaborators($account, $owner, $repository) {
    $collaborators = [];
    if (!empty($account) && !empty($owner) && !empty($repository)) {
      $params['userInfo'] = $account;
      $params['repository']['name'] = $repository;
      $params['repository']['username'] = $owner;
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
   * @param string $repository
   *   The repository name.
   * @param string $collaborator
   *   An collaborator name.
   * @param string $owner
   *   An owner name.
   *
   * @return bool
   *   true if the repository exists.
   */
  public static function exists($account, $owner, $repository, $collaborator) {
    $exists = FALSE;
    if (!empty($account) && !empty($repository) && !empty($owner) &&
      !empty($collaborator)
    ) {
      $params = [];
      $params['userInfo'] = $account;
      $params['repository']['name'] = $repository;
      $params['repository']['username'] = $owner;
      $params['collaborator']['username'] = $collaborator;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $account['type']
      );
      $exists = $git_service->isCollaborator($params);

    }
    // FIXME: we cannot rebuild all the caches, we have to fix it ASAP.
    drupal_flush_all_caches();
    return $exists;
  }

  /**
   * It creates a collaborator in the provided repository.
   *
   * @param array $account
   *   An associative array containing structure account.
   * @param string $owner
   *   An owner name.
   * @param string $repository
   *   An associative array containing structure repository.
   * @param string $collaborator
   *   An collaborator name
   *
   * @return array
   *   With the created collaborators
   */
  static function addCollaborators(
    $account,
    $owner,
    $repository,
    $collaborator
  ) {
    $iscollaborator = FALSE;
    if (!empty($account) && !empty($repository) && !empty($owner) &&
      !empty($collaborator)
    ) {
      $params = [];
      $params['userInfo'] = $account;
      $params['repository']['name'] = $repository;
      $params['repository']['username'] = $owner;
      $params['collaborator'] = $collaborator;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $account['type']
      );
      $iscollaborator = $git_service->addCollaborator($params);
    }
    return $iscollaborator;
  }

  /**
   * Delete a collaborators.
   *
   * @param array $account
   *   An associative array containing structure account.
   * @param string $owner
   *   An owner name.
   * @param array $repository
   *   An name repository.
   * @param string $collaborator
   *   An collaborator name
   *
   * @return bool
   *   An associative array containing structure accounts
   */
  public static function deleteCollaborators(
    $account,
    $owner,
    $repository,
    $collaborator
  ) {
    $delete = FALSE;
    if (!empty($account) && !empty($repository) && !empty($owner) &&
      !empty($collaborator)
    ) {
      $params = [];
      $params['userInfo'] = $account;
      $params['repository']['name'] = $repository;
      $params['repository']['username'] = $owner;
      $params['collaborator']['username'] = $collaborator;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $account['type']
      );

      $delete = $git_service->deleteCollaborator($params);
    }
    return $delete;
  }

}
