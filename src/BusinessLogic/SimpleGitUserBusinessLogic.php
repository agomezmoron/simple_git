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
   * @param array $accounts
   *   An associative array containing structure account.
   * @param string $user
   *   A name user.
   *
   * @return array
   *   An associative array containing structure users
   */
  public static function getUser($accounts, $user) {
    $userInfo = [];
    foreach ($accounts as $account) {
      $params['userInfo'] = $account;
      $git_service = Service\SimpleGitConnectorFactory::getConnector(
        $params['userInfo']['type']
      );
      if (!in_array($params['userInfo']['type'],
        array_column($userInfo, 'type'))
      ) {
        $userInfo[] = $git_service->getUserDetail($account, $user);
      }

    }
    if (in_array(NULL, $userInfo)) {
      $userInfo = [];
    }

    return $userInfo;
  }

}
