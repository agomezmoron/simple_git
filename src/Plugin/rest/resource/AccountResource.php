<?php

namespace Drupal\simple_git\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\simple_git\Plugin\rest\resource\response\ResourceResponseNonCached;
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
 *     "canonical" = "/api/simple_git/account/{accountId}",
 *     "https://www.drupal.org/link-relations/create" =
 *   "/api/simple_git/account",
 *   }
 * )
 */
class AccountResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */

  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current account.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    $logger,
    AccountProxyInterface $current_user
  ) {
    parent::__construct(
      $configuration, $plugin_id, $plugin_definition, $serializer_formats,
      $logger
    );
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
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
   * It connects the user to with the Git Service given using the given
   * information, returning the account data.
   *
   * @param array $data
   *   Request data.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the Git account data.
   */
  public function post(array $data = []) {
    $accountsOld = [];
    $accounts = [];
    $accountsOld = SimpleGitAccountBusinessLogic::getAccounts(
      $this->currentUser);
    $user_data = SimpleGitAuthorizationBusinessLogic::authorize(
      $this->currentUser, $data
    );
    $accounts = SimpleGitAccountBusinessLogic::getAccounts($this->currentUser);

    if (empty($user_data)) {
      // An error occurred authenticating (Unauthorized).
      $reponse = new ResourceResponse(NULL, 401);
    }
    elseif ($user_data['status'] == 409) {
      //An error Conflict
      $reponse = new ResourceResponse(NULL, 409);
    }
    elseif ((sizeof($accountsOld) + 1) == sizeof($accounts)) {
      //The request has been fulfilled, resulting in the creation of a new account.
      $reponse = new ResourceResponse($user_data, 201);
    }
    else {
      $reponse = new ResourceResponse($user_data);
    }
    return $reponse;
  }

  /**
   * Responds to DELETE requests.
   *
   * It deletes the sent account.
   *
   * @param $accountId
   *   A id of account.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response with the result status.
   */
  public function delete($accountId) {
    $accounts = [];
    $current_accounts = [];

    $accounts = SimpleGitAccountBusinessLogic::getAccounts(
      $this->currentUser
    );
    $current_accounts = SimpleGitAccountBusinessLogic::deleteAccount(
      $accounts, $accountId
    );

    $current_accounts = SimpleGitAccountBusinessLogic::setAccounts(
      $this->currentUser, $current_accounts
    );
    if ((sizeof($accounts) - 1) == sizeof($current_accounts)) {
      //Deleted correctly
      $response = new ResourceResponse();
    }
    elseif (!empty($current_accounts)) {
      //Account not found
      $response = new ResourceResponse(NULL, 204);
    }
    else {
      // Internal Server Error
      $response = new ResourceResponse(NULL, 500);
    }

    return $response;
  }

  /**
   * Responds to the GET request.
   *
   * @param $accountId
   *   A id of account
   *
   * @return \Drupal\simple_git\Plugin\rest\resource\response
   * \ResourceResponseNonCached
   *   The response containing all the linked accounts.
   */
  public function get($accountId = NULL) {
    $accounts = [];

    if ($accountId == ModuleConstantInterface::REST_ALL_OPTION) {
      // Should be reviewed once it is pushed.
      $accounts = SimpleGitAccountBusinessLogic::getAccounts(
        $this->currentUser
      );

      $response = new ResourceResponseNonCached($accounts);
    }
    else {
      $accounts = SimpleGitAccountBusinessLogic::getAccountByAccountId(
        $this->currentUser, $accountId);
      $response = new ResourceResponseNonCached($accounts);
    }

    return $response;
  }

}
