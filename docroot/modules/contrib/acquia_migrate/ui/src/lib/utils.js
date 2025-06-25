import escapeRegExp from 'lodash/escapeRegExp';

const precision = (value, power = 4) => {
  const exp = 10 ** power;
  return Math.round(value * exp) / exp;
};
const safeDivide = (n, d) => (d !== 0 ? n / d : 0);
export const maxValue = (value, max) => (value < max ? value : max);
export const progress = (current, total) =>
  precision(safeDivide(maxValue(current, total), total));
export const progressFormat = (amount) => (amount * 100).toFixed(2);

export const progressAll = (items) => {
  const c = 1 / items.length;
  return precision(
    items.reduce((acc, cur) => {
      acc += c * safeDivide(cur.importedCount, cur.totalCount);
      return acc;
    }, 0.0),
  );
};

export const progressDirection = (migration, operation) =>
  operation === 'rollback'
    ? progress(
        migration.totalCount - migration.importedCount,
        migration.totalCount,
      )
    : progress(migration.importedCount, migration.totalCount);
/**
 * Adapted from https://www.30secondsofcode.org/js/s/deep-get/.
 *
 * @param {object} obj
 *   Object to search.
 * @param {string} property
 *   The nested property e.g 'data.attributes.title'
 * @return {*}
 *   The property value or null if not found.
 */
export const getDeep = (obj, property) =>
  property.split('.').reduce((xs, x) => (xs && xs[x] ? xs[x] : null), obj);

export const getMigrationById = (id, migrations) =>
  migrations.find((m) => m.id === id);

export const getQueueItemById = (id, queue) => queue.find((q) => q.id === id);

const getDependentMigrations = (dependencies, migrations) =>
  dependencies
    .map(({ id }) => getMigrationById(id, migrations))
    .filter((m) => !!m);

export const getPreview = (links) => {
  const preview = Object.fromEntries(
    Object.entries({
      links:
        'https://drupal.org/project/acquia_migrate#link-rel-preview-migration-row',
      unmet:
        'https://drupal.org/project/acquia_migrate#link-rel-unmet-requirement',
    }).map(([key, rel]) => [
      key,
      Object.fromEntries(
        Object.entries(links).filter((link) => link[1].rel === rel),
      ),
    ]),
  );
  const previewable =
    Object.values(preview).filter((value) => Object.values(value).length)
      .length > 0;

  return { preview, previewable };
};

/**
 * Get the status for a dependency. If it has the action link it is incomplete.
 * If it does not have the link but has dependencies those need to be checked.
 * If a circular dependency is found it will appear as incomplete, but whether
 * that migration may be run depends on if it has the action link.
 *
 * @param {string} id
 *   Single migration dependency id.
 * @param {array} migrations
 *   The full list of migrations to check against.
 * @param {string} action
 *   Whether the dependency is for an import or rollback.
 * @param {array} sourceIds
 *   The list of parent migrations to not traverse.
 *
 * @return {boolean}
 *   The dependency is incomplete if it or any of its dependents are incomplete.
 */
const dependenciesAreIncomplete = (id, migrations, action, sourceIds) => {
  const migration = getMigrationById(id, migrations);
  // If the migrations list is filtered the dependency id may not be present.
  if (!migration) {
    return false;
  }
  const { activity, links } = migration;
  const hasAction = links && links.hasOwnProperty(action);
  const hasDependencies = (migration.dependencies || []).length;
  // If the dependency is not idle, let it first finish its activity.
  if (activity !== 'idle') {
    return true;
  }
  // If the dependency does not have a link, it does not need to be run first.
  // If it does not have data to migrate or no dependencies, then there is no
  // need to check deeper.
  if (!hasAction && (migration.totalCount === 0 || !hasDependencies)) {
    return false;
  }
  // If the dependency has the link it must be run first.
  if (hasAction) {
    // Dependency has not completed action.
    return true;
  }
  // The dependency may not have a link because it also has dependencies.
  if (hasDependencies) {
    // If any deeper dependencies are incomplete THIS dependency is incomplete.
    return migration.dependencies.reduce((incomplete, dep) => {
      if (incomplete) {
        return incomplete;
      }
      // If any dependencies point back to a parent (circular dependency).
      if (sourceIds.includes(dep.id)) {
        console.warn(`Circular dependency detected for ${dep.id}`);
        return true;
      }
      // Traverse to check if any deeper dependencies are incomplete.
      return dependenciesAreIncomplete(dep.id, migrations, action, [
        ...sourceIds,
        id,
      ]);
    }, false);
  }
};

