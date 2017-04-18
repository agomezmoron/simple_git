<?php

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
   * @param mixed $user
   *   A user object.
   * @param mixed[] $params
   *   An associative array containing params service.
   *
   * @return mixed[]
   *   An associative array with element 'account_id'.
   */
  public static function authorize($user, array $params) {
    $git_service
      = Service\SimpleGitConnectorFactory::getConnector($params['type']);
    $auth_info = $git_service->authorize($params);
    $result = [];

    // 'Access_token'.
    if (!empty($auth_info)) {

      $auth_info
        = array(
          'userInfo' => array(
            'access_info' => array(
              'token' => $auth_info,
            ),
          ),
        );
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
