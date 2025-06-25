<?php

namespace Drupal\Tests\acquia_migrate\Unit;

use Drupal\acquia_migrate\MessageAnalyzer;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * Tests MessageAnalyzer with the solutions parsed from messages-solutions.yml.
 *
 * @covers \Drupal\acquia_migrate\MessageAnalyzer
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class MessageAnalyzerTest extends MigrateTestCase {

  /**
   * Checks how many tests we have per solutions.
   *
   * Every single solution must have at least one test case.
   */
  public function testMessageRegularExpressions() {
    $analyzer = new MessageAnalyzer($this->getDatabase([]));
    $reflector = new \ReflectionObject($analyzer);
    $solutions_prop = $reflector->getProperty('solutions');
    $solutions_prop->setAccessible(TRUE);

    $all_solutions = $solutions_prop->getValue($analyzer);
    $this->assertIsArray($all_solutions);
    // Example should be excluded.
    unset($all_solutions['migration_plugin_id']);
    $this->assertNotEmpty($all_solutions);

    $solutions_should_be_tested = [];
    foreach ($all_solutions as $plugin_id => $all_plugin_solutions) {
      foreach ($all_plugin_solutions as $plugin_solutions_index => $plugin_solutions) {
        if (!empty($plugin_solutions['generic_solution'])) {
          $solutions_should_be_tested[] = implode(':', [
            $plugin_id,
            $plugin_solutions_index,
            'generic_solution',
          ]);
        }

        if (!empty($plugin_solutions['specific_solution'])) {
          foreach (array_keys($plugin_solutions['specific_solution']) as $solution_index) {
            $solutions_should_be_tested[] = implode(':', [
              $plugin_id,
              $plugin_solutions_index,
              'specific_solution',
              $solution_index,
            ]);
          }
        }

        if (!empty($plugin_solutions['computed_specific_solution'])) {
          foreach (array_keys($plugin_solutions['computed_specific_solution']) as $solution_index) {
            $solutions_should_be_tested[] = implode(':', [
              $plugin_id,
              $plugin_solutions_index,
              'computed_specific_solution',
              $solution_index,
            ]);
          }
        }
      }
    }

    $actual_test_case_keys = array_keys($this->providerMessages());

    // A single solution might be tested multiple times. In this case, we
    // expect that the calculated test case key is suffixed with a numerical
    // index with a colon separator.
    $untested_solutions = array_reduce(
      $solutions_should_be_tested,
      function (array $carry, string $test_case_key) use ($actual_test_case_keys) {
        foreach ($actual_test_case_keys as $actual_test_case_key) {
          $quoted = preg_quote($test_case_key, '/');
          if (preg_match('/(^' . $quoted . ')(?:\:[^:\W]{1,})*$/', $actual_test_case_key)) {
            return $carry;
          }
        }

        $carry[] = $test_case_key;

        return $carry;
      },
      []
    );

    // Every suggestion/solution should have at least one test.
    $this->assertEquals([], $untested_solutions);
  }

  /**
   * Tests the expected solutions found for the given messages.
   *
   * @covers \Drupal\acquia_migrate\MessageAnalyzer::getSolution
   *
   * @dataProvider providerMessages
   */
  public function testMessageAnalyzerSolutionMatches(string $migration_plugin_id, string $message, string $expected_solution, array $source_test_data = []) {
    $analyzer = new MessageAnalyzer($this->getDatabase($source_test_data));
    $this->assertEquals($expected_solution, $analyzer->getSolution($migration_plugin_id, $message));
    // For compatibility with Drupal <9.3 and >=9.3 messages.
    // @see https://www.drupal.org/node/2976098
    // @see \Drupal\migrate\MigrateExecutable::import()
    $message_with_prefix = sprintf("%s:%s: %s", $migration_plugin_id, $this->randomMachineName(), $message);
    $this->assertEquals($expected_solution, $analyzer->getSolution($migration_plugin_id, $message_with_prefix));
  }

  /**
   * Data provider for ::testMessageAnalyzerSolutionMatches.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerMessages(): array {
    return [
      // Solutions for d7_comment messages.
      'd7_comment:0:computed_specific_solution:is_empty' => [
        'Migration plugin ID' => 'd7_comment',
        'Message' => '[comment: 1234]: name=You have to specify a valid author.',
        'Expected' => "This comment (1234) is missing a value for the 'name' column. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.",
        'Source DB' => [
          'comment' => [
            ['cid' => 1234, 'name' => '', 'uid' => 10],
          ],
        ],
      ],
      'd7_comment:0:computed_specific_solution:is_not_empty' => [
        'Migration plugin ID' => 'd7_comment',
        'Message' => '[comment: 5678]: name=You have to specify a valid author.',
        'Expected' => "This comment (5678) is pointing to a user that no longer exists. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.",
        'Source DB' => [
          'comment' => [
            ['cid' => 5678, 'name' => 'missing user', 'uid' => 20],
          ],
        ],
      ],
      'd7_comment:1:generic_solution' => [
        'Migration plugin ID' => 'd7_comment',
        'Message' => '[comment: 1234]: comment_body=This value should not be null.',
        'Expected' => 'This comment (1234) is corrupted.',
        'Source DB' => [
          'field_data_comment_body' => [
            ['entity_id' => 1234, 'field_data_comment_body__value' => ''],
          ],
        ],
      ],
      'd7_comment:1:computed_specific_solution:does_not_exist' => [
        'Migration plugin ID' => 'd7_comment',
        'Message' => '[comment: 2345]: comment_body=This value should not be null.',
        'Expected' => "This comment (2345) has a row in the 'comment' table but no corresponding row in the 'field_data_comment_body' table. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration. Fix it either by deleting the 'comment' table row with cid=2345 or by creating a 'field_data_comment_body' table row with entity_id=2345.",
        'Source DB' => [
          'field_data_comment_body' => [
            ['entity_id' => 1234, 'field_data_comment_body__value' => 'baz'],
          ],
        ],
      ],

      // Solutions for d7_comment_type messages.
      'd7_comment_type:0:generic_solution' => [
        'Migration plugin ID' => 'd7_comment_type',
        'Message' => 'Attempt to create a bundle with an ID longer than 32 characters: an_enormously_long_comment_bundle_name_which_triggers_an_exception_during_migration',
        'Expected' => 'The comment type migrations tries to save a comment bundle with an ID longer than 32 characters. Probably you are using a very outdated Drupal 9 instance. Please recreate your migration environment and start over.',
      ],

      // Solutions for d7_field.
      'd7_field:0:specific_solution:location' => [
        'Migration plugin ID' => 'd7_field',
        'Message' => "Can't migrate the 'field_name_foo' field storage for 'entity_type_id_bar' entities, because the field's type 'location' is not available on the destination site.",
        'Expected' => 'The Location module has no Drupal 9 port. Community consensus is that the Address module is the successor. You can help finish this migration at https://www.drupal.org/project/address/issues/2974631.',
      ],
      'd7_field:0:specific_solution:computed' => [
        'Migration plugin ID' => 'd7_field',
        'Message' => "Can't migrate the 'field_name_foo' field storage for 'entity_type_id_bar' entities, because the field's type 'computed' is not available on the destination site.",
        'Expected' => "Computed fields cannot be migrated automatically as Drupal 9 field is computed by PHP code that needs to be provided via hook implementations. See https://git.drupalcode.org/project/computed_field/-/blob/3.x/README.md.",
      ],
      'd7_field:1:generic_solution:0' => [
        'Migration plugin ID' => 'd7_field',
        'Message' => "Skipping field field_file_image_alt_text as it will be migrated to the image media entity's source image field.",
        'Expected' => 'No further actions needed The alt property is migrated into the image, so there is no need for a separate field.',
      ],
      'd7_field:1:generic_solution:1' => [
        'Migration plugin ID' => 'd7_field',
        'Message' => "Skipping field field_file_image_title_text as it will be migrated to the image media entity's source image field.",
        'Expected' => 'No further actions needed The title property is migrated into the image, so there is no need for a separate field.',
      ],

      // Solutions for d7_field_instance.
      'd7_field_instance:0:specific_solution:location' => [
        'Migration plugin ID' => 'd7_field_instance',
        'Message' => "Can't migrate the 'User location' (field_user_location) field instance for 'user' entities of type 'user', because the field's type 'location' is not available on the destination site.",
        'Expected' => 'The Location module has no Drupal 9 port. Community consensus is that the Address module is the successor. You can help finish this migration at https://www.drupal.org/project/address/issues/2974631.',
      ],
      'd7_field_instance:0:specific_solution:computed' => [
        'Migration plugin ID' => 'd7_field_instance',
        'Message' => "Can't migrate the 'computed' (field_foo_computed) field instance for 'foo' entities of type 'bar', because the field's type 'computed' is not available on the destination site.",
        'Expected' => "Computed fields cannot be migrated automatically as Drupal 9 field is computed by PHP code that needs to be provided via hook implementations. See https://git.drupalcode.org/project/computed_field/-/blob/3.x/README.md.",
      ],
      'd7_field_instance:1:generic_solution:0' => [
        'Migration plugin ID' => 'd7_field_instance',
        'Message' => "Skipping field field_file_image_alt_text as it will be migrated to the image media entity's source image field.",
        'Expected' => 'No further actions needed The alt property is migrated into the image, so there is no need for a separate field.',
      ],
      'd7_field_instance:1:generic_solution:1' => [
        'Migration plugin ID' => 'd7_field_instance',
        'Message' => "Skipping field field_file_image_title_text as it will be migrated to the image media entity's source image field.",
        'Expected' => 'No further actions needed The title property is migrated into the image, so there is no need for a separate field.',
      ],

      // Solutions for d7_field_formatter_settings messages.
      'd7_field_formatter_settings:0:specific_solution:hs_taxonomy_term_reference_hierarchical_text' => [
        'Migration plugin ID' => 'd7_field_formatter_settings',
        'Message' => 'The field formatter plugin ID hs_taxonomy_term_reference_hierarchical_text (used on field type entity_reference) could not be mapped to an existing formatter plugin; defaulting to theoretical_plugin_id and dropping all formatter settings. Either redo the migration with the module installed that provides an equivalent formatter plugin, or modify the entity view display after the migration and manually choose the right field formatter.',
        'Expected' => 'Hierarchical Select never got a stable Drupal 7 or 8 release. No equivalent formatter exists in Drupal 7 or 8. The default formatter should suffice. Otherwise, install the https://www.drupal.org/project/shs module and use its entity_reference_shs field formatter.',
      ],
      'd7_field_formatter_settings:1:generic_solution:0' => [
        'Migration plugin ID' => 'd7_field_formatter_settings',
        'Message' => "Skipping field field_file_image_title_text as it will be migrated to the image media entity's source image field.",
        'Expected' => 'No further actions needed The title property is migrated into the image, so there is no need for a separate field.',
      ],
      'd7_field_formatter_settings:1:generic_solution:1' => [
        'Migration plugin ID' => 'd7_field_formatter_settings',
        'Message' => "Skipping field field_file_image_alt_text as it will be migrated to the image media entity's source image field.",
        'Expected' => 'No further actions needed The alt property is migrated into the image, so there is no need for a separate field.',
      ],

      // Solutions for d7_field_instance_widget_settings messages.
      'd7_field_instance_widget_settings:0:generic_solution:0' => [
        'Migration plugin ID' => 'd7_field_instance_widget_settings',
        'Message' => "Skipping field field_file_image_alt_text as it will be migrated to the image media entity's source image field.",
        'Expected' => 'No further actions needed The alt property is migrated into the image, so there is no need for a separate field.',
      ],
      'd7_field_instance_widget_settings:0:generic_solution:1' => [
        'Migration plugin ID' => 'd7_field_instance_widget_settings',
        'Message' => "Skipping field field_file_image_title_text as it will be migrated to the image media entity's source image field.",
        'Expected' => 'No further actions needed The title property is migrated into the image, so there is no need for a separate field.',
      ],

      // Solutions for d7_file messages.
      'd7_file:0:generic_solution:0' => [
        'Migration plugin ID' => 'd7_file',
        'Message' => "File 'sites/default//files/sub-dir/foo-bar_0.txt' does not exist",
        'Expected' => 'There is a hardcoded files directory in the file path followed by a double slash. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
      ],
      'd7_file:0:generic_solution:1' => [
        'Migration plugin ID' => 'd7_file',
        'Message' => "File 'sites/default/files//sub-dir/foo-bar_0.txt' does not exist",
        'Expected' => 'There is a hardcoded files directory in the file path followed by a double slash. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
      ],
      'd7_file:0:generic_solution:2' => [
        'Migration plugin ID' => 'd7_file',
        'Message' => "File 'sites/default/files/sub-dir//foo-bar_0.txt' does not exist",
        'Expected' => 'There is a hardcoded files directory in the file path followed by a double slash. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
      ],
      'd7_file:0:generic_solution:3' => [
        'Migration plugin ID' => 'd7_file',
        'Message' => "File 'sites/example.com/files//foo-bar_0.txt' does not exist",
        'Expected' => 'There is a hardcoded files directory in the file path followed by a double slash. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
      ],
      'd7_file:1:generic_solution' => [
        'Migration plugin ID' => 'd7_file',
        'Message' => "File 'sites/default/files/foo-bar_1.txt' does not exist",
        'Expected' => 'There is a hardcoded files directory in the file path. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
      ],
      'd7_file:2:generic_solution' => [
        'Migration plugin ID' => 'd7_file',
        'Message' => "File 'subdir//another/foo-bar_2.txt' does not exist",
        'Expected' => 'There is a double slash in the file path. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
      ],
      'd7_file:3:computed_specific_solution:does_not_exist' => [
        'Migration plugin ID' => 'd7_file',
        'Message' => '[file: 123]: uid.0.target_id=The referenced entity (user: 456) does not exist.',
        'Expected' => 'This file (123) is owned by a user that no longer exists. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
        'DB' => [
          'users' => [
            ['uid' => 10, 'name' => 'user that exists'],
          ],
        ],
      ],

      // Solutions for d7_file_entity messages.
      'd7_file_entity:0:generic_solution' => [
        'Migration plugin ID' => 'd7_file_entity',
        'Message' => "[media: 2, revision: 3]: field_media_oembed_video=The provided URL does not represent a valid oEmbed resource.",
        'Expected' => 'oEmbed does not support private videos — perhaps that is the case?',
      ],

      // Solutions for d7_file_plain messages.
      'd7_file_plain:0:generic_solution' => [
        'Migration plugin ID' => 'd7_file_plain',
        'Message' => '[media: 4, revision: 5]: thumbnail.0=Only files with the following extensions are allowed: <em class="placeholder">png jpg jpeg gif</em>',
        'Expected' => 'An SVG file was migrated into the Drupal 9 media library. But Drupal has only minimal support for SVG right now — see https://www.drupal.org/project/drupal/issues/3060504 for more information.',
      ],
      // This is an obsolete solution (and it was wrong even when it was added)!
      'd7_file_plain:1:generic_solution' => [
        'Migration plugin ID' => 'd7_file_plain',
        'Message' => '[media: 890]: field_file.0=The file is <em class="placeholder">2.93 KB</em> exceeding the maximum file size of <em class="placeholder">2 KB</em>.',
        'Expected' => 'The max_filesize setting is not yet being automatically inferred for the generated media source field. For now, tweak the configuration manually. Or, you can help improve this aspect of this migration at https://www.drupal.org/project/media_migration/issues/3168920.',
      ],
      'd7_file_plain:2:generic_solution' => [
        'Migration plugin ID' => 'd7_file_plain',
        'Message' => 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'a2a4f7c5-92af-44f9-b165-b98594787174\' for key \'media_field__uuid__value\': INSERT INTO {media} ("vid", "bundle", "uuid", "langcode") VALUES (:db_insert_placeholder_0, :db_insert_placeholder_1, :db_insert_placeholder_2, :db_insert_placeholder_3); Array
(
    [:db_insert_placeholder_0] =>
    [:db_insert_placeholder_1] => image
    [:db_insert_placeholder_2] => a2a4f7c5-92af-44f9-b165-b98594787174
    [:db_insert_placeholder_3] => en
)',
        'Expected' => 'This is likely caused by rows in the "file_managed" table having an empty value for the "filemime" column. This is a data integrity bug in the Drupal 7 database. Please fix it there, clear caches and rollback & import this migration. Help implementing an automated fix at https://www.drupal.org/project/media_migration/issues/3390454',
      ],

      // Solution for d7_user.
      'd7_user:0:generic_solution' => [
        'Migration plugin ID' => 'd7_user',
        'Message' => '[user: 2]: init.0.value=This value is not a valid email address.',
        'Expected' => 'The required initial email address is missing for this user. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
      ],

      // Solutions for d7_filter_format messages.
      'd7_filter_format:0:specific_solution:php_code' => [
        'Migration plugin ID' => 'd7_filter_format',
        'Message' => 'Filter php_code could not be mapped to an existing filter plugin; defaulting to filter_null and dropping all settings. Either redo the migration with the module installed that provides an equivalent filter, or modify the text format after the migration to remove this filter if it is no longer necessary.',
        'Expected' => "Stop using the php_code filter! It was a bad practice in Drupal 7 too. Either modify your Drupal 7 site to stop using it, otherwise it's recommended to not migrate any content that is actively using this filter.",
      ],
      'd7_filter_format:0:specific_solution:insert_block' => [
        'Migration plugin ID' => 'd7_filter_format',
        'Message' => 'Filter insert_block could not be mapped to an existing filter plugin; defaulting to filter_null and dropping all settings. Either redo the migration with the module installed that provides an equivalent filter, or modify the text format after the migration to remove this filter if it is no longer necessary.',
        'Expected' => 'The "Insert Block" module has no Drupal 9 port. You can use the Drupal 9 compatibility patch and help get it committed at https://www.drupal.org/project/insert_block/issues/3151591. Warning: this module is not maintained!',
      ],
      'd7_filter_format:0:specific_solution:ds_code' => [
        'Migration plugin ID' => 'd7_filter_format',
        'Message' => 'Filter ds_code could not be mapped to an existing filter plugin; defaulting to filter_null and dropping all settings. Either redo the migration with the module installed that provides an equivalent filter, or modify the text format after the migration to remove this filter if it is no longer necessary.',
        'Expected' => "Stop using the ds_code filter! It is equivalent to the php_code filter, which already was a bad practice in Drupal. Modify your Drupal 7 site to stop using it. Otherwise, it's recommended to not migrate any content that is actively using this filter.",
      ],
      'd7_filter_format:0:specific_solution:easychart' => [
        'Migration plugin ID' => 'd7_filter_format',
        'Message' => 'Filter easychart could not be mapped to an existing filter plugin; defaulting to filter_null and dropping all settings. Either redo the migration with the module installed that provides an equivalent filter, or modify the text format after the migration to remove this filter if it is no longer necessary.',
        'Expected' => 'The "Easychart" module has no Drupal 9 port. You can use the Drupal 9 compatibility patch and help get it committed at https://www.drupal.org/project/easychart/issues/3214283. This filter was even removed from the 7.x-3.x version, the last version it shipped with was 7.x-2.x, which got its last release in 2015! Warning: this module is not maintained!',
      ],
      'd7_filter_format:0:specific_solution:tabs' => [
        'Migration plugin ID' => 'd7_filter_format',
        'Message' => 'Filter tabs could not be mapped to an existing filter plugin; defaulting to filter_null and dropping all settings. Either redo the migration with the module installed that provides an equivalent filter, or modify the text format after the migration to remove this filter if it is no longer necessary.',
        'Expected' => 'The "jQuery UI filter" module has no Drupal 9 port. This filter is available in the Drupal 8 version of this module. You can use the Drupal 9 compatibility patch and help get it committed at https://www.drupal.org/project/jquery_ui_filter/issues/3158053. Warning: this module is not maintained!',
      ],
      // @todo Should this return the same message as tabs?
      'd7_filter_format:0:specific_solution:accordion' => [
        'Migration plugin ID' => 'd7_filter_format',
        'Message' => 'Filter accordion could not be mapped to an existing filter plugin; defaulting to filter_null and dropping all settings. Either redo the migration with the module installed that provides an equivalent filter, or modify the text format after the migration to remove this filter if it is no longer necessary.',
        'Expected' => 'The "jQuery UI filter" module has no Drupal 9 port. This filter is available in the Drupal 8 version of this module. You can use the Drupal 9 compatibility patch and help get it committed at https://www.drupal.org/project/jquery_ui_filter/issues/3158053. Warning: this module is not maintained!',
      ],
      'd7_filter_format:1:specific_solution:image_resize_filter' => [
        'Migration plugin ID' => 'd7_filter_format',
        'Message' => 'Filter image_resize_filter could not be mapped to an existing filter plugin; omitted since it is a transformation-only filter. Install and configure a successor after the migration.',
        'Expected' => "Use Drupal 9 core's media_embed filter and configure a view mode corresponding to the various typical image dimensions that were used on the Drupal 7 site.",
      ],
      'd7_filter_format:1:specific_solution:filter_tokens' => [
        'Migration plugin ID' => 'd7_filter_format',
        'Message' => 'Filter filter_tokens could not be mapped to an existing filter plugin; omitted since it is a transformation-only filter. Install and configure a successor after the migration.',
        'Expected' => "Install the Drupal 9 port of the token_filter module. Preferably, reassess whether it still makes sense to use: if it's used to embed media, you should instead use Drupal 9 core's media_embed filter.",
      ],
      'd7_filter_format:1:specific_solution:typogrify' => [
        'Migration plugin ID' => 'd7_filter_format',
        'Message' => 'Filter typogrify could not be mapped to an existing filter plugin; omitted since it is a transformation-only filter. Install and configure a successor after the migration.',
        'Expected' => 'Install the typogrify module. It has a Drupal 9-compatible release. Then re-run this migration.',
      ],

      // Solutions for d7_image_styles messages.
      'd7_image_styles:0:generic_solution' => [
        'Migration plugin ID' => 'd7_image_styles',
        'Message' => 'The "manualcrop_crop_and_scale" plugin does not exist.',
        'Expected' => 'The https://www.drupal.org/project/manualcrop module for Drupal 7 is obsolete, its successor is https://www.drupal.org/project/image_widget_crop, but https://www.drupal.org/project/focal_point is a viable alternative too.',
      ],

      // Solutions for d7_menu_links messages.
      'd7_menu_links:0:generic_solution' => [
        'Migration plugin ID' => 'd7_menu_links',
        'Message' => ' The path "internal:/<void>" failed validation.',
        'Expected' => "This menu link points to a path that no longer exists or possibly never existed. Perhaps it points to a View that hasn't been manually recreated?",
      ],
      'd7_menu_links:1:generic_solution' => [
        'Migration plugin ID' => 'd7_menu_links',
        'Message' => "No parent link found for plid '1043' in menu 'foobarbaz'.",
        'Expected' => 'This menu link does not have a parent link. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
      ],

      // Solutions for d7_path_redirect messages.
      'd7_path_redirect:0:computed_specific_solution:does_not_exist' => [
        'Migration plugin ID' => 'd7_path_redirect',
        'Message' => '[redirect: 123]: uid.0.target_id=The referenced entity (user: 45) does not exist.',
        'Expected' => 'This redirect (123) is owned by a user that no longer exists. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
        'DB' => [
          'users' => [
            ['uid' => 1, 'name' => 'an existing user'],
          ],
        ],
      ],

      // Solutions for d7_url_alias messages.
      'd7_url_alias:0:computed_specific_solution:does_not_exist' => [
        'Migration plugin ID' => 'd7_url_alias',
        'Message' => "[path_alias: 123, revision: 456]: path.0.value=Either the path '/user/789' is invalid or you do not have access to it.",
        'Expected' => 'This path alias (123) points to a user that no longer exists. This is wrong in the Drupal 7 source database too, please fix it there (potentially be deleting this path alias), refresh the source database and rollback & import this migration.',
        'DB' => [
          'users' => [
            ['uid' => 1, 'name' => 'an existing user'],
          ],
        ],
      ],
      'd7_url_alias:1:generic_solution' => [
        'Migration plugin ID' => 'd7_url_alias',
        'Message' => "[path_alias: 5, revision: 5]: path.0.value=Either the path 'internal:/bar/baz' is invalid or you do not have access to it.",
        'Expected' => "This path alias points to a path that no longer exists or possibly never existed. Perhaps it points to a View that hasn't been manually recreated?",
      ],

      // Solutions for fallbacks.
      'FALLBACK:0:generic_solution' => [
        'Migration plugin ID' => 'd7_foo',
        'Message' => '[foo: 123, bar: 456]: field_baz=This value should not be null.',
        'Expected' => 'A new field was added to this entity type after entities had already been created. This new field was marked as required, but the entities that already existed were not updated with values for this new required field. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration!',
      ],
      'FALLBACK:1:specific_solution:biblio' => [
        'Migration plugin ID' => 'foobar:baz',
        'Message' => 'The "biblio" entity type does not exist.',
        'Expected' => 'The "Bibliography" (biblio) module has no Drupal 9 port. The Drupal 9 successor is "Bibliography & Citation" (bibcite). It is run by an enthusiastic team, follow them at https://twitter.com/BibCite. You can help finish this migration at https://www.drupal.org/project/bibcite_migrate.',
      ],
      'FALLBACK:1:specific_solution:field_collection_item' => [
        'Migration plugin ID' => 'foobar:baz',
        'Message' => 'The "field_collection_item" entity type does not exist.',
        'Expected' => 'We are working on providing a reliable migration path to Paragraphs. Stay tuned.',
      ],
      'FALLBACK:1:specific_solution:menu_fields' => [
        'Migration plugin ID' => 'foobar:baz',
        'Message' => 'The "menu_fields" entity type does not exist.',
        'Expected' => 'The "Menu Item Fields" (menu_fields) module has no Drupal 9 port. The Drupal 9 successor is "Menu Item Extras" (menu_item_extras).',
      ],
      'FALLBACK:2:generic_solution' => [
        'Migration plugin ID' => 'foobar:baz',
        'Message' => '[nid: 123, revision: 456]: field_foo.0.target_id=The referenced entity (block_content: 789) does not exist.',
        'Expected' => 'Either the referenced entity does not exist anymore on the source site or the migration of the referenced entity failed. If the referenced entity does not exist anymore in the Drupal 7 source database either, please fix it there, refresh the source database and rollback & import this migration.',
      ],
      'FALLBACK:2:specific_solution:file' => [
        'Migration plugin ID' => 'foobar:baz',
        'Message' => '[nid: 123, revision: 456]: field_bar.0.target_id=The referenced entity (file: 789) does not exist.',
        'Expected' => 'Either the referenced file does not exist anymore on the source site or the file failed to migrate successfully. If there is no message for the file with this fid in the "Public files" or "Private files" migration, the Drupal 7 source database is wrong, please fix it there, refresh the source database and rollback & import this migration.',
      ],
      'FALLBACK:2:specific_solution:user' => [
        'Migration plugin ID' => 'foobar:baz',
        'Message' => '[nid: 123, revision: 456]: field_baz.0.target_id=The referenced entity (user: 789) does not exist.',
        'Expected' => 'This entity is referencing a user that probably no longer exists. This is likely wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
      ],
      'FALLBACK:3:generic_solution' => [
        'Migration plugin ID' => 'd7_node_complete:article',
        'Message' => "Skipping the migration of this published revision: it is the copy of the last published revision. It was saved by Workbench Moderation as a workaround for the lack of a feature in Drupal 7, because it wasn't able to handle forward (non-default) revisions. In Drupal 9 this is not needed anymore.",
        'Expected' => 'Clones of published entity revisions saved by Workbench Moderation are unnecessary in Drupal 9.',
      ],
      'FALLBACK:4:generic_solution' => [
        'Migration plugin ID' => 'd7_node_complete:page',
        'Message' => "Skipping the migration of this draft revision: it lacks its previous revision. It happens because with Drupal 7 Workbench Moderation it was possible to delete older revisions, but in Drupal 9 core it is impossible to restore the original data integrity. Hopefully it isn't a problem that a draft cannot be restored.",
        'Expected' => "Draft revisions whose parent revision was deleted and which were saved before a published revision aren't migrated.",
      ],
    ];
  }

  /**
   * Tests that all solutions are evaluated for the given messages.
   *
   * @covers \Drupal\acquia_migrate\MessageAnalyzer::getSolution
   *
   * @dataProvider providerMultiple
   */
  public function testMessageAnalyzerWithMultipleSolutions(string $migration_plugin_id, string $message, string $expected_solution, array $source_test_data = []) {
    $this->testMessageAnalyzerSolutionMatches($migration_plugin_id, $message, $expected_solution, $source_test_data);
  }

  /**
   * Data provider for ::testMessageAnalyzerWithMultipleSolutions.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerMultiple(): array {
    return [
      'Multiple messages' => [
        'Migration plugin ID' => 'd7_node_complete:page',
        'Message' => "[nid: 123, revision: 456]: field_foo.0.target_id=The referenced entity (file: 789) does not exist.||field_bar=This value should not be null.",
        'Expected' => "▶ Either the referenced file does not exist anymore on the source site or the file failed to migrate successfully. If there is no message for the file with this fid in the \"Public files\" or \"Private files\" migration, the Drupal 7 source database is wrong, please fix it there, refresh the source database and rollback & import this migration. ▶ A new field was added to this entity type after entities had already been created. This new field was marked as required, but the entities that already existed were not updated with values for this new required field. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration!",
      ],
    ];
  }

}
