import React, { useContext, useEffect, useReducer, useState } from 'react';
import PropTypes from 'prop-types';

import useModal from '../hooks/use-modal';
import useQueue from '../hooks/use-queue';
import useBulkUpdate from '../hooks/use-bulk-update';
import useInitialImport from '../hooks/use-initial-import';
import useStaleData from '../hooks/use-stale-data';
import useOffline from '../hooks/use-offline';
import { ErrorContext } from '../errors/try-catch';
import { useCollection } from '../hooks/use-resource';

import { update } from '../lib/api';
import {
  filterByNeedsReview,
  filterByNeedsRefresh,
  getActiveMigrations,
  migrationStatus,
  sortMigrationsByIndex,
  sortMigrationsByLastImported,
  listHasNewRows,
  listHasRefreshableRows,
  getMethodFromRel,
  maxValue,
  getMigrationById,
  hasUpdateLink,
} from '../lib/utils';
import useLimiter from '../hooks/use-limiter';
import MigrationTextLimiter from '../lib/limiter/migration-text-limiter';

/**
 * Select useful properties from migration.
 *
 * @param {object} migration
 *   Single migration resource.
 * @param {number} index
 *   The current key of the migration, used for sorting.
 * @return {object}
 *   Parsed migration object.
 */
const parseMigration = (migration, index) => {
  const { id, attributes, relationships, links } = migration;
  const {
    label,
    lastImported,
    processedCount,
    importedCount,
    totalCount,
    completed,
    stale,
    skipped,
    activity,
  } = attributes;
  const { dependencies, consistsOf } = relationships;
  // Guard against importedCount or processedCount > totalCount errors.
  const safeCounts = {
    importedCount: maxValue(importedCount, totalCount),
    processedCount: maxValue(processedCount, totalCount),
    totalCount,
  };
  const status = migrationStatus({ ...safeCounts, completed, skipped, links });
  return {
    id,
    index,
    label,
    status,
    activity,
    lastImported,
    ...safeCounts,
    completed,
    stale,
    skipped,
    dependencies: dependencies.data.map((item) => {
      const { dependencyReasons } = item.meta;
      return { id: item.id, dependencyReasons };
    }),
    consistsOf: consistsOf.data.map((item) => ({ id: item.id })),
    links,
  };
};

const migrationPageConfig = [
  {
    name: 'active',
    title: 'In Progress',
    path: '/',
    to: './',
    initialized: false,
    columns: ['actions', 'name', 'status', 'imported', 'messages', 'ops'],
    currentRows: [],
    filter: getActiveMigrations,
    sort: sortMigrationsByIndex,
    showNew: listHasNewRows,
    allowedOps: ['import', 'rollback'],
  },
  {
    name: 'needsReview',
    title: 'Needs Review',
    path: 'needs-review',
    to: 'needs-review',
    initialized: false,
    columns: ['actions', 'name', 'imported', 'messages', 'lastImported', 'ops'],
    currentRows: [],
    filter: filterByNeedsReview,
    sort: sortMigrationsByLastImported,
    showNew: listHasNewRows,
    allowedOps: ['rollback'],
  },
  {
    name: 'completed',
    title: 'Completed',
    path: 'completed',
    to: 'completed',
    initialized: false,
    columns: ['actions', 'name', 'imported', 'messages', 'lastImported', 'ops'],
    currentRows: [],
    filter: (migrations) =>
      migrations.filter((migration) => migration.completed),
    sort: sortMigrationsByLastImported,
    showNew: listHasNewRows,
    allowedOps: ['rollback'],
  },
  {
    name: 'skipped',
    title: 'Skipped',
    path: 'skipped',
    to: 'skipped',
    initialized: false,
    columns: ['name', 'imported', 'messages', 'ops'],
    currentRows: [],
    filter: (migrations) => migrations.filter((migration) => migration.skipped),
    sort: sortMigrationsByIndex,
    showNew: listHasNewRows,
  },
  {
    name: 'refresh',
    title: 'Refresh',
    path: 'refresh',
    to: 'refresh',
    initialized: false,
    columns: ['actions', 'name', 'status', 'imported'],
    currentRows: [],
    filter: filterByNeedsRefresh,
    sort: sortMigrationsByIndex,
    showNew: listHasRefreshableRows,
    allowedOps: ['refresh'],
  },
];

