import { useReducer } from 'react';

import { hasSkipLink } from '../lib/utils';

const selectionReducer = (state, action) => {
  switch (action.type) {
    case 'selectSingle':
      // Return a new array without the id or a new array including it.
      return state.includes(action.id)
        ? state.filter((selection) => selection !== action.id)
        : [...state, action.id];

    case 'selectAll':
      // If all selected, select none, otherwise select all.
      if (state.length === action.list.length) {
        return [];
      }
      return action.list;

    default:
      return state;
  }
};
const usePreselections = (migrations) => {
  const list = migrations
    .filter(({ links }) => hasSkipLink(links))
    .map(({ id }) => id);
  const [selections, dispatchSelections] = useReducer(selectionReducer, list);

  // Select all checkbox is only active if all selected.
  const checked = selections.length === list.length;

  const selectSingle = (id) => {
    dispatchSelections({ type: 'selectSingle', id });
  };
  const selectAll = () => {
    dispatchSelections({
      type: 'selectAll',
      list,
    });
  };

  return { checked, selections, selectAll, selectSingle };
};

export default usePreselections;
