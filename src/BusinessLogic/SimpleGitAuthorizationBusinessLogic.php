<?php
/**
 * @file
 * Contains \Drupal\simple_git\BusinessLogic\SimpleGitAuthorizationBusinessLogic
 */

namespace Drupal\simple_git\BusinessLogic;

use \Drupal\simple_git\Service;
use \Drupal\simple_git\BusinessLogic\SimpleGitAccountBusinessLogic;

abstract class SimpleGitAuthorizationBusinessLogic extends SimpleGitDataBaseBusinnesLogic {

  /**
   * @param $user
   * @param $params
   * @return array|mixed
   */
  static function authorize($user, $params) {
    $git_service = Service\SimpleGitConnectorFactory::getConnector($params['type']);

    $auth_info = $git_service->authorize($params);

    $result = array();

    // 'access_token'
    if (!empty($auth_info)) {
      $git_account = $git_service->getAccount($auth_info);
      if (isset($git_account['user'])) {
        //
        $account_info = SimpleGitAccountBusinessLogic::SaddOrUpdateAccount($user, $git_account, $git_service->getConnectorType());

        $result = $git_account;
        $result['account_id'] = $account_info['account_id'];
      }
    }

    return $result;
  }


}