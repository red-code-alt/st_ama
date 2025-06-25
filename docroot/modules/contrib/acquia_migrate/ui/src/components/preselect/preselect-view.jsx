import React, { useEffect, useState, useContext } from 'react';

import Selector from '../selector';
import usePreselections from '../../hooks/use-preselections';
import { PreselectContext } from '../../contexts/preselect';
import {
  getMigrationById,
  hasUnskipLink,
  dependencyStatus,
  deepDependencyList,
  sumMigrationsCount,
  listMigrationsUnderlying,
} from '../../lib/utils';

/**
 * Display list of migrations.
 *
 * @return {ReactNode}
 *   <PreselectView />
 */
const PreselectView = () => {
  const [pending, setPending] = useState(false);
  const [required, setRequired] = useState(new Set());
  const [submitting, setSubmitting] = useState(new Set());
  const { migrations, bulkUpdateMigrations, refreshResource } =
    useContext(PreselectContext);
  const { checked, selections, selectAll, selectSingle } =
    usePreselections(migrations);

  const [skippedList, selectedList] = migrations.reduce(
    (lists, migration) => {
      lists[+selections.includes(migration.id)].push(migration);
      return lists;
    },
    [[], []],
  );

  const completion = [
    {
      label: 'Selected',
      count: selections.length,
      modifier: 'primary',
      description: (
        <span>
          <strong>Selected</strong> refers to migrations that you plan to run.
        </span>
      ),
    },
    {
      label: 'Required',
      // Not counting items selected by user.
      count: [...required].filter((id) => !selections.includes(id)).length,
      modifier: 'secondary',
      description: (
        <span>
          <strong>Required</strong> are dependencies of selected migrations.
        </span>
      ),
    },
    {
      label: 'Skipped',
      count: skippedList.length,
      modifier: 'warning',
      description: (
        <span className="migration__import-status-desc migration__warning">
          Migrations with <strong>0</strong> items are <strong>skipped</strong>{' '}
          by default, but you can choose to skip any others that you do not wish
          to import now.
        </span>
      ),
    },
  ];

  const handleSubmit = (e) => {
    e.preventDefault();
    setPending(true);

    const submitTime = Date.now();

    // If not in selections, mark skipped.
    bulkUpdateMigrations(
      migrations.map(({ id }) => ({
        type: 'migration',
        id,
        attributes: {
          skipped: !submitting.has(id),
        },
      })),
    ).then((response) => {
      refreshResource();
    });
  };

  useEffect(() => {
    // Get a flat list of the deep dependencies for all current selections.
    setRequired(
      selections.reduce((list, selection) => {
        deepDependencyList(
          getMigrationById(selection, migrations),
          migrations,
        ).forEach((id) => list.add(id));
        return list;
      }, new Set()),
    );
  }, [selections]);

  useEffect(() => {
    setSubmitting(new Set([...required, ...selections]));
  }, [selections, required]);

  return (
    <form className="preselect__list">
      <div>
        <table>
          <thead>
            <tr>
              <th>
                <Selector
                  id="allMigrations"
                  options={{
                    checked,
                    disabled: false,
                  }}
                  toggle={selectAll}
                />
              </th>
              <th>Name</th>
              <th style={{ 'min-width': '6rem' }}>Number of Items</th>
              <th>Dependencies</th>
            </tr>
          </thead>
          <tbody>
            {migrations.map((migration) => (
              <tr key={migration.id}>
                <td>
                  <Selector
                    id={migration.id}
                    options={{
                      checked:
                        selections.includes(migration.id) ||
                        required.has(migration.id),
                      disabled:
                        hasUnskipLink(migration.links) ||
                        required.has(migration.id),
                    }}
                    toggle={() => {
                      selectSingle(migration.id);
                    }}
                  />
                </td>
                <td>{migration.label}</td>
                <td className="col--align">
                  {migration.totalCount === 0 ||
                  !selections.includes(migration.id) ? (
                    <span
                      className="migration__import-status migration__warning"
                      title={`${
                        migration.totalCount === 0
                          ? 'Migrations with 0 items are skipped by default'
                          : `${migration.totalCount} migrations will be skipped.`
                      }`}
                    >
                      {migration.totalCount}
                    </span>
                  ) : (
                    <span>{migration.totalCount}</span>
                  )}
                </td>
                <td>
                  {dependencyStatus(migration, migrations)
                    .map(({ label }) => label)
                    .join(', ')}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="preselect__start">
        <button
          type="submit"
          disabled={pending || submitting.size === 0}
          className="button button--primary"
          onClick={handleSubmit}
        >
          Start Migration
        </button>
        {submitting.size > 0 ? (
          <strong>({submitting.size}) Selected items</strong>
        ) : (
          <span>No migrations selected</span>
        )}
      </div>
      <div className="panel panel--info">
        <div className="panel__content">
          <div className="preselection_summary">
            {completion
              .filter(({ count }) => count > 0)
              .map((progress) => (
                <div key={`${progress.label}--count`}>
                  <span className="preselection_summary__count">
                    {progress.count}
                  </span>
                  <span className="preselection_summary__label">
                    {progress.label}
                  </span>
                </div>
              ))}
          </div>
          {completion
            .filter(({ count }) => count > 0)
            .map((progress) => (
              <p key={`${progress.label}--desc`}>{progress.description}</p>
            ))}
        </div>
      </div>
    </form>
  );
};

export default PreselectView;
