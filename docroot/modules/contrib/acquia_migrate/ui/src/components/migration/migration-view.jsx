import React, { useContext, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';

import MigrationRow from './migration-row';
import Selector from '../selector';
import MigrationActions from './migration-actions';
import MigrationOverview from './migration-overview';
import LoadingPending from '../loading-pending';
import useSelections from '../../hooks/use-selections';
import { MigrationContext } from '../../contexts/migrations';
import { FocuserProvider } from '../../contexts/focuser';
import { DisplayProvider } from '../../contexts/display';
import { getMigrationById, listHasNewRows } from '../../lib/utils';
import useOffline from '../../hooks/use-offline';

/**
 * Display list of migrations.
 *
 * @param {string} name
 *   The list name
 * @param {array} list
 *   The list of parsed migrations.
 * @return {ReactNode}
 *   <MigrationView name={name} list={list}
 */
const MigrationView = ({ name, list }) => {
  const { columns, currentRows, allowedOps } = list;
  const {
    migrations,
    resetList,
    limiters: { searchTitle },
    isInitialImporting,
    isLoading,
  } = useContext(MigrationContext);
  const hasNew = listHasNewRows(list);
  const offline = useOffline();
  const isLocked = isInitialImporting || offline;
  const isLoaded = !!currentRows.length;
  const currentMigrations = currentRows
    .filter((listItem) => listItem.status !== 'removed')
    .filter((listItem) => searchTitle.test(listItem))
    .map((listItem) => getMigrationById(listItem.id, migrations));

  const viewRef = useRef(null);

  const {
    options,
    operation,
    setOperation,
    selections,
    checked,
    selectSingle,
    selectAll,
  } = useSelections(currentMigrations, allowedOps);

  const toggle = () => {
    selectAll(!checked);
  };

  useEffect(() => {
    if (hasNew) {
      setTimeout(() => {
        resetList(name);
      }, 1500);
    }
  }, [hasNew]);

  return (
    <div className="migration_view">
      <MigrationOverview name={name} />
      {!isLoaded ? (
        <LoadingPending pending={isLoading} empty="No migrations available." />
      ) : (
        <FocuserProvider parentRef={viewRef}>
          <div className="sticky-outer">
            {columns.includes('actions') && (
              <MigrationActions
                migrations={currentMigrations}
                options={options}
                operation={operation}
                setOperation={setOperation}
                selections={selections}
                selectAll={selectAll}
              />
            )}
            <div className="sticky-enabled" ref={viewRef}>
              <table className="migrations__list">
                <thead>
                  <tr>
                    {columns.includes('actions') && (
                      <th>
                        <Selector
                          id="allMigrations"
                          options={{
                            checked,
                            disabled: isLocked,
                          }}
                          toggle={toggle}
                        />
                      </th>
                    )}
                    {columns.includes('name') && <th>Name</th>}
                    {columns.includes('status') && <th>Status</th>}
                    {columns.includes('imported') && (
                      <th className="col--align">Imported/Total</th>
                    )}
                    {columns.includes('messages') && (
                      <th className="col--align">Messages</th>
                    )}
                    {columns.includes('lastImported') && <th>Last Imported</th>}
                    {columns.includes('ops') && (
                      <th className="col--align">Operations</th>
                    )}
                  </tr>
                </thead>
                <tbody>
                  <DisplayProvider>
                    {currentRows
                      .filter((row) => searchTitle.test(row))
                      .map((data) => (
                        <MigrationRow
                          key={data.key}
                          data={data}
                          columns={columns}
                          operation={operation}
                          selections={selections}
                          selectSingle={selectSingle}
                        />
                      ))}
                  </DisplayProvider>
                </tbody>
              </table>
            </div>
          </div>
        </FocuserProvider>
      )}
    </div>
  );
};

export default MigrationView;

MigrationView.propTypes = {
  name: PropTypes.string.isRequired,
  list: PropTypes.shape({
    title: PropTypes.string,
    initialized: PropTypes.bool,
    activeCount: PropTypes.number,
    columns: PropTypes.arrayOf(PropTypes.string),
    currentRows: PropTypes.arrayOf(PropTypes.object),
    filter: PropTypes.func,
    sort: PropTypes.func,
    path: PropTypes.string,
    to: PropTypes.string,
    allowedOps: PropTypes.arrayOf(PropTypes.string),
  }).isRequired,
};
