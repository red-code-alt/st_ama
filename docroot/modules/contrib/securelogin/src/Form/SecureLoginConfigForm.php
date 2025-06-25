<?php

namespace Drupal\securelogin\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a SecureLogin Config form.
 */
class SecureLoginConfigForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a new SecureLoginConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   */
  final public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, TypedConfigManagerInterface $typed_config_manager) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'securelogin_config_form';
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   The editable config names.
   */
  protected function getEditableConfigNames() {
    return ['securelogin.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed[]
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    global $base_secure_url;
    $config = $this->config('securelogin.settings');
    $notice = $this->t('Must be checked to enforce secure authenticated sessions.');
    $states = ['invisible' => [':input[name="all_forms"]' => ['checked' => TRUE]]];

    $form['base_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Secure base URL'),
      '#default_value' => $config->get('base_url'),
      '#description'   => $this->t('The base URL for secure pages. Leave blank to allow Drupal to determine it automatically (recommended). It is not allowed to have a trailing slash; Drupal will add it for you. For example: %base_secure_url%.', ['%base_secure_url%' => $base_secure_url]),
    ];
    $form['secure_forms'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Redirect form pages to secure URL'),
      '#default_value' => $config->get('secure_forms'),
      '#description'   => $this->t('If enabled, any pages containing the forms enabled below will be redirected to the secure URL. Users can be assured that they are entering their private data on a secure URL, the contents of which have not been tampered with.'),
    ];
    $form['all_forms'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Submit all forms to secure URL'),
      '#default_value' => $config->get('all_forms'),
      '#description'   => $this->t('If enabled, all forms will be submitted to the secure URL.'),
    ];
    $form['forms'] = [
      '#type'          => 'fieldset',
      '#title'         => $this->t('Forms'),
      '#description'   => $this->t('If checked, these forms will be submitted to the secure URL. Note, some forms must be checked to enforce secure authenticated sessions.'),
      '#states'        => $states,
    ];
    $form['forms']['form_user_form'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('User edit form'),
      '#default_value' => $config->get('form_user_form'),
    ];
    $form['forms']['form_user_login_form'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('User login form'),
      '#default_value' => $config->get('form_user_login_form'),
      '#description'   => $notice,
    ];
    $form['forms']['form_user_pass'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('User password request form'),
      '#default_value' => $config->get('form_user_pass'),
      '#description'   => $notice,
    ];
    $form['forms']['form_user_pass_reset'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('User password reset form'),
      '#default_value' => $config->get('form_user_pass_reset'),
      '#description'   => $notice,
    ];
    $form['forms']['form_user_register_form'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('User registration form'),
      '#default_value' => $config->get('form_user_register_form'),
      '#description'   => $notice,
    ];
    $forms = [];
    $this->moduleHandler->alter('securelogin', $forms);
    foreach ($forms as $id => $item) {
      $form['forms']["form_$id"] = [
        '#type'          => 'checkbox',
        '#title'         => $item['#title'],
        '#default_value' => $config->get("form_$id"),
        '#description'   => $item['#description'] ?? '',
      ];
    }
    $form['other_forms'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Other forms to secure'),
      '#default_value' => $config->get('other_forms'),
      '#description'   => $this->t('List the form IDs of any other forms that you want secured, separated by a space. If the form has a base form ID, you must list the base form ID rather than the form ID.'),
      '#states'        => $states,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $this->config('securelogin.settings')
      ->set('base_url', $form_state->getValue('base_url'))
      ->set('secure_forms', $form_state->getValue('secure_forms'))
      ->set('all_forms', $form_state->getValue('all_forms'))
      ->set('other_forms', $form_state->getValue('other_forms'))
      ->set('form_user_login_form', $form_state->getValue('form_user_login_form'))
      ->set('form_user_pass_reset', $form_state->getValue('form_user_pass_reset'))
      ->set('form_user_form', $form_state->getValue('form_user_form'))
      ->set('form_user_register_form', $form_state->getValue('form_user_register_form'))
      ->set('form_user_pass', $form_state->getValue('form_user_pass'))
      ->save();

    $forms = [];
    $this->moduleHandler->alter('securelogin', $forms);
    foreach ($forms as $id => $item) {
      $this->config('securelogin.settings')->set("form_$id", $form_state->getValue("form_$id"))->save();
    }

    drupal_register_shutdown_function('drupal_flush_all_caches');

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $base_url = $form_state->getValue('base_url');
    if (!$base_url || !is_string($base_url)) {
      $form_state->setValue('base_url', NULL);
    }
    elseif (!UrlHelper::isValid($base_url, TRUE)) {
      $form_state->setErrorByName('base_url', $this->t('The secure base URL must be a valid URL.'));
    }
    elseif (!($scheme = parse_url($base_url, PHP_URL_SCHEME)) || strtolower($scheme) !== 'https') {
      $form_state->setErrorByName('base_url', $this->t('The secure base URL must start with <em>https://</em>.'));
    }

    parent::validateForm($form, $form_state);
  }

}
