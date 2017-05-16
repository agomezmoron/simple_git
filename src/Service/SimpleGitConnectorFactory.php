<?php

namespace Drupal\simple_git\Service;

use Drupal\simple_git\Interfaces\ModuleConstantInterface;
use Drupal\simple_git\Service\SimpleGitConnectorInterface;

/**
 * Connector Simple Factory.
 *
 * @package Drupal\simple_git\Service
 */
abstract class SimpleGitConnectorFactory {

  /**
   * It retrieves a \Drupal\simple_git\Service\SimpleGitConnectorService.
   *
   * @param string $type
   *   Depending on the type, the factory will return a different instance of
   *   \Drupal\simple_git\Service\SimpleGitConnectorService.
   *
   * @return \Drupal\simple_git\Service\SimpleGitConnector
   *   Instance that matches with the given $type.
   */
  static function getConnector($type) {
    $connector = NULL;
    switch ($type) {
      case ModuleConstantInterface::GIT_TYPE_GITHUB:
      case ModuleConstantInterface::GIT_TYPE_GITHUBM:
        $connector = \Drupal::service(
          'simple_git.github_connector.service'
        );
        break;

      case ModuleConstantInterface::GIT_TYPE_GITLAB:
      case ModuleConstantInterface::GIT_TYPE_GITLABM:
        $connector = \Drupal::service(
          'simple_git.gitlab_connector.service'
        );
      default:
        $connector = \Drupal::service(
          'simple_git.github_connector.service'
        );
    }
    return $connector;
  }

}
