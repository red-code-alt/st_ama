INTRODUCTION
------------

Workbench Moderation Migrate migrates Drupal 7 Workbench Moderation flows
to Drupal 9 and alters node revision migrations to migrate them with the right
moderation state.


REQUIREMENTS
------------

* [Migrate Drupal][1] (included in Drupal core)
* [Node][2] (included in Drupal core)
* [Content Moderation][3] (included in Drupal core)

INSTALLATION
------------

Install Workbench Moderation Migrate as you would normally install a contributed
Drupal 8 or 9 module (so: use Composer!).


CONFIGURATION
-------------

This module does not have any configuration option.


USAGE
-----

Short:
- Enable [Migrate Drupal UI][4].
- Configure your Drupal 7 source database.
- Perform the upgrade!

Workbench Moderation Migrate module supports only with complete node migrations
(their plugin ID starts with `d7_node_complete:`).

Since the migrate import command in Drush 10.4+ (or Migrate Tools) aren't
building migration dependency graph, they're running both the complete
(`d7_node_complete:<node_type>`) and deprecated (`d7_node:<node_type>`,
`d7_node_revision:<node_type>`, `d7_node_translation:<node_type>`), this causes
data integrity problems. Please keep this in mind if you don't use Drupal core's
Migrate Drupal UI.


MAINTAINERS
-----------

* Zoltán Horváth (huzooka) - https://www.drupal.org/u/huzooka

This project has been sponsored by [Acquia][5].

[1]: https://drupal.org/docs/core-modules-and-themes/core-modules/migrate-drupal-module
[2]: https://drupal.org/docs/core-modules-and-themes/core-modules/node-module
[3]: https://drupal.org/docs/8/core/modules/content-moderation
[4]: https://drupal.org/docs/core-modules-and-themes/core-modules/migrate-drupal-ui-module
[5]: https://acquia.com