const updateLists = (lists, migrations) => {
  return lists.reduce((updated, list) => {
    // Run a custom filter function for each list.
    const changed = list.filter(migrations);
    const hash = Date.now();
    // If all lists have been initialized then changes are "new".
    if (!list.initialized) {
      list.currentRows = list.sort(
        changed.map((migration) => ({
          key: `${migration.id}-${hash}`,
          id: migration.id,
          label: migration.label,
          index: migration.index,
          lastImported: migration.lastImported,
          page: list.name,
          status: 'initial',
          hasRefresh: migration.links.hasOwnProperty('refresh'),
        })),
      );
    } else {
      const currentIds = list.currentRows
        .filter((listItem) => listItem.status !== 'removed')
        .map((listItem) => listItem.id);
      const changedIds = changed.map((migration) => migration.id);

      const removals = currentIds.filter((id) => !changedIds.includes(id));
      const additions = changedIds.filter((id) => !currentIds.includes(id));

      if (additions.length) {
        // Add new items.
        list.currentRows = list.sort([
          ...list.currentRows,
          ...changed
            .filter((migration) => additions.includes(migration.id))
            .map((migration) => ({
              key: `${migration.id}-added-${hash}`,
              id: migration.id,
              label: migration.label,
              index: migration.index,
              lastImported: migration.lastImported,
              page: list.name,
              status: 'new',
              hasRefresh: migration.links.hasOwnProperty('refresh'),
            })),
        ]);
      }

      if (removals.length) {
        // Mark removed items.
        list.currentRows = list.sort(
          list.currentRows.map((listItem) =>
            removals.includes(listItem.id)
              ? { ...listItem, status: 'removed' }
              : listItem,
          ),
        );
      }
    }

    list.activeCount = list.currentRows.filter(
      (listItem) => listItem.status !== 'removed',
    ).length;

    // List has been run through once, do not apply "new" status the first time.
    if (!list.initialized) {
      list.initialized = true;
    }

    return [...updated, list];
  }, []);
};

const getNewIdsFromList = (list) =>
  list.currentRows.filter((row) => row.status === 'new').map((row) => row.id);

const removeItemFromList = (list, id) => {
  const marked = list.currentRows.find(
    (row) => row.id === id && row.status === 'removed',
  );
  return marked
    ? {
        ...list,
        currentRows: list.currentRows.filter((row) => row.key !== marked.key),
      }
    : list;
};

const removeItemsFromList = (list, ids) => {
  const markedIds = list.currentRows
    .filter((row) => ids.includes(row.id) && row.status === 'removed')
    .map((row) => row.key);
  // Checking for markedIds first to avoid unnecessarily rebuilding lists.
  return markedIds.length
    ? {
        ...list,
        currentRows: list.currentRows.filter(
          (row) => !markedIds.includes(row.key),
        ),
      }
    : list;
};

const resetNewItemsFromList = (list) => ({
  ...list,
  currentRows: [
    ...list.currentRows.map((row) =>
      row.status === 'new' ? { ...row, status: 'initial' } : row,
    ),
  ],
});

const listReducer = (state, action) => {
  if (action.type === 'remove') {
    return state.map((list) =>
      list.name === action.page ? removeItemFromList(list, action.id) : list,
    );
  }
  if (action.type === 'reset') {
    // Reset this page new items and clean up any remaining marked for removal.
    const ids = getNewIdsFromList(
      state.find((listItem) => listItem.name === action.page),
    );
    return state.map((list) =>
      list.name === action.page
        ? resetNewItemsFromList(list)
        : removeItemsFromList(list, ids),
    );
  }
  return updateLists(state, action.migrations);
};

/**
 * @type {React.Context<{}>}
 */
const MigrationContext = React.createContext({});

/**
 * Provides global props to the Context.
 *
 * @param {string} basepath
 *   The base url of the app.
 * @param {string} basepathModule
 *   The base url of the module auditor app.
 * @param {string} basepathPreselect
 *   The base url of the preselect app.
 * @param {string} source
 *   The API entrypoint.
 * @param {node} children
 *   React nodes passed into this Context.
 * @return {ReactNode}
 *   <MigrationProvider value={value} />
 */
