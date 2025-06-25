import React from 'react';
import PropTypes from 'prop-types';

import { parseUnknownValue } from '../../lib/utils';

const RawValue = ({ value }) =>
  parseUnknownValue(value)
    .split('; ')
    .map((val, index) => <code key={index}>{val}</code>);

const RawRow = ({ row }) => (
  <tr>
    <td>
      <code>{row.sourceFieldName}</code> →{' '}
      <code>{row.destinationFieldName}</code>
    </td>
    <td>
      <RawValue value={row.sourceValueSimplified} />
    </td>
    <td>
      <RawValue value={row.destinationValueSimplified} />
    </td>
  </tr>
);

const PreviewRaw = ({ list }) => (
  <table>
    <thead>
      <tr>
        <th>Mapping</th>
        <th>Source</th>
        <th>Destination</th>
      </tr>
    </thead>
    <tbody>
      {list.map((item) => (
        <RawRow
          key={`${item.sourceFieldName}-${item.destinationFieldName}`}
          row={item}
        />
      ))}
    </tbody>
  </table>
);

export default PreviewRaw;

RawValue.propTypes = {
  value: PropTypes.oneOfType([PropTypes.array, PropTypes.string]),
};

RawValue.defaultProps = {
  value: '',
};

RawRow.propTypes = {
  row: PropTypes.shape({
    sourceFieldName: PropTypes.string,
    sourceValue: PropTypes.any,
    sourceValueSimplified: PropTypes.any,
    destinationFieldName: PropTypes.string,
    destinationValue: PropTypes.any,
    destinationValueSimplified: PropTypes.any,
  }).isRequired,
};

PreviewRaw.propTypes = {
  list: PropTypes.arrayOf(
    PropTypes.shape({
      sourceFieldName: PropTypes.string,
      sourceValue: PropTypes.any,
      sourceValueSimplified: PropTypes.any,
      destinationFieldName: PropTypes.string,
      destinationValue: PropTypes.any,
      destinationValueSimplified: PropTypes.any,
    }),
  ),
};

PreviewRaw.defaultProps = {
  list: [],
};
