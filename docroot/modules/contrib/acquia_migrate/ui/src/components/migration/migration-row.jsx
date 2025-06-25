import React, { useContext, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';

import DropMenu from '../dropmenu';
import MigrationLink from './migration-link';
import MigrationStatus from './migration-status';
import MigrationImported from './migration-imported';
import MigrationImportStatus from './migration-import-status';
import MigrationProgressBar from './migration-progress-bar';
import AnimatedNumber from '../animated-number';
import Icon from '../icon';
import ExtLink from '../ext-link';
import Selector from '../selector';
import { MigrationContext } from '../../contexts/migrations';
import { FocuserContext } from '../../contexts/focuser';
import useOffline from '../../hooks/use-offline';
import useToggle from '../../hooks/use-toggle';
import {
  hasBatchLink,
  getUpdateLinks,
  dependencyStatus,
  getMigrationPage,
  getMigrationById,
  getQueueItemById,
  getPreview,
} from '../../lib/utils';
import { DisplayContext } from '../../contexts/display';

const MigrationCol = ({ className, children }) => (
  <td className={className}>
    <div>{children}</div>
  </td>
);

/**
 * Single migration row, this will display the attributes and current status.
 *
 * @todo refactor as a datagrid, move actions out of the DropButton.
 *
 * @param {object} data
 *   The migration row data.
 * @param {array} columns
 *   The columns this row should display.
 * @param {string} operation
 *   The currently selected operation.
 * @param {array} selections
 *   The currently selected migration rows.
 * @param {function} selectSingle
 *   Toggle a migration row selected @see useSelections selectSingle
 */
const MigrationRow = ({
  data,
  columns,
  operation,
  selections,
  selectSingle,
}) => {
  const { id, page, status: migrationStatus } = data;
  const {
    migrations,
    addToQueue,
    updateMigration,
    removeFromList,
    isInitialImporting,
    active,
    queue,
    controllingSession,
  } = useContext(MigrationContext);
  const { scrollParent, isChildFocused } = useContext(FocuserContext);
  const { checkOperationsToggle, toggleOperations } =
    useContext(DisplayContext);
  const [pending, setPending] = useToggle(false);
  const rowRef = useRef(null);
  const offline = useOffline();

  const migration = getMigrationById(id, migrations);
  const queueItem = getQueueItemById(id, queue);

  const {
    label,
    status,
    activity,
    importedCount,
    totalCount,
    lastImported,
    links,
  } = migration;

  // Checking if there are any previewable links.
  const { previewable } = getPreview(links);
  const dependencies = dependencyStatus(migration, migrations, 'import');
  const hasImported = links.hasOwnProperty('rollback');

  const isOpsOpen = checkOperationsToggle(id);

  /**
   * If in queue, display icon instead of selector.
   */
  const isActive = active && active.id === id;
  const isQueued = !!queueItem;
  const isNew = migrationStatus === 'new';
  const isRemoved = migrationStatus === 'removed';
  const isFocused = isChildFocused(id);

  /**
   * Determine selector options from current operation.
   */
  const hasLink = links.hasOwnProperty(operation);
  const isLocked = isInitialImporting || offline;
  const options = {
    checked: hasLink ? selections.includes(id) : false,
    disabled: isLocked || !hasLink || isRemoved,
  };

  const toggle = () => {
    selectSingle({ [id]: !options.checked });
  };

  useEffect(() => {
    if (isRemoved) {
      setTimeout(() => {
        removeFromList(id, page, queueItem);
      }, 1500);
    }
  }, [isRemoved]);

  // Deselect on unmount.
  useEffect(() => {
    if (isFocused && rowRef.current) {
      scrollParent(rowRef.current.offsetTop - rowRef.current.offsetHeight);
    }
    return () => {
      selectSingle({ [id]: false });
    };
  }, [isFocused]);

  /**
   * Filter list of links in migration to known rels.
   * Exclude "stop" link.
   */
  const { stop, ...migrationLinks } = links;
  const buttons = getUpdateLinks(migrationLinks).map((link) => {
    const { title, rel } = link;
    return {
      key: `${id}-${title}`,
      title,
      rel,
      action: () => {
        updateMigration({
          link,
          toggle: setPending,
          callback: () => {
            // @todo Figure out why `toggle: setPending` does not toggle back to `pending=false`, remove the callback that reloads the page.
            if (title === 'Reset migration') {
              location.reload();
            }
          },
        });
      },
    };
  });

  /**
   * Include "rollback-and-import" link if available.
   * This operation must wait until the queue is empty.
   */
  if (hasBatchLink(migrationLinks, 'rollback-and-import') && !queue.length) {
    const rbiLink = migrationLinks['rollback-and-import'];
    buttons.push({
      key: `${id}-${rbiLink.title}`,
      title: rbiLink.title,
      rel: rbiLink.rel,
      action: () => {
        addToQueue({
          id,
          title: label,
          start: { href: rbiLink.href },
          operation: 'rollback-and-import',
        });
      },
    });
  }

  // Close dropdown if open and no buttons
  if (isOpsOpen && !buttons.length) {
    toggleOperations(id);
  }

  const messagesLink = links['migration-messages'];

  return (
    <tr
      id={id}
      ref={rowRef}
      className={`migration table_row ${
        isRemoved
          ? `migration__removed migration__removed--${getMigrationPage(
              migration,
            )}`
          : ''
      }
      ${isNew ? `migration__new` : ''}
      ${isFocused ? 'migration__focused' : ''}`}
    >
      {columns.includes('actions') && (
        <MigrationCol className="migration__actions">
          {isQueued ? (
            <Icon size="16" icon={isActive ? 'zap' : 'clock'} />
          ) : (
            <Selector id={id} options={options} toggle={toggle} />
          )}
        </MigrationCol>
      )}
      {columns.includes('name') && (
        <MigrationCol>
          {queueItem ? (
            <MigrationProgressBar
              item={queueItem}
              label={label}
              imported={lastImported}
            />
          ) : (
            <MigrationLink id={id} previewable={previewable}>
              {label}
            </MigrationLink>
          )}
        </MigrationCol>
      )}
      {columns.includes('status') && (
        <MigrationCol className="migration_row__status">
          <MigrationStatus
            id={id}
            activity={activity}
            dependencies={dependencies}
            hasImported={hasImported}
            showDependencies={
              (!hasLink &&
                (importedCount === 0 || controllingSession !== true)) ||
              operation === 'import' ||
              operation === 'refresh'
            }
            link={hasLink ? links[operation] : null}
          />
        </MigrationCol>
      )}
      {columns.includes('imported') && (
        <MigrationCol className="migration_row__imported_count col--align">
          <MigrationImportStatus
            status={status}
            importedCount={importedCount}
            totalCount={totalCount}
          />
        </MigrationCol>
      )}
      {columns.includes('messages') && (
        <MigrationCol className="migration_row__message_count col--align">
          {messagesLink ? (
            <ExtLink href={messagesLink.href} title={`View ${label} messages`}>
              <AnimatedNumber value={messagesLink.title} />
            </ExtLink>
          ) : (
            <span className="tabular">0</span>
          )}
        </MigrationCol>
      )}
      {columns.includes('lastImported') && (
        <MigrationCol className="migration_row__last_imported">
          <MigrationImported imported={lastImported} />
        </MigrationCol>
      )}
      {columns.includes('ops') && (
        <MigrationCol className="migration_row__operations col--align">
          <DropMenu
            buttons={buttons}
            pending={pending}
            isOpen={isOpsOpen}
            toggleOpen={() => toggleOperations(id)}
          />
        </MigrationCol>
      )}
    </tr>
  );
};

export default MigrationRow;

MigrationCol.propTypes = {
  className: PropTypes.string,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.element),
    PropTypes.element,
  ]).isRequired,
};

MigrationCol.defaultProps = {
  className: '',
};

MigrationRow.propTypes = {
  data: PropTypes.shape({
    id: PropTypes.string,
    index: PropTypes.number,
    lastImported: PropTypes.shape({
      startTime: PropTypes.string,
      endTime: PropTypes.string,
      duration: PropTypes.number,
    }),
    page: PropTypes.string,
    status: PropTypes.string,
  }).isRequired,
  columns: PropTypes.arrayOf(PropTypes.string).isRequired,
  operation: PropTypes.string.isRequired,
  selections: PropTypes.arrayOf(PropTypes.string),
  selectSingle: PropTypes.func.isRequired,
};

MigrationRow.defaultProps = {
  selections: [],
};
