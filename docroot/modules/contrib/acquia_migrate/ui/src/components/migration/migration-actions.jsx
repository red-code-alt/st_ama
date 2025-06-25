import React, { useContext } from 'react';
import PropTypes from 'prop-types';

import { MigrationContext } from '../../contexts/migrations';
import { getMigrationById } from '../../lib/utils';
import ClaroTextInput from '../claro/text-input';
import useOffline from '../../hooks/use-offline';

const MigrationActions = ({
  migrations,
  options,
  operation,
  setOperation,
  selections,
  selectAll,
}) => {
  const offline = useOffline();
  const {
    addToQueue,
    limiters: { searchTitle },
    updateLimiter,
  } = useContext(MigrationContext);
  const areSelected = selections.filter((value) => value);
  const handleChange = (e) => {
    setOperation(e.target.value);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    areSelected.forEach((id) => {
      const { label, links } = getMigrationById(id, migrations);
      const { href } = links[operation];
      addToQueue({ id, title: label, start: { href }, operation });
    });
    selectAll(false);
  };

  return (
    <div className="migration__operations">
      <form onSubmit={handleSubmit} className="migrate__exposed-form">
        <div className="migrate__exposed-form__item">
          <select
            name="migration__operations_selector"
            id="migration__operations_selector"
            className="form-element form-element--type-select"
            onChange={handleChange}
            value={operation}
            disabled={offline}
          >
            {options.map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
        </div>
        <div className="migrate__exposed-form__item migrate__exposed-form__item--actions">
          <button
            type="submit"
            disabled={operation === '' || !areSelected.length}
            className="button button--primary form-submit"
          >
            {areSelected.length
              ? `Apply to (${areSelected.length}) selected`
              : 'No migrations selected'}
          </button>
        </div>
        <ClaroTextInput
          name={searchTitle.name}
          type="search"
          value={searchTitle.value}
          size="30"
          placeholder="Filter by migration name"
          onChange={(e) => {
            updateLimiter(searchTitle.name, e.target.value);
          }}
        />
      </form>
    </div>
  );
};

export default MigrationActions;

MigrationActions.propTypes = {
  migrations: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.string,
      index: PropTypes.number,
      label: PropTypes.string,
      consistsOf: PropTypes.arrayOf(PropTypes.object),
      dependencies: PropTypes.arrayOf(PropTypes.object),
      lastImported: PropTypes.object,
      links: PropTypes.object,
      importedCount: PropTypes.number,
      processedCount: PropTypes.number,
      totalCount: PropTypes.number,
      skipped: PropTypes.bool,
      completed: PropTypes.bool,
      status: PropTypes.object,
    }),
  ),
  options: PropTypes.arrayOf(PropTypes.array),
  operation: PropTypes.string,
  setOperation: PropTypes.func.isRequired,
  selections: PropTypes.arrayOf(PropTypes.string),
  selectAll: PropTypes.func.isRequired,
};

MigrationActions.defaultProps = {
  migrations: [],
  operation: '',
  options: [],
  selections: [],
};