/**
 * Format the migration dependencies.
 *
 * @param {object} migration
 *   Parsed migration data @see parseMigration.
 * @param {array} migrations
 *   The full list of migrations to check against.
 * @param {string} action
 *   Whether the dependency is for an import or rollback.
 * @return {{id:string, label:string, incomplete:boolean }}
 *   The dependency info and status.
 */
export const dependencyStatus = (migration, migrations, action = 'import') =>
  getDependentMigrations(migration.dependencies, migrations).map(
    ({ id, label }) => ({
      id,
      label,
      incomplete: dependenciesAreIncomplete(id, migrations, action, [
        migration.id,
      ]),
    }),
  );

/**
 * Traverse dependencies and return a flat list.
 *
 * @param {object} migration
 *   Parsed migration data @see parseMigration.
 * @param {array} migrations
 *   The full list of migrations to check against.
 * @param {Set} sourceIds
 *   The list of parent migrations to not traverse.
 * @return {[]}
 *   A flat unique list of dependencies.
 */
export const deepDependencyList = (
  migration,
  migrations,
  sourceIds = new Set(),
) => [
  ...getDependentMigrations(migration.dependencies, migrations).reduce(
    (list, dependency) => {
      if (sourceIds.has(dependency.id)) {
        return list;
      }
      // Ignore empty dependencies: no need to require them.
      if (dependency.totalCount === 0) {
        return list;
      }
      sourceIds.add(migration.id);
      list.add(dependency.id);
      return new Set([
        ...list,
        ...deepDependencyList(dependency, migrations, sourceIds),
      ]);
    },
    new Set(),
  ),
];

/**
 * Get the HTTP method for the given rel.
 * @param {string} rel
 *   The link rel.
 * @return {string}
 *   The HTTP method.
 */
export const getMethodFromRel = (rel) => {
  const known = {
    'https://drupal.org/project/acquia_migrate#link-rel-update-resource':
      'PATCH',
    'https://drupal.org/project/acquia_migrate#link-rel-migration-mapping-override-field':
      'POST',
  };

  return known[rel] ? known[rel] : null;
};
/**
 * Return an array of link objects matching the given rel.
 *
 * @param {string} rel
 *   The link rel.
 * @param {object} links
 *   The links object from a response.
 * @return {[]}
 *   Array of links.
 */
export const getLinksWithRel = (rel, links) =>
  Object.values(links).filter((link) => link.rel === rel);

/**
 * Filter links by the migration-messages rel.
 *
 * @param {object} links
 *   The links object from a response.
 * @return {[]}
 *   Array of links @see getLinksWithRel.
 */
export const getMessagesLinks = (links) =>
  getLinksWithRel(
    'https://drupal.org/project/acquia_migrate#link-rel-migration-messages',
    links,
  );

/**
 * Filter links by the batch-process rel.
 *
 * @param {object} links
 *   The links object from a response.
 * @return {[]}
 *   Array of links @see getLinksWithRel.
 */
export const getBatchLinks = (links) =>
  getLinksWithRel(
    'https://drupal.org/project/acquia_migrate#link-rel-start-batch-process',
    links,
  );

/**
 * Filter links by the bulk-update rel.
 *
 * @param {object} links
 *   The links object from a response.
 * @return {[]}
 *   Array of links @see getLinksWithRel.
 */
export const getBulkLinks = (links) =>
  getLinksWithRel(
    'https://drupal.org/project/acquia_migrate#link-rel-bulk-update-migrations',
    links,
  );

/**
 * Filter links by the preselect rel.
 *
 * @param {object} links
 *   The links object from a response.
 * @return {[]}
 *   Array of links @see getLinksWithRel.
 */
export const getQueryLinks = (links) =>
  getLinksWithRel(
    'https://drupal.org/project/acquia_migrate#link-rel-query',
    links,
  );

/**
 * Filter links by the preselect rel.
 *
 * @param {object} links
 *   The links object from a response.
 * @return {[]}
 *   Array of links @see getLinksWithRel.
 */
