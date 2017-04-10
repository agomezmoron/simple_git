<?php

namespace Drupal\simple_git\BusinessLogic;

use Drupal\simple_git\Service;

/**
 * The base class for all account business logic.
 *
 * @package Drupal\simple_git\BusinessLogic
 */
abstract class SimpleGitUserBusinessLogic {

  /**
   * Returns user.
   *
   * @param int $user
   *   An associative array containing structure user.
   *
   * @return array
   *   An associative array containing structure accounts
   */
  public static function getUser($account, $user) {
    $userInfo = [];
    $git_service = Service\SimpleGitConnectorFactory::getConnector(
      $account['type']
    );
    $userInfo = $git_service->getUserDetail($account, $user);
    return $userInfo;
  }

}
