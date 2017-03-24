<?php

/**
 * @file
 * Contains \Drupal\simple_git\BusinessLogic\SimpleGitAccountBusinessLogic.
 */
namespace Drupal\simple_git\BusinessLogic;

abstract class SimpleGitAccountBusinessLogic {

  /**
   * Returns an account using the account Id.
   *
   * @param int $account_id
   *   A id of account.
   *
   * @return array $result
   *   An associative array with element 'account_id'.
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
   * Create account.
   *
   * @param array $accounts
   *   An associative array containing structure account.
   *
   * @return array $account
   *   An associative array with element 'account_id', 'type', 'name',
   *   'access_info' for create account.
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
   * Modify access info.
   *
   * @param array $account
   *   An associative array containing structure account.
   *
   * @return array $access_info
   *   An associative array with element 'token', 'expires_in', 'refresh_token'.
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
   * Returns accounts.
   *
   * @param array $user
   *   An associative array containing structure user.
   *
   * @return mixed
   */
  static function getAccounts($user) {
    return Drupal::service('user.data')
      ->get(MODULE_SIMPLEGIT, $user->id(), 'accounts');
  }

  /**
   * Modify account.
   *
   * @param array $user
   *   An associative array containing structure user.
   *
   * @param array $account
   *   An associative array containing structure account.
   *
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
   * Modify multiple accounts.
   *
   * @param array $user
   *   An associative array containing structure user.
   *
   * @param array $accounts
   *   An associative array containing structure account.
   *
   * @return mixed
   */
  static function setAccounts($user, $accounts) {
    foreach ($accounts as $account) {
      $last_accounts_list = self::setAccount($user, $account);
    }
    return $last_accounts_list;
  }

  /**
   * Check for the user data.
   *
   * @param array $db_users
   *  A user account object of the database.
   *
   * @param array $new_user
   *    A new user account object.
   *
   * @return array $db_users
   *   Contains the new user.
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
   * Check the information of the access data.
   *
   * @param array $db_user
   *  A user account object of the database.
   *
   * @param array $new_user
   *   A new user account object.
   *
   * @return array $db_user
   *   An associative array with user.
   *
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