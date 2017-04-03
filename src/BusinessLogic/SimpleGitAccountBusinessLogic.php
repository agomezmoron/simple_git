<?php

/**
 * @file
 * Contains \Drupal\simple_git\BusinessLogic\SimpleGitAccountBusinessLogic.
 * @author  Alejandro Gómez Morón <amoron@emergya.com>
 * @author  Estefania Barrrera Berengeno <ebarrera@emergya.com>
 * @version PHP: 7
 */

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
   * @package BusinessLogic
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
   * Returns accounts.
   *
   * @param array $user
   *   An associative array containing structure user.
   *
   * @return mixed
   */
  static function getAccounts($user) {
    $db_accounts = [];

    $saved_accounts = \Drupal::service('user.data')
      ->get(ModuleConstantInterface::MODULE_SIMPLEGIT, $user->id(), 'accounts');

    if (!empty($saved_accounts) && is_array($saved_accounts)) {
      $db_accounts = $saved_accounts;
    }

    return $db_accounts;
  }

  /**
   * Delete a account.
   *
   * @param $accounts
   *    An associative array containing structure account.
   *
   * @param $account_id
   *    A id of account.
   *
   * @return array $filetered_accounts
   */
  static function deleteAccount($accounts, $account_id) {

    $filtered_accounts = [];
// we have to check if there is an account with the given $account['account_id'] for this $account_id
    foreach ($accounts as $account) {
      if ($account['account_id'] != $account_id) {
        $filtered_accounts[] = $account;
      }
    }
    return $filtered_accounts;
  }

  /**
   * It adds or update an account to the logged user.
   *
   * @param mixed $user
   *  The logged user.
   * @param array $git_account
   *  The git account information.
   *
   * @return array $db_accounts
   *  All the associated accounts with the passed one (updated or created).
   */
  static function addOrUpdateAccount($user, $git_account) {
    $db_accounts = self::getAccounts($user);
    $db_accounts = self::checkAndUpdateUserData($db_accounts, $git_account);
    return self::setAccounts($user, $db_accounts);
  }

  /**
   * Check and update all the associated linked users for the user data.
   *
   * @param array $db_users
   *    A user account object of the database.
   *
   * @param array $new_user
   *    A new user account object.
   *
   * @return array $db_users
   *   Contains the new user.
   */
  static function checkAndUpdateUserData($db_users, $new_user) {
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
   * Check and update the information of the access data (updating the token if needed).
   *
   * @param array $db_user
   *   A user account object of the database.
   *
   * @param array $new_user
   *   A new user account object.
   *
   * @return array $db_user
   *   An associative array with user.
   *
   */
  static function checkAndUpdateAccessInfo($db_user, $new_user) {
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
        // TODO: Pending to implement Gitlab connector
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
    // updating the rest of the information (fullname, photo, etc).
    $db_user['userInfo'] = $new_user['userInfo'];
    return $db_user;
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

    $request_account['account_id'] = $max_account_id + 1;
    $request_account['access_info'] = self::setAccessInfo($request_account);

    return $request_account;
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
      case ModuleConstantInterface::GIT_TYPE_GITHUB:
        $access_info = array(
          'token' => $account['access_info']['token'],
        );
        break;
      case ModuleConstantInterface::GIT_TYPE_GITLAB:
        $access_info = array(
          'token' => $account['access_info']['token'],
          'expires_in' => $account['access_info']['expires_in'],
          'refresh_token' => $account['access_info']['refresh_token'],
        );
        break;
      default:
        $access_info = array(
          'token' => $account['access_info']['token'],
        );
        break;
    }
    return $access_info;
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
    \Drupal::service('user.data')
      ->set(
        ModuleConstantInterface::MODULE_SIMPLEGIT, $user->id(), 'accounts',
        $accounts
      );
    // FIXME: we cannot rebuild all the caches, we have to fix it ASAP
    drupal_flush_all_caches();
    return $accounts;
  }
}