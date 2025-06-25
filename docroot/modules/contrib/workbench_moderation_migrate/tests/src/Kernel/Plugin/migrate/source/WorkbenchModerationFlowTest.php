<?php

namespace Drupal\Tests\workbench_moderation_migrate\Kernel\Plugin\migrate\source;

use Drupal\Tests\migmag\Kernel\MigMagNativeMigrateSqlTestBase;

/**
 * Tests the 'workbench_moderation_flow' migrate source plugin.
 *
 * @covers \Drupal\workbench_moderation_migrate\Plugin\migrate\source\WorkbenchModerationFlow
 * @group workbench_moderation_migrate
 */
class WorkbenchModerationFlowTest extends MigMagNativeMigrateSqlTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate_drupal',
    'workbench_moderation_migrate',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $expected_transitions = [
      [
        'id' => '1',
        'name' => 'Single edit and ready for review',
        'from_name' => 'published',
        'to_name' => 'needs_review',
      ],
      [
        'id' => '6',
        'name' => 'Submit for Review',
        'from_name' => 'draft',
        'to_name' => 'needs_review',
      ],
      [
        'id' => '11',
        'name' => 'Edited by Admin or Publisher',
        'from_name' => 'draft',
        'to_name' => 'published',
      ],
      [
        'id' => '16',
        'name' => 'Work in Progress',
        'from_name' => 'published',
        'to_name' => 'draft',
      ],
      [
        'id' => '21',
        'name' => 'Reviewed',
        'from_name' => 'draft',
        'to_name' => 'reviewed',
      ],
      [
        'id' => '26',
        'name' => 'Reviewed and ready for publication',
        'from_name' => 'reviewed',
        'to_name' => 'published',
      ],
      [
        'id' => '31',
        'name' => 'Needs more work',
        'from_name' => 'needs_review',
        'to_name' => 'draft',
      ],
      [
        'id' => '36',
        'name' => 'Unpublish',
        'from_name' => 'published',
        'to_name' => 'unpublished',
      ],
    ];
    $expected_states = [
      [
        'name' => 'draft',
        'label' => 'Draft',
        'description' => 'Work in progress',
        'weight' => '-10',
      ],
      [
        'name' => 'needs_review',
        'label' => 'Needs Review',
        'description' => 'Ready for moderation',
        'weight' => '-9',
      ],
      [
        'name' => 'published',
        'label' => 'Published',
        'description' => 'Make this version live',
        'weight' => '-7',
      ],
      [
        'name' => 'reviewed',
        'label' => 'Reviewed',
        'description' => 'Work has been reviewed',
        'weight' => '-8',
      ],
      [
        'name' => 'unpublished',
        'label' => 'Unpublished',
        'description' => 'Unpublish content',
        'weight' => '0',
      ],
    ];

    return [
      'No moderation states' => [
        'source' => [
          'system' => static::SYSTEM,
          'variable' => [
            [
              'name' => 'node_options_article',
              'value' => 'a:1:{i:0;s:8:"revision";}',
            ],
            [
              'name' => 'node_options_basic_page',
              'value' => 'a:1:{i:0;s:6:"status";}',
            ],
            [
              'name' => 'workbench_moderation_default_state_article',
              'value' => 's:5:"draft";',
            ],
            [
              'name' => 'workbench_moderation_default_state_basic_page',
              'value' => 's:5:"draft";',
            ],
          ],
          'node_type' => [
            ['type' => 'article'],
            ['type' => 'basic_page'],
          ],
          'workbench_moderation_states' => static::WORKBENCH_MODERATION_STATES,
          'workbench_moderation_transitions' => static::WORKBENCH_MODERATION_TRANSITIONS,
        ],
        'expected' => [],
      ],

      'Single moderation state' => [
        'source' => [
          'system' => static::SYSTEM,
          'variable' => [
            [
              'name' => 'node_options_article',
              'value' => 'a:1:{i:0;s:6:"status";}',
            ],
            [
              'name' => 'node_options_basic_page',
              'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
            ],
            [
              'name' => 'node_options_news',
              'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
            ],
            [
              'name' => 'workbench_moderation_default_state_article',
              'value' => 's:5:"draft";',
            ],
            [
              'name' => 'workbench_moderation_default_state_basic_page',
              'value' => 's:9:"published";',
            ],
            [
              'name' => 'workbench_moderation_default_state_news',
              'value' => 's:9:"published";',
            ],
          ],
          'node_type' => [
            ['type' => 'article'],
            ['type' => 'basic_page'],
            ['type' => 'news'],
          ],
          'workbench_moderation_states' => static::WORKBENCH_MODERATION_STATES,
          'workbench_moderation_transitions' => static::WORKBENCH_MODERATION_TRANSITIONS,
        ],
        'expected' => [
          [
            'value' => 's:9:"published";',
            'workbench_moderation_states' => $expected_states,
            'workbench_moderation_transitions' => $expected_transitions,
            'node_types' => [
              ['node_type' => 'basic_page'],
              ['node_type' => 'news'],
            ],
            'node_types_aggregated' => 'basic_page,news',
          ],
        ],
      ],

      'Missing and non-moderated node types' => [
        'source' => [
          'system' => static::SYSTEM,
          'variable' => array_merge(
            static::VARIABLE__NODE_OPTIONS,
            static::VARIABLE__WORKBENCH_MODERATION
          ),
          'node_type' => static::NODE_TYPE,
          'workbench_moderation_states' => static::WORKBENCH_MODERATION_STATES,
          'workbench_moderation_transitions' => static::WORKBENCH_MODERATION_TRANSITIONS,
        ],
        'expected' => [
          [
            'value' => 's:5:"draft";',
            'workbench_moderation_states' => $expected_states,
            'workbench_moderation_transitions' => $expected_transitions,
            'node_types' => [
              ['node_type' => 'book'],
              ['node_type' => 'event_calendar'],
              ['node_type' => 'memo'],
              ['node_type' => 'news'],
              ['node_type' => 'news_article'],
              ['node_type' => 'news_release'],
              ['node_type' => 'page'],
              ['node_type' => 'reports_and_presentations'],
              ['node_type' => 'rule'],
            ],
            'node_types_aggregated' => 'book,event_calendar,memo,news,news_article,news_release,page,reports_and_presentations,rule',
          ],
          [
            'value' => 's:9:"published";',
            'workbench_moderation_states' => $expected_states,
            'workbench_moderation_transitions' => $expected_transitions,
            'node_types' => [
              ['node_type' => 'form'],
              ['node_type' => 'psychiatric_formulary_drugs'],
            ],
            'node_types_aggregated' => 'form,psychiatric_formulary_drugs',
          ],
        ],
        'count' => 2,
        'config' => [],
      ],
    ];
  }

  /**
   * Records of the source site's system table.
   *
   * @const array[]
   */
  const SYSTEM = [
    [
      'name' => 'workbench_moderation',
      'schema_version' => 7001,
      'type' => 'module',
      'status' => 1,
    ],
  ];

  /**
   * Node option variable records of the source site's variable table.
   *
   * @const array[]
   */
  const VARIABLE__NODE_OPTIONS = [
    [
      'name' => 'node_options_article',
      'value' => 'a:3:{i:0;s:7:"promote";i:1;s:10:"moderation";i:2;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_blog_entry',
      'value' => 'a:1:{i:0;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_book',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_bulletins',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_circulars',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_contract',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ],
    [
      'name' => 'node_options_cta_description',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
    [
      'name' => 'node_options_document',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_event',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ],
    [
      'name' => 'node_options_event_calendar',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_external_video',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
    [
      'name' => 'node_options_faq',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
    [
      'name' => 'node_options_filedepot_folder',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ],
    [
      'name' => 'node_options_form',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_gallery',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
    [
      'name' => 'node_options_homepage_carousel',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
    [
      'name' => 'node_options_homepage_icons',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ],
    [
      'name' => 'node_options_meetings',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
    [
      'name' => 'node_options_memo',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_news',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_news_article',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_news_release',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_oca',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
    [
      'name' => 'node_options_office_closure',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_page',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_press_release',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
    [
      'name' => 'node_options_profile',
      'value' => 'a:0:{}',
    ],
    [
      'name' => 'node_options_project',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_project_release',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_provider_alerts',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ],
    [
      'name' => 'node_options_provider_letters',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_provider_letters_rsc',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ],
    [
      'name' => 'node_options_psychiatric_formulary_drugs',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_reports_and_presentations',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_rule',
      'value' => 'a:2:{i:0;s:10:"moderation";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_scc_letters',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ],
    [
      'name' => 'node_options_schemaorg_event',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
    [
      'name' => 'node_options_sc_letters',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ],
    [
      'name' => 'node_options_services',
      'value' => 'a:0:{}',
    ],
    [
      'name' => 'node_options_site_page',
      'value' => 'a:0:{}',
    ],
    [
      'name' => 'node_options_slider_content',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
    [
      'name' => 'node_options_tableau',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:8:"revision";}',
    ],
    [
      'name' => 'node_options_testimonials',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ],
  ];

  /**
   * Workbench Moderation variable records of the source site's variable table.
   *
   * @const array[]
   */
  const VARIABLE__WORKBENCH_MODERATION = [
    [
      'name' => 'workbench_moderation_default_state_article',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_blog_entry',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_book',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_bulletins',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_circulars',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_contract',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_cta_description',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_document',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_event',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_event_calendar',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_external_video',
      'value' => 's:0:"";',
    ],
    [
      'name' => 'workbench_moderation_default_state_faq',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_form',
      'value' => 's:9:"published";',
    ],
    [
      'name' => 'workbench_moderation_default_state_homepage_icons',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_meetings',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_memo',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_news',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_news_article',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_news_release',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_oca',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_office_closure',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_page',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_press_release',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_profile',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_provider_alerts',
      'value' => 's:0:"";',
    ],
    [
      'name' => 'workbench_moderation_default_state_provider_letters',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_provider_letters_rsc',
      'value' => 's:0:"";',
    ],
    [
      'name' => 'workbench_moderation_default_state_psychiatric_formulary_drugs',
      'value' => 's:9:"published";',
    ],
    [
      'name' => 'workbench_moderation_default_state_reports_and_presentations',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_rule',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_scc_letters',
      'value' => 's:0:"";',
    ],
    [
      'name' => 'workbench_moderation_default_state_sc_letters',
      'value' => 's:0:"";',
    ],
    [
      'name' => 'workbench_moderation_default_state_services',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_site_page',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_slider_content',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_tableau',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_default_state_testimonials',
      'value' => 's:5:"draft";',
    ],
    [
      'name' => 'workbench_moderation_nodedraft_disabled',
      'value' => 'b:0;',
    ],
    [
      'name' => 'workbench_moderation_noderevision_disabled',
      'value' => 'b:0;',
    ],
  ];

  /**
   * Records of the source site's node_type table.
   *
   * @const array[]
   */
  const NODE_TYPE = [
    ['type' => 'blog'],
    ['type' => 'book'],
    ['type' => 'event_calendar'],
    ['type' => 'external_video'],
    ['type' => 'feed'],
    ['type' => 'feed_item'],
    ['type' => 'filedepot_folder'],
    ['type' => 'form'],
    ['type' => 'forum'],
    ['type' => 'homepage_icons'],
    ['type' => 'memo'],
    ['type' => 'news'],
    ['type' => 'news_article'],
    ['type' => 'news_release'],
    ['type' => 'node_block'],
    ['type' => 'page'],
    ['type' => 'panel'],
    ['type' => 'photo'],
    ['type' => 'poll'],
    ['type' => 'profile'],
    ['type' => 'provider_letters'],
    ['type' => 'psychiatric_formulary_drugs'],
    ['type' => 'reports_and_presentations'],
    ['type' => 'rule'],
    ['type' => 'tableau'],
    ['type' => 'webform'],
  ];

  /**
   * Moderation state records of the source workbench_moderation_state table.
   *
   * @const array[]
   */
  const WORKBENCH_MODERATION_STATES = [
    [
      'name' => 'draft',
      'label' => 'Draft',
      'description' => 'Work in progress',
      'weight' => -10,
    ],
    [
      'name' => 'needs_review',
      'label' => 'Needs Review',
      'description' => 'Ready for moderation',
      'weight' => -9,
    ],
    [
      'name' => 'published',
      'label' => 'Published',
      'description' => 'Make this version live',
      'weight' => -7,
    ],
    [
      'name' => 'reviewed',
      'label' => 'Reviewed',
      'description' => 'Work has been reviewed',
      'weight' => -8,
    ],
    [
      'name' => 'unpublished',
      'label' => 'Unpublished',
      'description' => 'Unpublish content',
      'weight' => 0,
    ],
  ];

  /**
   * State transitions of the source workbench_moderation_transitions table.
   *
   * @const array[]
   */
  const WORKBENCH_MODERATION_TRANSITIONS = [
    [
      'id' => 1,
      'name' => 'Single edit and ready for review',
      'from_name' => 'published',
      'to_name' => 'needs_review',
    ],
    [
      'id' => 6,
      'name' => 'Submit for Review',
      'from_name' => 'draft',
      'to_name' => 'needs_review',
    ],
    [
      'id' => 11,
      'name' => 'Edited by Admin or Publisher',
      'from_name' => 'draft',
      'to_name' => 'published',
    ],
    [
      'id' => 16,
      'name' => 'Work in Progress',
      'from_name' => 'published',
      'to_name' => 'draft',
    ],
    [
      'id' => 21,
      'name' => 'Reviewed',
      'from_name' => 'draft',
      'to_name' => 'reviewed',
    ],
    [
      'id' => 26,
      'name' => 'Reviewed and ready for publication',
      'from_name' => 'reviewed',
      'to_name' => 'published',
    ],
    [
      'id' => 31,
      'name' => 'Needs more work',
      'from_name' => 'needs_review',
      'to_name' => 'draft',
    ],
    [
      'id' => 36,
      'name' => 'Unpublish',
      'from_name' => 'published',
      'to_name' => 'unpublished',
    ],
  ];

}
