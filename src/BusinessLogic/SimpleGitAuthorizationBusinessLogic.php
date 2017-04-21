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
   *   An associative array with element 'accountId'.
   */
  public static function authorize($user, array $params) {
    $git_service
      = Service\SimpleGitConnectorFactory::getConnector($params['type']);
    $auth_info = $git_service->authorize($params);
    $result = [];

    // 'Access_token'.
    if (!empty($auth_info)) {

      $auth_info
        = [
        'userInfo' => [
          'access_info' => [
            'token' => $auth_info,
          ],
        ],
      ];
      $accounts = SimpleGitAccountBusinessLogic::getAccounts($user);
      $git_account = $git_service->getAccount($auth_info);
      $exist = FALSE;
      foreach ($accounts as &$account) {
        if ($account['username'] == $git_account['username']
          && $account['type'] == $git_account['type']
        ) {
          $exist = TRUE;
        }
      }
      if ($exist) {
        $result['status'] = 409;
      }
      elseif (isset($git_account['username'])) {
        $result = $git_account;
        $git_account['access_info']
          = $auth_info['userInfo']['access_info'];
        $account_info
          = SimpleGitAccountBusinessLogic::addOrUpdateAccount(
          $user, $git_account
        );
        $accountIDs = [];
        $accountIDs = array_column($account_info, 'accountId');
        $result['accountId'] = end($accountIDs);
      }
    }
    return $result;
  }

}



