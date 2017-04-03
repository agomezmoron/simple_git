<?php

/**
 * @file
 * Contains \Drupal\simple_git\Plugin\rest\resource\AccountResource.php
 * @author  Alejandro Gómez Morón <amoron@emergya.com>
 * @author  Estefania Barrrera Berengeno <ebarrera@emergya.com>
 * @version PHP: 7
 */

namespace Drupal\simple_git\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\simple_git\BusinessLogic\SimpleGitAccountBusinessLogic;
use Drupal\simple_git\BusinessLogic\SimpleGitAuthorizationBusinessLogic;
use Drupal\simple_git\Interfaces\ModuleConstantInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a Connector Resource.
 *
 * @package Drupal\simple_git\Plugin\rest\resource
 * @RestResource(
 *   id = "simple_git_account_resource",
 *   label = @Translation("Git Account Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/simple_git/account/{account_id}",
 *     "https://www.drupal.org/link-relations/create" = "/api/simple_git/account",
 *   }
 * )
 */
class AccountResource extends ResourceBase {

  /**
   *  A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */

  protected $current_user;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array     $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string    $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed     $plugin_definition
   *   The plugin implementation definition.
   * @param array     $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\ $logger
   *   A logger instance.
   */

  public function __construct(
    array $configuration, $plugin_id, $plugin_definition,
    array $serializer_formats, $logger, AccountProxyInterface $current_user
  ) {
    parent::__construct(
      $configuration, $plugin_id, $plugin_definition, $serializer_formats,
      $logger
    );
    $this->current_user = $current_user;
  }

  /**
   * {@inheritdoc}
   */

  public static function create(
    ContainerInterface $container, array $configuration, $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * It connects the user to with the Git Service given using the given information, returning the account data.
   *
   * @param array $data
   *  Request data.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the Git account data.
   */

  public function post(array $data = []) {
    $user_data = SimpleGitAuthorizationBusinessLogic::authorize(
      $this->current_user, $data
    );

    // an error occurred authenticating
    if (empty($user_data)) {
      throw new HttpException(
        401, t('An error occurred authorizing the user.')
      );
    }

    return new ResourceResponse($user_data);

  }

  /**
   * Responds to DELETE requests.
   *
   * It deletes the sent account.
   *
   * @param $account_id
   *  A id of account
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response with the result status.
   */

  public function delete($account_id) {
    $accounts = [];
    $current_accounts = [];

    $accounts = SimpleGitAccountBusinessLogic::getAccounts(
      $this->current_user
    );
    $current_accounts = SimpleGitAccountBusinessLogic::deleteAccount(
      $accounts, $account_id
    );
    SimpleGitAccountBusinessLogic::setAccounts(
      $this->current_user, $current_accounts
    );

    return new ResourceResponse();
  }

  /**
   * Responds to the GET request.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing all the linked accounts.
   */

  public function get($account_id = NULL) {
    $accounts = [];

    if ($account_id == ModuleConstantInterface::REST_ALL_OPTION) {
      // should be reviewed once it is pushed!
      $accounts = SimpleGitAccountBusinessLogic::getAccounts(
        $this->current_user
      );
    }
    else {
      $accounts = SimpleGitAccountBusinessLogic::getAccountByAccountId(
        $this->current_user, $account_id
      );
    }

    return new ResourceResponse($accounts);
  }

}