const MigrationProvider = ({
  basepath,
  basepathModule,
  basepathPreselect,
  source,
  children,
}) => {
  const [migrations, setMigrations] = useState([]);
  const [controllingSession, setControllingSession] = useState(null);
  const [links, setLinks] = useState(null);
  const { initialImportLink, setInitialImporting, isInitialImporting } =
    useInitialImport(links);
  const { limiters, updateLimiter } = useLimiter({
    searchTitle: new MigrationTextLimiter({
      name: 'searchTitle',
      value: '',
    }),
  });
  const { modal, showModal } = useModal();
  const [lists, dispatchLists] = useReducer(listReducer, migrationPageConfig);
  const {
    active,
    queue,
    addToQueue,
    removeFromQueue,
    resetInQueue,
    clearQueue,
    cleanupQueue,
    isQueueRunning,
    setQueueRunning,
    setQueueClear,
    isQueueStopped,
    isQueueCompleted,
    isActivePending,
  } = useQueue();
  const safeToCloseApp =
    !isInitialImporting && (isQueueCompleted || queue.length === 0);
  const { bulkUpdateMigrations } = useBulkUpdate(links);
  const { throwError } = useContext(ErrorContext);
  const { isRefreshing } = useStaleData(links);
  const { isLoading, document } = useCollection({
    href: source,
    handleError: throwError,
  });
  const offline = useOffline();

  // Active migration (for this session).
  const [activeStopping, setActiveStopping] = useState(null);
  const activeMigration = active
    ? getMigrationById(active.id, migrations)
    : null;
  const activeStopLink =
    activeMigration && hasUpdateLink(activeMigration.links, 'stop')
      ? activeMigration.links.stop
      : null;
  const activeStopHref = activeStopLink ? activeStopLink.href : null;

  const updateMigration = ({
    link,
    toggle = () => {},
    callback = () => {},
    onError = () => {},
  }) => {
    const { href, rel, title, params } = link;
    const method = getMethodFromRel(rel);
    if (!method) {
      console.error('No method found for rel', rel);
      return;
    }

    const { confirm, data } = params;
    const action = () => {
      toggle();
      update({ href, method, payload: { data } }).then((response) => {
        toggle();
        if (response.ok) {
          callback();
        } else {
          onError();
        }
      });
    };

    if (confirm) {
      showModal(title, confirm, action);
    } else {
      action();
    }
  };

  const removeFromList = (id, page) => {
    dispatchLists({ type: 'remove', id, page });
  };

  const resetList = (page) => {
    dispatchLists({ type: 'reset', page });
    cleanupQueue();
  };

  useEffect(() => {
    if (document) {
      setLinks(document.links);
      setControllingSession(document.meta.controllingSession);
      setMigrations(document.data.map(parseMigration));
    }
  }, [document]);

  useEffect(() => {
    if (migrations.length) {
      dispatchLists({ type: 'update', migrations });
    }
  }, [migrations]);

  // PAUSE: if the item has a stop link, call it and reset the item.
  // When the server responds, clear activeStopping.
  // Otherwise, no need to make a change to the queue.
  useEffect(() => {
    if (activeStopping) {
      // When offline, there's no point in making the request: change only local state.
      if (offline) {
        resetInQueue(activeStopping);
        setActiveStopping(null);
        return;
      }
      updateMigration({
        link: activeStopLink,
        callback: () => {
          resetInQueue(activeStopping);
          setActiveStopping(null);
        },
        onError: () => {
          console.error(`Error stopping migration ${activeStopping}`);
          setActiveStopping(null);
        },
      });
    }
  }, [activeStopping]);

  useEffect(() => {
    if (!isQueueRunning) {
      if (activeStopLink) {
        const { qid } = active;
        // This qid is set so that the stop link will not be called again before
        // the server responds.
        setActiveStopping(qid);
      }
    }
  }, [isQueueRunning, activeStopHref]);

  // STOP: if the item has a stop link, call it then clear all, else clear all.
  useEffect(() => {
    if (isQueueStopped) {
      if (activeStopLink) {
        updateMigration({
          link: activeStopLink,
          callback: clearQueue,
        });
      } else {
        clearQueue();
      }
    }
  }, [isQueueStopped]);

  return (
    <MigrationContext.Provider
      value={{
        lists,
        links,
        controllingSession,
        initialImportLink,
        setInitialImporting,
        isInitialImporting,
        isRefreshing,
        migrations,
        limiters,
        updateLimiter,
        modal,
        updateMigration,
        removeFromList,
        resetList,
        active,
        activeStopLink,
        queue,
        addToQueue,
        removeFromQueue,
        isQueueRunning,
        setQueueRunning,
        setQueueClear,
        isQueueStopped,
        isQueueCompleted,
        isActivePending,
        safeToCloseApp,
        basepath,
        basepathModule,
        basepathPreselect,
        isLoading,
        bulkUpdateMigrations,
      }}
    >
      {children}
    </MigrationContext.Provider>
  );
};

export { MigrationContext, MigrationProvider };

MigrationProvider.propTypes = {
  basepath: PropTypes.string.isRequired,
  basepathModule: PropTypes.string.isRequired,
  basepathPreselect: PropTypes.string.isRequired,
  source: PropTypes.string.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};
