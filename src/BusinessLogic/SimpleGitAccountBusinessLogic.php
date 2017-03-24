<?php
/**
 * @file
 * Contains \Drupal\simple_git\BusinessLogic\SimpleGitAccountBusinessLogic
 */
namespace Drupal\simple_git\BusinessLogic;

abstract class SimpleGitAccountBusinessLogic {

  /**
   * @param $account_id
   * @return array
   */
  static function getAccountByAccountId($user, $account_id) {

    // get user_data, variable "accounts"
    $accounts = array();

    $accounts = self::getAccounts($user);

    $result = array();

    // we have to check if there is an account with the given $account['account_id'] for this $account_id
    foreach ($accounts as &$account) {
      // if it exists, we have to update it
      if ($account['account_id'] == $account_id) {
        $result = $account;
        break;
      }
    }

    return $result;
  }


  /**
   * @param $accounts
   * @param $git_account
   * @param $connector_type
   * @return array
   */
  static function createAccount($accounts, $request_account) {
    // getting the maximim account_id
    $max_account_id = max(array_column($accounts, 'account_id'));

    $account = array(
      'account_id' => $max_account_id + 1,
      'type' => $request_account['type'],
      'name' => $request_account['name'],
      'access_info' => setAccessInfo($request_account),
    );

    return $account;
  }

  /**
   * @param $account
   * @return array
   */
  static function setAccessInfo($account) {
    $access_info = array();
    switch ($account['type']) {
      case GIT_TYPE_GITHUB:
        $access_info = array(
          'token' => $account['token'],
        );
        break;
      case GIT_TYPE_GITLAB:
        $access_info = array(
          'token' => $account['token'],
          'expires_in' => $account['expires_in'],
          'refresh_token' => $account['refresh_token'],
        );
        break;
      default:
        $access_info = array(
          'token' => $account['token'],
        );
        break;
    }
    return $access_info;
  }

  /**
   * @param $user
   * @return mixed
   */
  static function getAccounts($user) {
    return Drupal::service('user.data')
      ->get(MODULE_SIMPLEGIT, $user->id(), 'accounts');
  }

  /**
   * @param $user
   * @param $accounts
   * @return mixed
   */
  static function setAccount($user, $account) {
    $db_accounts = self::getAccounts($user);

    $new_account = self::createAccount($db_accounts, $account);

    $accounts = self::checkUserData($db_accounts, $new_account);

    return Drupal::service('user.data')
      ->set(MODULE_SIMPLEGIT, $user->id(), 'accounts', $accounts);
  }

  /**
   * @param $user
   * @param $accounts
   * @return mixed
   */
  static function setAccounts($user, $accounts) {
    foreach ($accounts as $account) {
      $last_accounts_list = self::setAccount($user, $account);
    }
    return $last_accounts_list;
  }

  /**
   * @param $db_users
   * @param $new_user
   * @return array
   */
  static function checkUserData($db_users, $new_user) {
    $exist = FALSE;

    foreach ($db_users as $db_user) {
      if ($db_user['username'] == $new_user['username']) {
        $exist = TRUE;
        if ($db_user['type'] == $new_user['type']) {
          $checked_user = checkAccessInfo($db_user, $new_user);
          if (isset($checked_user)) {
            $db_user = $checked_user;
          }
        }
      }
    }

    if (!$exist) {
      $db_users[] = $new_user;
    }

    return $db_users;
  }

  /**
   * @param $db_user
   * @param $new_user
   * @return null
   */
  static function checkAccessInfo($db_user, $new_user) {
    switch ($new_user['type']) {
      case GIT_TYPE_GITHUB:
        if ($db_user['access_info']['token'] != $new_user['access_info']['token']) {
          $db_user['access_info']['token'] = $new_user['access_info']['token'];
        }
        else {
          $db_user = NULL;
        }
        break;
      case GIT_TYPE_GITLAB:
        // TODO: Pending to implement Gitlab connector
        break;
      default:
        if ($db_user['access_info']['token'] != $new_user['access_info']['token']) {
          $db_user['access_info']['token'] = $new_user['access_info']['token'];
        }
        else {
          $db_user = NULL;
        }
        break;
    }
    return $db_user;
  }
}