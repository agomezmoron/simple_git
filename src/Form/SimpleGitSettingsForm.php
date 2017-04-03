<?php

/**
 * @file
 * Contains \Drupal\simple_git\Form\SimpleGitSettingsForm.
 * @author  Alejandro Gómez Morón <agomezmoron@emergya.com>
 * @author  Estefania Barrrera Berengeno <ebarrera@emergya.com>
 * @version PHP: 7
 */

namespace Drupal\simple_git\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_git\Interfaces\ModuleConstantInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deletion confirmation form for the block instance deletion form.
 *
 * @package Drupal/simple_git/Service
 */
class SimpleGitSettingsForm extends ConfigFormBase {

  /**
   * Constructs a SimpleGitSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config service.
   */
  public function __construct(ConfigFactory $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Config\ConfigFactory $config */
    $config = $container->get('config.factory');
    return new static($config);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_git_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = array();
    // add the GitHub configuration
    $this->buildGitHubForm($form);

    // add the GitHub configuration
    $this->buildGitLabForm($form);

    $form['#submit'][] = array($this, 'submitForm');

    return parent::buildForm($form, $form_state);
  }

  /**
   * It builds the GitHub configuration subform.
   *
   * @param $form
   *  An associative array containing the structure of the form.
   */
  private function buildGitHubForm(&$form) {
    $git_settings = $this->configFactory->get('simple_git.settings');

    $form['git_hub'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('GitHub settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['git_hub']['git_hub_app_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GitHub App Id'),
      '#description' => $this->t('GitHub App Id value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUB
      )['app_id'],
    );

    $form['git_hub']['git_hub_app_secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GitHub App Secret'),
      '#description' => $this->t('GitHub App Secret value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUB
      )['app_secret'],
    );

    $form['git_hub']['git_hub_app_url_redirect'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GitHub URL Redirect'),
      '#description' => $this->t('GitHub URL Redirect value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUB
      )['app_url_redirect'],
    );

    $form['git_hub']['git_hub_app_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GitHub App Name'),
      '#description' => $this->t('GitHub App Name'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUB
      )['app_name'],
    );

  }

  /**
   * It builds the GitLab configuration subform.
   *
   * @param $form
   *  An associative array containing the structure of the form.
   */
  private function buildGitLabForm(&$form) {
    $git_settings = $this->configFactory->get('simple_git.settings');

    $form['git_lab'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('GitLab settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['git_lab']['git_lab_app_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GitLab App Id'),
      '#description' => $this->t('GitLab App Id value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITLAB
      )['app_id']
    );

    $form['git_lab']['git_lab_app_secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GitLab App Secret'),
      '#description' => $this->t('GitLab App Secret value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITLAB
      )['app_secret'],
    );

    $form['git_lab']['git_lab_app_url_redirect'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GitLab URL Redirect'),
      '#description' => $this->t('GitLab URL Redirect value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITLAB
      )['app_url_redirect'],
    );

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = array();
    $values['git_hub'] = array(
      'app_id' => $form_state->getValue('git_hub_app_id'),
      'app_secret' => $form_state->getValue('git_hub_app_secret'),
      'app_url_redirect' => $form_state->getValue('git_hub_app_url_redirect'),
      'app_name' => $form_state->getValue('git_hub_app_name')
    );

    $values['git_lab'] = array(
      'app_id' => $form_state->getValue('git_lab_app_id'),
      'app_secret' => $form_state->getValue('git_lab_app_secret'),
      'app_url_redirect' => $form_state->getValue('git_lab_app_url_redirect'),
    );

    \Drupal::configFactory()->getEditable('simple_git.settings')
      ->set(ModuleConstantInterface::GIT_TYPE_GITHUB, $values['git_hub'])
      ->set(ModuleConstantInterface::GIT_TYPE_GITLAB, $values['git_lab'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_git.settings',
    ];
  }

}

