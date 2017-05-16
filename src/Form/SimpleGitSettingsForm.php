<?php

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
    /*@var \Drupal\Core\Config\ConfigFactory $config*/
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

    $form = [];
    // Add the GitHub Web configuration.
    $this->buildGitHubWForm($form);

    // Add the GitLab Web configuration.
    $this->buildGitLabWForm($form);

    // Add the GitHub Movile configuration.
    $this->buildGitHubMForm($form);

    // Add the GitLab Movile configuration.
    $this->buildGitLabMForm($form);

    $form['#submit'][] = [$this, 'submitForm'];

    return parent::buildForm($form, $form_state);
  }

  /**
   * It builds the GitHub Web configuration subform.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  private function buildGitHubWForm(&$form) {
    $git_settings = $this->configFactory->get('simple_git.settings');

    $form['git_hub'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('GitHub Web settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['git_hub']['git_hub_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitHub App Web Id'),
      '#description' => $this->t('GitHub App Web Id value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUB
      )['app_id'],
    ];

    $form['git_hub']['git_hub_app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitHub App Web Secret'),
      '#description' => $this->t('GitHub App Web Secret value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUB
      )['app_secret'],
    ];

    $form['git_hub']['git_hub_app_url_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitHub URL Web Redirect'),
      '#description' => $this->t('GitHub URL Web Redirect value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUB
      )['app_url_redirect'],
    ];

    $form['git_hub']['git_hub_app_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitHub App Web Name'),
      '#description' => $this->t('GitHub App Web Name'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUB
      )['app_name'],
    ];

  }

  /**
   * It builds the GitLab Web configuration subform.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  private function buildGitLabWForm(&$form) {
    $git_settings = $this->configFactory->get('simple_git.settings');

    $form['git_lab'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('GitLab Web settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['git_lab']['git_lab_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitLab App Web Id'),
      '#description' => $this->t('GitLab App Web Id value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITLAB
      )['app_id'],
    ];

    $form['git_lab']['git_lab_app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitLab App Web Secret'),
      '#description' => $this->t('GitLab App Web Secret value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITLAB
      )['app_secret'],
    ];

    $form['git_lab']['git_lab_app_url_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitLab URL Web Redirect'),
      '#description' => $this->t('GitLab URL Web Redirect value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITLAB
      )['app_url_redirect'],
    ];

  }

  /**
   * It builds the GitHub Mobile configuration subform.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  private function buildGitHubMForm(&$form) {
    $git_settings = $this->configFactory->get('simple_git.settings');

    $form['git_hubM'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('GitHub Mobile settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['git_hubM']['git_hubM_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitHub App Mobile Id'),
      '#description' => $this->t('GitHub App Mobile Id value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUBM
      )['app_id'],
    ];

    $form['git_hubM']['git_hubM_app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitHub App Mobile Secret'),
      '#description' => $this->t('GitHub App Mobile Secret value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUBM
      )['app_secret'],
    ];

    $form['git_hubM']['git_hubM_app_url_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitHub URL Mobile Redirect'),
      '#description' => $this->t('GitHub URL Mobile Redirect value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUBM
      )['app_url_redirect'],
    ];
    $form['git_hubM']['git_hubM_app_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitHub App Mobile Name'),
      '#description' => $this->t('GitHub App Mobile Name'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITHUBM
      )['app_name'],
    ];

  }

  /**
   * It builds the GitLab Mobile configuration subform.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  private function buildGitLabMForm(&$form) {
    $git_settings = $this->configFactory->get('simple_git.settings');

    $form['git_labM'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('GitLab Mobile settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['git_labM']['git_labM_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitLab App Mobile Id'),
      '#description' => $this->t('GitLab App Mobile Id value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITLABM
      )['app_id'],
    ];

    $form['git_labM']['git_labM_app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitLab App Mobile Secret'),
      '#description' => $this->t('GitLab App <mobile Secret value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITLABM
      )['app_secret'],
    ];

    $form['git_labM']['git_labM_app_url_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GitLab URL Mobile Redirect'),
      '#description' => $this->t('GitLab URL Mobile Redirect value'),
      '#default_value' => $git_settings->get(
        ModuleConstantInterface::GIT_TYPE_GITLABM
      )['app_url_redirect'],
    ];

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

    $values = [];
    $values['git_hub'] = [
      'app_id' => $form_state->getValue('git_hub_app_id'),
      'app_secret' => $form_state->getValue('git_hub_app_secret'),
      'app_url_redirect' => $form_state->getValue('git_hub_app_url_redirect'),
      'app_name' => $form_state->getValue('git_hub_app_name'),
    ];

    $values['git_lab'] = [
      'app_id' => $form_state->getValue('git_lab_app_id'),
      'app_secret' => $form_state->getValue('git_lab_app_secret'),
      'app_url_redirect' => $form_state->getValue('git_lab_app_url_redirect'),
    ];

    $values['git_hubM'] = [
      'app_id' => $form_state->getValue('git_hubM_app_id'),
      'app_secret' => $form_state->getValue('git_hubM_app_secret'),
      'app_url_redirect' => $form_state->getValue('git_hubM_app_url_redirect'),
      'app_name' => $form_state->getValue('git_hubM_app_name'),
    ];

    $values['git_labM'] = [
      'app_id' => $form_state->getValue('git_lab_appM_id'),
      'app_secret' => $form_state->getValue('git_lab_appM_secret'),
      'app_url_redirect' => $form_state->getValue('git_labM_app_url_redirect'),
    ];

    \Drupal::configFactory()->getEditable('simple_git.settings')
      ->set(ModuleConstantInterface::GIT_TYPE_GITHUB, $values['git_hub'])
      ->set(ModuleConstantInterface::GIT_TYPE_GITLAB, $values['git_lab'])
      ->set(ModuleConstantInterface::GIT_TYPE_GITHUBM, $values['git_hubM'])
      ->set(ModuleConstantInterface::GIT_TYPE_GITLABM, $values['git_labM'])
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