export const getPreselectLinks = (links) =>
  getLinksWithRel(
    'https://drupal.org/project/acquia_migrate#link-rel-preselect-migrations',
    links,
  );

/**
 * Filter links by the update rel.
 *
 * @param {object} links
 *   The links object from a response.
 * @return {[]}
 *   Array of links @see getLinksWithRel.
 */
export const getUpdateLinks = (links) =>
  getLinksWithRel(
    'https://drupal.org/project/acquia_migrate#link-rel-update-resource',
    links,
  );

/**
 * Filter links by the mapping override rel.
 *
 * @param {object} links
 *   The links object from a response.
 * @return {[]}
 *   Array of links @see getLinksWithRel.
 */
export const getOverrideLinks = (links) =>
  getLinksWithRel(
    'https://drupal.org/project/acquia_migrate#link-rel-migration-mapping-override-field',
    links,
  );

/**
 * Filter links by the unmet rel.
 *
 * @param {object} links
 *   The links object from a response.
 * @return {[]}
 *   Array of links @see getLinksWithRel.
 */
export const getUnmetLinks = (links) =>
  getLinksWithRel(
    'https://drupal.org/project/acquia_migrate#link-rel-unmet-requirement',
    links,
  );

export const getStaleDataLinks = (links) =>
  getLinksWithRel(
    'https://drupal.org/project/acquia_migrate#link-rel-stale-data',
    links,
  );

export const getExtLinks = (links) =>
  Object.values(links).filter((link) => link.type === 'text/html');

/**
 * Check for the preselect link
 *
 * @param {object} links
 *   The links object from a response.
 * @return {boolean}
 *   Whether the link was found.
 */
export const hasMessagesLink = (links) => !!getMessagesLinks(links).length;

/**
 * Check for an batch-process link by its key
 *
 * @param {object} links
 *   The links object from a response.
 * @param {string} key
 *   The property in links to check for.
 * @return {boolean}
 *   Whether the link was found.
 */
export const hasBatchLink = (links, key) =>
  links.hasOwnProperty(key) && !!getBatchLinks([links[key]]).length;

/**
 * Check for the bulk update link
 *
 * @param {object} links
 *   The links object from a response.
 * @return {boolean}
 *   The links object from a response.
 */
export const hasBulkLink = (links) => !!getBulkLinks(links).length;

/**
 * Check for the preselect link
 *
 * @param {object} links
 *   The links object from a response.
 * @return {boolean}
 *   The links object from a response.
 */
export const hasPreselectLink = (links) => !!getPreselectLinks(links).length;

/**
 * Check for an update link by its key
 *
 * @param {object} links
 *   The links object from a response.
 * @param {string} key
 *   The property in links to check for.
 * @return {boolean}
 *   Whether the link was found.
 */
export const hasUpdateLink = (links, key) =>
  links.hasOwnProperty(key) && !!getUpdateLinks([links[key]]).length;

/**
 * Check for the initial-import link
 * @see getBatchLink
 *
 * @param {object} links
 *   The links object from a response.
 * @return {boolean}
 *   The links object from a response.
 */
export const hasInitialImportLink = (links) =>
  hasBatchLink(links, 'initial-import');

/**
 * Check for the complete update link
 * @see getUpdateLink
 *
 * @param {object} links
 *   The links object from a response.
 * @return {boolean}
 *   The links object from a response.
 */
export const hasCompleteLink = (links) => hasUpdateLink(links, 'complete');

/**
 * Check for the skip update link
 * @see getUpdateLink
 *
 * @param {object} links
 *   The links object from a response.
 * @return {boolean}
 *   The links object from a response.
 */
export const hasSkipLink = (links) => hasUpdateLink(links, 'skip');

/**
 * Check for the unskip update link
 * @see getUpdateLink
 *
 * @param {object} links
 *   The links object from a response.
 * @return {boolean}
 *   The links object from a response.
 */
export const hasUnskipLink = (links) => hasUpdateLink(links, 'unskip');

