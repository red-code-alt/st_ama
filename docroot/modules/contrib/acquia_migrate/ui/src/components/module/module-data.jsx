import React from 'react';
import PropTypes from 'prop-types';
import ModuleRow from './module-row';

const compareModuleData = (prev, next) => {
  const prevHash = prev.modules.map((row) => row.id).join();
  const nextHash = next.modules.map((row) => row.id).join();

  return prevHash === nextHash;
};

/**
 * The loaded list of modules.
 *
 * @param {string} title
 * @param {array} modules
 *   List returned from MigrationContext @see parseModules.
 * @param {array} columns
 *   The columns this datagrid should display.
 * @param {string} coreVersion
 *   The Drupal 7 version.
 * @param {string} destVersion
 *   The Drupal 9 version.
 * @return {ReactNode}
 *   <ModuleData title={title} modules={modules} />
 */
const ModuleData = ({
  title,
  modules,
  columns,
  sourceVersion,
  destVersion,
}) => (
  <div className="module-recommendations datagrid">
    {title ? <h4>{title}</h4> : null}
    <div
      className="module-recommendations__header datagrid__header"
      role="presentation"
    >
      {columns.includes('source') ? (
        <div className="datagrid__header-item datagrid__item">
          Drupal {sourceVersion}
        </div>
      ) : null}
      {columns.includes('destination') ? (
        <div className="datagrid__header-item datagrid__item">
          Drupal {destVersion}
        </div>
      ) : null}
      {columns.includes('status') ? (
        <div className="datagrid__header-item datagrid__item">Module state</div>
      ) : null}
    </div>
    {modules.map((module) => (
      <ModuleRow key={module.id} module={module} columns={columns} />
    ))}
  </div>
);

export default React.memo(ModuleData, compareModuleData);

ModuleData.propTypes = {
  title: PropTypes.string,
  modules: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.string,
      title: PropTypes.string,
      version: PropTypes.string,
    }),
  ).isRequired,
  columns: PropTypes.arrayOf(PropTypes.string).isRequired,
  sourceVersion: PropTypes.string,
  destVersion: PropTypes.string,
};

ModuleData.defaultProps = {
  title: null,
  sourceVersion: '7',
  destVersion: '9',
};
