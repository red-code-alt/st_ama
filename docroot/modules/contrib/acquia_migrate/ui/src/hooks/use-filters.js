import { useContext, useEffect, useReducer } from 'react';

import {
  formatURL,
  parseQueryValue,
  getQuery,
  parseQuery,
  parseFilters,
  stringifyQueryObj,
} from '../lib/uri';
import { LocationContext } from '../contexts/location';

/**
 * Convert field objects into string for equality checks.
 *
 * @param {array} fields
 * @return {string}
 */
const hashifyFieldStrings = (fields) => stringifyQueryObj(fields).join('-');

/**
 * Clear the filter field values or change to the updated value.
 *
 * @param {array} fields
 *   List of fields, @see parseFilters.
 * @param {array} updates
 *   Optional values to set on the fields.
 *
 * @return {array}
 *   The updated fields list.
 */
const overwriteFilterFields = (fields, updates = []) =>
  fields.map((filter) => {
    const change = updates.find((update) => update.field === filter.field);
    return { ...filter, value: change ? change.value : '' };
  });

const filterReducer = (state, action) => {
  switch (action.type) {
    case 'init':
      return { ...action.filters };

    case 'navigate':
      return {
        ...state,
        fields: overwriteFilterFields(
          state.fields,
          parseQueryValue(action.params),
        ),
      };

    case 'filterChange':
      return {
        ...state,
        fields: state.fields.map((filterField) =>
          filterField.field === action.filter.field
            ? { ...filterField, ...action.filter }
            : filterField,
        ),
      };

    case 'filterClear':
      return {
        ...state,
        fields: overwriteFilterFields(state.fields),
      };

    default:
      return state;
  }
};

const useFilters = () => {
  const { windowURL, windowPath, setWindowURL } = useContext(LocationContext);
  const [filters, dispatchFilterData] = useReducer(filterReducer, {});

  const fieldHash = hashifyFieldStrings(filters.fields || []);
  const windowHash = hashifyFieldStrings(
    parseQueryValue(parseQuery(windowURL)),
  );

  const setFields = (queryLink) => {
    dispatchFilterData({
      type: 'init',
      filters: parseFilters(queryLink, parseQueryValue(parseQuery(windowURL))),
    });
  };

  const updateFilter = (filter) => {
    dispatchFilterData({ type: 'filterChange', filter });
  };

  const submitFilters = () => {
    const values = stringifyQueryObj(filters.fields);
    const href = formatURL({ filter: values }, filters.template);
    setWindowURL(getQuery(href));
  };

  const clearFilters = () => {
    setWindowURL(windowPath);
  };

  useEffect(() => {
    if (filters.fields) {
      dispatchFilterData({ type: 'navigate', params: parseQuery(windowURL) });
    }
  }, [windowURL]);

  useEffect(() => {
    // Avoid extra submits.
    // Check for filter.fields to not submit before init.
    if (filters.fields && fieldHash !== windowHash) {
      submitFilters();
    }
  }, [fieldHash]);

  return {
    filters,
    setFields,
    updateFilter,
    submitFilters,
    clearFilters,
  };
};

export default useFilters;