export const STATUS = {
  ready: {
    code: 'READY',
    message: 'Migration ready, unprocessed',
  },
  partialOK: {
    code: 'IMPORTING_OK',
    message: 'Migration partially processed',
  },
  partialNotice: {
    code: 'IMPORTING_NOTICE',
    message:
      'Migration partially processed, number of rows imported do not match',
  },
  partialErr: {
    code: 'IMPORTING_ERR',
    message: 'Migration partially processed with errors, check messages',
  },
  importedOK: {
    code: 'IMPORTED_OK',
    message: 'Migration completely processed successfully',
  },
  importedWarning: {
    code: 'IMPORTED_WARN',
    message: 'Migration completely processed with errors, check messages',
  },
  importedNotice: {
    code: 'IMPORTED_NOTICE',
    message: 'Migration completely processed but import incomplete',
  },
  importedErr: {
    code: 'IMPORTED_ERR',
    message: 'Migration incompletely processed with errors, check messages',
  },
  completedOK: {
    code: 'COMPLETED_OK',
    message: 'Migration completed',
  },
  completedOutdated: {
    code: 'COMPLETED_OUTDATED',
    message: 'Migration completed, but new content found',
  },
  skippedOK: {
    code: 'SKIPPED_OK',
    message: 'Migration skipped',
  },
  skippedCheck: {
    code: 'SKIPPED_CHECK',
    message: 'Migration skipped, but content is available to import.',
  },
  unknownErr: {
    code: 'UNKNOWN_ERR',
    message: 'Migration in an unexpected state.',
  },
};

/**
 * Get the migration status based current errors and attributes.
 *
 * @param {number} importedCount
 *   The number of imported rows.
 * @param {number} processedCount
 *   The number of processed rows.
 * @param {number} totalCount
 *   The total number of rows.
 * @param {boolean} completed
 *   Whether the migration is marked "completed"
 * @param {boolean} skipped
 *   Whether the migration is marked "skipped"
 * @param {object} links
 *   The links object from this migration.
 * @return {{code:{string}, message:{string}}}
 *   The status code and message.
 */
export const migrationStatus = ({
  importedCount,
  processedCount,
  totalCount,
  completed,
  skipped,
  links = {},
}) => {
  const hasErrors = hasMessagesLink(links);
  if (completed) {
    return importedCount === totalCount
      ? STATUS.completedOK
      : STATUS.completedOutdated;
  }
  if (skipped) {
    return totalCount === 0 ? STATUS.skippedOK : STATUS.skippedCheck;
  }
  if (processedCount === 0 && totalCount > 0) {
    return STATUS.ready;
  }
  if (processedCount > 0 && processedCount < totalCount) {
    if (hasErrors) {
      return STATUS.partialErr;
    }
    return importedCount === processedCount
      ? STATUS.partialOK
      : STATUS.partialNotice;
  }
  if (processedCount === totalCount) {
    if (hasErrors) {
      return importedCount === processedCount
        ? STATUS.importedWarning
        : STATUS.importedErr;
    }
    return importedCount === processedCount
      ? STATUS.importedOK
      : STATUS.importedNotice;
  }

  return STATUS.unknownErr;
};

const needsReview = (migration) =>
  ['IMPORTED_WARN', 'IMPORTED_NOTICE', 'IMPORTED_ERR'].includes(
    migration.status.code,
  ) && !migration.completed;

const needsRefresh = (migration) => migration.stale;

const isActive = (migration) =>
  !migration.skipped && !migration.completed && !needsReview(migration);

export const filterByStatus = (migrations, key) =>
  migrations.filter((migration) => migration.status.code === STATUS[key].code);

export const filterByNeedsReview = (migrations) =>
  migrations.filter(needsReview);

export const filterByNeedsRefresh = (migrations) =>
  migrations.filter(needsRefresh);

/**
 * Parse an unknown variable into a string.
 *
 * @param {string|number|boolean|array|object|null} value
 *   An unknown value.
 * @return {string}
 *   The parsed value.
 */
export const parseUnknownValue = (value) => {
  if (value === null) {
    return 'null';
  }
  if (typeof value === 'string') {
    return value.length ? value : '""';
  }
  if (typeof value === 'boolean' || typeof value === 'number') {
    return `${value}`;
  }
  if (Array.isArray(value)) {
    return value.length === 0 ? '[]' : value.map(parseUnknownValue).join('; ');
  }
  if (value === Object(value)) {
    const entries = Object.entries(value);
    return entries.length === 0
      ? '{}'
      : `{ ${entries
          .map(([key, val]) => {
            return `${key}: ${parseUnknownValue(val)}`;
          })
          .join(', ')} }`;
  }
};

