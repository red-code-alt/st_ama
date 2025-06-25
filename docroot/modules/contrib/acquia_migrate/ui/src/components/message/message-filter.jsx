import React from 'react';
import PropTypes from 'prop-types';

import ClaroSelect from '../claro/select';
import ClaroTextInput from '../claro/text-input';

const MessageFilter = ({ field, update }) => {
  const { field: name, label, operator, value, options } = field;
  const handleChange = (e) => {
    update({
      field: name,
      operator,
      value: e.target.value,
    });
  };
  return (
    <>
      {options ? (
        <ClaroSelect
          label={label}
          name={name}
          value={value}
          options={options}
          onChange={handleChange}
        />
      ) : (
        <ClaroTextInput
          label={label}
          name={name}
          value={value}
          onChange={handleChange}
        />
      )}
    </>
  );
};

export default MessageFilter;

MessageFilter.propTypes = {
  field: PropTypes.shape({
    cardinality: PropTypes.number.isRequired,
    field: PropTypes.string.isRequired,
    label: PropTypes.string.isRequired,
    operator: PropTypes.string.isRequired,
    value: PropTypes.string,
    options: PropTypes.arrayOf(
      PropTypes.shape({
        label: PropTypes.string,
        value: PropTypes.string,
      }),
    ),
  }).isRequired,
  update: PropTypes.func.isRequired,
};
