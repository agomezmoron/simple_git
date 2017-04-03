<?php

/**
 * Interface for a router class for Drupal with access check.
 *
 * @author  Alejandro Gómez Morón <amoron@emergya.com>
 * @author  Estefania Barrrera Berengeno <ebarrera@emergya.com>
 * @version PHP 7
 */

namespace Drupal\simple_git\Interfaces;

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
   * Defining the "all" option in the rest definition.
   */
  const REST_ALL_OPTION = 'all';

}

?>