export const getResource = ({ href }) =>
  fetch(href, {
    method: 'GET',
    headers: { Accept: 'application/vnd.api+json' },
  })
    .then((response) => {
      if (!response.ok && response.status !== 304) {
        throw new Error(response.statusText);
      }
      return response.json().then((json) => json);
    })
    .catch((error) => error);

export const getActiveMigrations = (migrations) => migrations.filter(isActive);

export const getSelectableMigrations = (migrations, operation) =>
  migrations.filter((migration) => migration.links.hasOwnProperty(operation));

export const getMigrationPage = (migration) => {
  if (isActive(migration)) {
    return 'active';
  }
  if (needsReview(migration)) {
    return 'needs-review';
  }
  if (migration.completed) {
    return 'complete';
  }
  if (migration.skipped) {
    return 'skip';
  }
  return '';
};

/**
 * Return a unique list of all available operations for the current migrations.
 *
 * @param {array} migrations
 *   The full list of migrations to check against.
 * @param {array} allowed
 *   An optional list of permitted operations.
 * @return {Map}
 *   Map of [key: title], e.g. 'rollback-and-import': 'Rollback and Import'
 */
export const getOperations = (migrations, allowed = []) => {
  return migrations.reduce((list, migration) => {
    Object.entries(migration.links)
      .filter(
        ([, value]) =>
          value.rel ===
          'https://drupal.org/project/acquia_migrate#link-rel-start-batch-process',
      )
      .forEach(([key, value]) => {
        if (!list.has(key)) {
          // Empty or undefined allowed permits any matches.
          if (allowed.length) {
            if (allowed.includes(key)) {
              list.set(key, value.title);
            }
          } else {
            list.set(key, value.title);
          }
        }
      });

    return list;
  }, new Map());
};

export const sumMigrationsCount = (migrations, attribute) =>
  migrations.reduce((total, migration) => total + migration[attribute], 0);

export const listMigrationsUnderlying = (migrations) =>
  migrations.reduce(
    (list, migration) => [
      ...list,
      ...migration.consistsOf.map((plugin) => plugin.id),
    ],
    [],
  );

/**
 * Sort any active migrations to the beginning of the list.
 *
 * @param {array} migrations
 *   The full list of migrations.
 * @param {array} queue
 *   The currently processing queue.
 * @return {[]}
 *   Sorted list of migrations.
 */
export const sortMigrationsByActive = (migrations, queue) => {
  const inactiveMigrations = migrations.filter(
    (migration) => migration.status === 'removed',
  );
  const activeMigrations = queue.reduce((list, queueItem) => {
    const activeMigration = migrations
      .filter((migration) => migration.status !== 'removed')
      .find((migration) => migration.id === queueItem.id);
    if (activeMigration && !list.includes(activeMigration)) {
      list.push(activeMigration);
    }
    return list;
  }, []);

  return [
    ...inactiveMigrations,
    ...activeMigrations,
    ...migrations.filter(
      (migration) =>
        !inactiveMigrations.includes(migration) &&
        !activeMigrations.includes(migration),
    ),
  ];
};

export const sortMigrationsByIndex = (migrations) =>
  [...migrations].sort((a, b) => a.index - b.index);

export const sortMigrationsByLastImported = (migrations) =>
  [...migrations].sort((a, b) => {
    const [endTimeA, endTimeB] = [a, b].map(
      (migration) => new Date(getDeep(migration, 'lastImported.endTime')),
    );
    return endTimeB - endTimeA;
  });

export const listHasNewRows = (list) =>
  list.currentRows.some((listItem) => listItem.status === 'new');

export const listHasRefreshableRows = (list) =>
  list.currentRows.some((listItem) => listItem.hasRefresh);

/**
 * Check if fields contain a matching text value, ignore if search string < 3;
 *
 * @param {string} search
 *   The text to search for in each field.
 * @param {array} fields
 *   List of string values to search.
 * @return {boolean}
 *   Used in an array.filter, false will remove the item for not matching.
 */
export const searchTextFields = (search, fields) => {
  if (search.length < 3) {
    return true;
  }
  const expr = new RegExp(escapeRegExp(search), 'gmi');
  return fields.filter((text) => !!text).some((text) => expr.test(text));
};
