<?php

namespace Drupal\simple_git\BusinessLogic;

use Drupal\simple_git\Interfaces\ModuleConstantInterface;

/**
 * The base class for all account business logic.
 *
 * @package Drupal\simple_git\BusinessLogic
 */
abstract class SimpleGitAccountBusinessLogic {

  /**
   * Returns an account using the account Id.
   *
   * @param int $user
   *   An associative array containing structure user.
   * @param int $accountId
   *   A id of account.
   *
   * @return array
   *   An associative array with element '$accountId'.
   */
  public static function getAccountByAccountId($user, $accountId) {

    // Get user_data, variable "accounts".
    $accounts = [];
    $result = [];
    $accounts = self::getAccounts($user);

    // We have to check if there is an account with the given
    // $account['accountId'] for this $accountId.
    foreach ($accounts as &$account) {
      // If it exists, we have to update it.
      if ($account['accountId'] == $accountId) {
        $result = $account;
        break;
      }
    }

    return $result;
  }

  /**
   * Returns accounts.
   *
   * @param int $user
   *   An associative array containing structure user.
   *
   * @return array
   *   An associative array containing structure accounts
   */
  public static function getAccounts($user) {
    $db_accounts = [];
    $saved_accounts = \Drupal::service('user.data')
      ->get(ModuleConstantInterface::MODULE_SIMPLEGIT, $user->id(),
        'accounts');

    if (!empty($saved_accounts) && is_array($saved_accounts)) {
      $db_accounts = $saved_accounts;
    }
    return $db_accounts;
  }

  /**
   * Delete a account.
   *
   * @param array $accounts
   *   An associative array containing structure account.
   * @param int $accountId
   *   A id of account.
   *
   * @return array
   *   An associative array containing structure accounts
   */
  public static function deleteAccount($accounts, $accountId) {

    $filtered_accounts = [];
    // We have to check if there is an account with the given
    // $account['accountId'] for this $accountId.
    foreach ($accounts as $account) {
      if ($account['accountId'] != $accountId) {
        $filtered_accounts[] = $account;
      }
    }
    return $filtered_accounts;
  }

  /**
   * It adds or update an account to the logged user.
   *
   * @param int $user
   *   The logged user.
   * @param array $git_account
   *   The git account information.
   *
   * @return array
   *   All the associated accounts with the passed one (updated or created).
   */
  public static function addOrUpdateAccount($user, $git_account) {
    $db_accounts = self::getAccounts($user);
    $db_accounts = self::checkAndUpdateUserData($db_accounts, $git_account);
    return self::setAccounts($user, $db_accounts);;
  }

  /**
   * Check and update all the associated linked users for the user data.
   *
   * @param array $db_users
   *   A user account object of the database.
   * @param array $new_user
   *   A new user account object.
   *
   * @return array
   *   Contains the new user.
   */
  public static function checkAndUpdateUserData($db_users, $new_user) {
    $exist = FALSE;

    foreach ($db_users as &$db_user) {
      if ($db_user['username'] == $new_user['username']
        && $db_user['type'] == $new_user['type']
      ) {
        $exist = TRUE;
        $checked_user = self::checkAndUpdateAccessInfo(
          $db_user, $new_user
        );
        if (isset($checked_user)) {
          $db_user = $checked_user;
        }
      }
    }

    if (!$exist) {
      $new_user = self::createAccount($db_users, $new_user);
      $db_users[] = $new_user;
    }
    return $db_users;
  }

  /**
   * Check and update the information of the access data.
   *
   * @param array $db_user
   *   A user account object of the database.
   * @param array $new_user
   *   A new user account object.
   *
   * @return array
   *   An associative array with user.
   */
  public static function checkAndUpdateAccessInfo($db_user, $new_user) {
    switch ($new_user['type']) {
      case ModuleConstantInterface::GIT_TYPE_GITHUB:
        if ($db_user['access_info']['token']
          != $new_user['access_info']['token']
        ) {
          $db_user['access_info']['token']
            = $new_user['access_info']['token'];
        }

        break;

      case ModuleConstantInterface::GIT_TYPE_GITLAB:
        // TODO: Pending to implement Gitlab connector.
        break;

      default:
        if ($db_user['access_info']['token']
          != $new_user['access_info']['token']
        ) {
          $db_user['access_info']['token']
            = $new_user['access_info']['token'];
        }
        break;
    }
    // Updating the rest of the information (fullname, photo, etc).
    $db_user['userInfo'] = $new_user['userInfo'];
    return $db_user;
  }

  /**
   * Create account.
   *
   * @param array $accounts
   *   An associative array containing structure account.
   * @param array $request_account
   *   An associative array containing sctructure request account.
   *
   * @return array
   *   An associative array with element 'accountId', 'type', 'name',
   *   'access_info' for create account.
   */
  public static function createAccount($accounts, $request_account) {
    // Getting the maximim accountId.
    $max_accountId = max(array_column($accounts, 'accountId'));

    $request_account['accountId'] = $max_accountId + 1;
    $request_account['access_info'] = self::setAccessInfo($request_account);

    return $request_account;
  }

  /**
   * Modify access info.
   *
   * @param array $account
   *   An associative array containing structure account.
   *
   * @return array
   *   An associative array with element 'token', 'expires_in', 'refresh_token'.
   */
  public static function setAccessInfo($account) {
    $access_info = [];
    switch ($account['type']) {
      case ModuleConstantInterface::GIT_TYPE_GITHUB:
        $access_info = [
          'token' => $account['access_info']['token'],
        ];
        break;

      case ModuleConstantInterface::GIT_TYPE_GITLAB:
        $access_info = [
          'token' => $account['access_info']['token'],
          'expires_in' => $account['access_info']['expires_in'],
          'refresh_token' => $account['access_info']['refresh_token'],
        ];
        break;

      default:
        $access_info = [
          'token' => $account['access_info']['token'],
        ];
        break;
    }
    return $access_info;
  }

  /**
   * Modify multiple accounts.
   *
   * @param array $user
   *   An associative array containing structure user.
   * @param array $accounts
   *   An associative array containing structure account.
   *
   * @return array
   *   An associative array with element 'accountId', 'type', 'name',
   *   'access_info' for create account.
   */
  public static function setAccounts($user, $accounts) {
    \Drupal::service('user.data')
      ->set(
        ModuleConstantInterface::MODULE_SIMPLEGIT, $user->id(), 'accounts',
        $accounts
      );
    // FIXME: we cannot rebuild all the caches, we have to fix it ASAP.
    drupal_flush_all_caches();
    return $accounts;
  }

}
