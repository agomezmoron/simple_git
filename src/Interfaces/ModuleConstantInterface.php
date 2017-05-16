<?php

namespace Drupal\simple_git\Interfaces;

/**
 * Interface ModuleConstantInterface.
 *
 * @package Drupal\simple_git\Interfaces
 */
interface ModuleConstantInterface {

  /**
   * Defining the module name.
   *
   * @package Drupal/simple_git/Interfaces
   */
  const MODULE_SIMPLEGIT = 'simple_git';

  /**
   * Defining the GitHub type.
   */
  const GIT_TYPE_GITHUB = 'GITHUB';

  /**
   * Defining the GitLab type.
   */
  const GIT_TYPE_GITLAB = 'GITLAB';
  /**
   * Defining the GitHub mobile type.
   */
  const GIT_TYPE_GITHUBM = 'GITHUBM';

  /**
   * Defining the GitLab mobile type.
   */
  const GIT_TYPE_GITLABM = 'GITLABM';
  /**
   * Defining the "all" option in the rest definition.
   */
  const REST_ALL_OPTION = 'all';

}
