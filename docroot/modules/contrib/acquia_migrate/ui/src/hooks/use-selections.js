import { useState, useEffect, useReducer } from 'react';

import { getOperations, getSelectableMigrations } from '../lib/utils';

const selectionReducer = (state, action) => {
  switch (action.type) {
    case 'selectSingle':
      // If value is the same as whether state includes id, do nothing.
      if (state.includes(action.id) === action.value) {
        return state;
      }

      // Return a new list with the id, or return a filtered list without it.
      return action.value
        ? [...state, action.id]
        : state.filter((i) => i !== action.id);

    case 'selectAll':
      return action.value
        ? getSelectableMigrations(action.migrations, action.operation).map(
            (migration) => migration.id,
          )
        : [];

    case 'selectAvailable': {
      const available = getSelectableMigrations(
        action.migrations,
        action.operation,
      ).map((migration) => migration.id);

      return state.filter((id) => available.includes(id));
    }

    default:
      return state;
  }
};

const useSelections = (migrations, allowed) => {
  const migrationOperations = getOperations(migrations, allowed);
  const opsHash = Array.from(migrationOperations.keys()).sort().join('-');
  const [options, setOptions] = useState([]);
  const [operation, setOperation] = useState('');
  const [selections, dispatchSelections] = useReducer(selectionReducer, []);

  useEffect(() => {
    if (selections.length) {
      dispatchSelections({
        type: 'selectAvailable',
        operation,
        migrations,
      });
    }
  }, [operation]);

  useEffect(() => {
    if (opsHash !== '') {
      setOptions(Array.from(migrationOperations));
      setOperation(
        migrationOperations.has('import')
          ? 'import'
          : migrationOperations.keys().next().value,
      );
    }
  }, [opsHash]);

  const checked =
    selections.length === getSelectableMigrations(migrations, operation).length;

  const selectSingle = (selection) => {
    Object.entries(selection).forEach(([id, value]) => {
      dispatchSelections({ type: 'selectSingle', id, value });
    });
  };

  const selectAll = (value) => {
    dispatchSelections({ type: 'selectAll', value, operation, migrations });
  };

  return {
    operation,
    setOperation,
    options,
    selections,
    checked,
    selectSingle,
    selectAll,
  };
};

export default useSelections;
