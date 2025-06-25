<?php

namespace Drupal\acquia_migrate\Controller;

use Drupal\acquia_migrate\Form\UserOneConfigurationForm;
use Drupal\acquia_migrate\MigrationRepository;
use Drupal\acquia_migrate\SourceDatabase;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Presents a getting started page for new users.
 *
 * @internal
 */
final class GetStarted extends ControllerBase {

  /**
   * Key of s3fs configuration step.
   *
   * @const string
   */
  const S3FS_STEP_KEY = 'configure-files-s3fs';

  /**
   * The Acquia Migrate Accelerate migration repository.
   *
   * @var \Drupal\acquia_migrate\MigrationRepository
   */
  protected $migrationRepository;

  /**
   * GetStarted constructor.
   *
   * @param \Drupal\acquia_migrate\MigrationRepository $migration_repository
   *   The Acquia Migrate migration repository.
   */
  public function __construct(MigrationRepository $migration_repository) {
    $this->migrationRepository = $migration_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('acquia_migrate.migration_repository'));
  }

  /**
   * Acquia Migrate Accelerate's dynamic start page: dynamically redirects.
   *
   * @return \Drupal\Core\Routing\LocalRedirectResponse
   *   A redirect to the appropriate step, or to the list of steps if none could
   *   be determined.
   */
  public function startPage() {
    $build = $this->build();
    $steps = $build['content']['#context']['checklist']['#context']['steps'];

    // Redirect to the specific step if any.
    foreach ($steps as $step_build) {
      // Find the first non-completed step.
      if ($step_build['completed']) {
        continue;
      }
      // But if that step is not active, do not redirect to the first available
      // step.
      elseif (!$step_build['active']) {
        break;
      }

      // Extract the URL for this step.
      $url = $step_build['content']['label']['#url'];
      assert($url instanceof Url);

      // Redirect to this URL.
      $generated_url = $url->toString(TRUE);
      $generated_url->setCacheMaxAge(0);
      try {
        $redirect_response = new LocalRedirectResponse($generated_url->getGeneratedUrl());
        $redirect_response->addCacheableDependency($generated_url);
        return $redirect_response;
      }
      catch (\InvalidArgumentException $e) {
        // The redirect was to an external URL. When a step points to an
        // external URL, do not redirect, and instead let it fall back to the
        // overview.
        break;
      }
    }

    // Otherwise redirect to the overview listing all steps.
    return new LocalRedirectResponse(Url::fromRoute('acquia_migrate.get_started')->toString(TRUE)->getGeneratedUrl());
  }

  /**
   * Return a page render array.
   *
   * @return array
   *   A render array.
   */
  public function build() {
    $current_url = Url::fromRoute('<current>')->toString(TRUE)->getGeneratedUrl();
    $preselect_url = Url::fromRoute('acquia_migrate.migrations.preselect');
    $dashboard_url = Url::fromRoute('acquia_migrate.migrations.dashboard');
    $steps = [];
    $steps['user_one'] = [
      'completed' => UserOneConfigurationForm::hasBeenConfigured(),
      'active' => !UserOneConfigurationForm::hasBeenConfigured() && $this->currentUser()->isAnonymous(),
      'content' => [
        'label' => [
          '#type' => 'link',
          '#title' => $this->t('Essential configuration'),
          '#url' => Url::fromRoute('acquia_migrate.get_started.configure_user_one', [], [
            'query' => ['destination' => $current_url],
          ]),
          '#attributes' => [
            'class' => 'text-primary',
          ],
        ],
        'description' => [
          '#markup' => $this->t("You can enter the Drupal 7 source site's base URL and choose the credentials for your site's admin account."),
        ],
      ],
    ];
    $steps['authenticate'] = [
      'completed' => $this->currentUser()->isAuthenticated(),
      'active' => UserOneConfigurationForm::hasBeenConfigured() && !$this->currentUser()->isAuthenticated(),
      'content' => [
        'label' => [
          '#type' => 'link',
          '#title' => $this->t('Log in.'),
          '#url' => Url::fromRoute('user.login', [], [
            'query' => ['destination' => $current_url],
          ]),
          '#attributes' => [
            'class' => 'text-primary',
          ],
        ],
        'description' => [
          '#markup' => $this->t('Log in with the credentials you chose in the first step.'),
        ],
      ],
    ];
    $steps['configure'] = [
      'completed' => SourceDatabase::isConnected(),
      'active' => !SourceDatabase::isConnected(),
      'content' => [
        'label' => [
          '#type' => 'link',
          '#title' => $this->t('Configure your source database.'),
          '#url' => Url::fromUri('https://packagist.org/packages/acquia/acquia-migrate-accelerate#user-content-specifying-source-database-and-files'),
          '#attributes' => [
            'class' => 'text-primary',
            'target' => '_blank',
          ],
        ],
        'description' => [
          '#markup' => $this->t("Follow the link above for instructions on how to configure your source site's database in this site's <code>settings.php</code> file."),
        ],
      ],
    ];
    $source_db_connection = !SourceDatabase::isConnected()
      ? FALSE
      : SourceDatabase::getConnection();
    try {
      $s3fs_status = !$source_db_connection
        ? FALSE
        : $source_db_connection->select('system', 's')
          ->condition('s.type', 'module')
          ->condition('s.name', 's3fs')
          ->fields('s', ['status'])
          ->execute()
          ->fetchField();
    }
    catch (\Exception $e) {
      $s3fs_status = FALSE;
    }

    $public_uses_s3 = $s3fs_status && $this->getSourceDrupal7VariableValue($source_db_connection, 's3fs_use_s3_for_public');
    $private_uses_s3 = $s3fs_status && $this->getSourceDrupal7VariableValue($source_db_connection, 's3fs_use_s3_for_private');

    $source_public_files_path = Settings::get('migrate_source_base_path');
    $source_private_files_path = Settings::get('migrate_source_private_file_path');
    $public_files_configured = (!is_null($source_public_files_path) && file_exists($source_public_files_path)) || $public_uses_s3;
    $private_files_configured = $private_uses_s3 || is_null($source_private_files_path) || file_exists($source_private_files_path);
    $files_configured = $public_files_configured && $private_files_configured;
    $steps['configure-files'] = [
      'completed' => $files_configured,
      'active' => !$files_configured,
      'content' => [
        'label' => [
          '#type' => 'link',
          '#title' => $this->t('Configure your files directory.'),
          '#url' => Url::fromUri('https://packagist.org/packages/acquia/acquia-migrate-accelerate#user-content-specifying-source-database-and-files'),
          '#attributes' => [
            'class' => 'text-primary',
            'target' => '_blank',
          ],
        ],
        'description' => [
          '#markup' => $this->t("Follow the link above for instructions on how to configure your source site's public files directory in this site's <code>settings.php</code> file. (And optionally the private files directory.)"),
        ],
      ],
    ];

    $expected_file_public_path = !$source_db_connection ? FALSE : unserialize($source_db_connection
      ->select('variable')
      ->fields(NULL, ['value'])
      ->condition('name', 'file_public_path', 'LIKE')
      ->execute()
      ->fetchField(), ['allowed_classes' => FALSE]);
    if ($files_configured && $expected_file_public_path && $expected_file_public_path !== 'sites/default/files') {
      $expected_file_public_path_exists = $expected_file_public_path !== FALSE && $files_configured && file_exists($expected_file_public_path) && is_writable($expected_file_public_path);
      $steps['create-destination-files-directory'] = [
        'completed' => $expected_file_public_path_exists,
        'active' => !$expected_file_public_path_exists,
        'content' => [
          'label' => $this->t('Create matching files directory'),
          'description' => [
            '#markup' => $expected_file_public_path_exists
              ? $this->t("The source site uses a non-default directory for serving publicly accessible files. <code>@absolute-path</code> exists, and is writable.", ['@absolute-path' => getcwd() . '/' . $expected_file_public_path])
              : $this->t("The source site uses a non-default directory for serving publicly accessible files. Ensure the <code>@absolute-path</code> directory exists, and is writable.", ['@absolute-path' => getcwd() . '/' . $expected_file_public_path]),
          ],
        ],
      ];
    }

    if ($public_uses_s3 || $private_uses_s3) {
      $public_s3_settings_is_met = $public_uses_s3
        ? Settings::get('s3fs.use_s3_for_public')
        : TRUE;
      $private_s3_settings_is_met = $private_uses_s3
        ? Settings::get('s3fs.use_s3_for_private')
        : TRUE;
      $missing_lines_to_add = array_filter([
        $public_s3_settings_is_met ? NULL : "\$settings['s3fs.use_s3_for_public'] = TRUE;",
        $private_s3_settings_is_met ? NULL : "\$settings['s3fs.use_s3_for_private'] = TRUE;",
      ]);

      $steps[self::S3FS_STEP_KEY] = [
        'completed' => $public_s3_settings_is_met && $private_s3_settings_is_met,
        'active' => !$public_s3_settings_is_met || !$private_s3_settings_is_met,
        'content' => [
          'label' => $this->t('Configure S3 File System settings'),
          'description' => [
            '#markup' => $this->formatPlural(
              count($missing_lines_to_add),
              "The source site was configured to store public or private files on S3 File System. In Drupal 9, this configuration is stored in settings.php. You should add the following line to settings.php: \n@settings-to-add",
              "The source site was configured to store public or private files on S3 File System. In Drupal 9, this configuration is stored in settings.php. You should add the following lines to settings.php: \n@settings-to-add",
              [
                '@settings-to-add' => implode("\n", $missing_lines_to_add),
              ]
            ),
          ],
        ],
      ];
    }

    $steps['preselect'] = [
      'completed' => $this->migrationRepository->migrationsHaveBeenPreselected(),
      'active' => end($steps)['completed'] && !$this->migrationRepository->migrationsHaveBeenPreselected(),
      'content' => [
        'label' => [
          '#type' => 'link',
          '#title' => $this->t('Choose which data to import from your source site.'),
          '#url' => $preselect_url,
          '#attributes' => [
            'class' => 'text-primary',
          ],
        ],
        'description' => [
          '#markup' => '<em>Acquia Migrate Accelerate</em> will automatically import all of your sources site\'s content types and fields. On this page, you\'ll be able to choose which parts of your source site that you want to migrate into your new Drupal 9 site. Don\'t worry, you can still choose to bring over anything later that you skip now.',
        ],
      ],
    ];
    $steps['import_content'] = [
      'completed' => FALSE,
      'active' => $this->currentUser()->isAuthenticated() && end($steps)['completed'],
      'content' => [
        'label' => [
          '#type' => 'link',
          '#title' => $this->t('Import your content.'),
          '#url' => $dashboard_url,
          '#attributes' => [
            'class' => 'text-primary',
          ],
        ],
        'description' => [
          '#markup' => $this->t("Once here, you'll begin the process of importing your source site's content. If you decide that you no longer want to import a migration that you selected in the previous step, you can mark it skipped. Migrations that have nothing left to import are marked as completed."),
        ],
      ],
    ];
    $unlink_inactive_labels = function (array $step) : array {
      if (!$step['active'] && is_array($step['content']['label']) && $step['content']['label']['#type'] === 'link') {
        $step['content']['label'] = [
          '#markup' => $step['content']['label']['#title'],
        ];
      }
      return $step;
    };
    $checklist = [
      '#type' => 'inline_template',
      '#template' => '<ol>{% for step in steps %}<li><h4>{% if step.completed %}<del>{% endif %}{{step.content.label}}{% if step.completed %}</del>{% endif %}</h4><p>{{step.content.description}}</p>{% endfor %}</ol>',
      '#context' => [
        'steps' => array_map($unlink_inactive_labels, $steps),
      ],
    ];
    $build = [
      '#template' => 'page',
      '#title' => $this->t('Welcome to <em>Acquia Migrate Accelerate</em>'),
      'content' => [
        '#type' => 'inline_template',
        '#template' => '{{checklist}}',
        '#context' => [
          'checklist' => $checklist,
        ],
      ],
      '#attached' => [
        'library' => [
          'acquia_migrate/styles',
        ],
      ],
    ];
    return $build;
  }

  /**
   * Returns the value of a Drupal 7 variable from the given source database.
   *
   * @param \Drupal\Core\Database\Connection $source_connection
   *   The connection of the source Drupal 7 instance.
   * @param string $variable_name
   *   The name of the Drupal 7 variable.
   *
   * @return mixed|null
   *   The (unserialized) value of the variable. If the variable is missing from
   *   the source, a NULL will be returned.
   */
  protected function getSourceDrupal7VariableValue(Connection $source_connection, string $variable_name) {
    $variable_value = $source_connection->select('variable', 'v')
      ->fields('v', ['value'])
      ->condition('v.name', $variable_name)
      ->execute()
      ->fetchField();

    if ($variable_value === FALSE) {
      return NULL;
    }

    return unserialize($variable_value, ['allowed_classes' => FALSE]);
  }

}
