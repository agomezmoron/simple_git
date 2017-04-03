<?php

/**
 * @file
 * Contains \Drupal\simple_git\BusinessLogic\SimpleGitAuthorizationBusinessLogic.
 */

namespace Drupal\simple_git\BusinessLogic;

use Drupal\simple_git\Service;


/**
 * The base class for all authorization business logic.
 *
 * @package Drupal\simple_git\BusinessLogic
 */
abstract class SimpleGitAuthorizationBusinessLogic {

  /**
   * Check user authorization.
   *
   * @param array $user
   *   A user object.
   *
   * @param array $params
   *   An associative array containing params service.
   *
   * @return array|mixed
   */
  static function authorize($user, $params) {
    $git_service = Service\SimpleGitConnectorFactory::getConnector(
      $params['type']
    );

    $auth_info = $git_service->authorize($params);
    $result = array();

    // 'access_token'
    if (!empty($auth_info)) {

      $auth_info
        = array('userInfo' => array('access_info' => array('token' => $auth_info)));
      $git_account = $git_service->getAccount($auth_info);

      if (isset($git_account['username'])) {
        $result = $git_account;

        $git_account['access_info']
          = $auth_info['userInfo']['access_info'];
        $account_info
          = SimpleGitAccountBusinessLogic::addOrUpdateAccount(
          $user, $git_account
        );

        $result['account_id'] = $account_info['account_id'];
      }
    }

    return $result;
  }


}