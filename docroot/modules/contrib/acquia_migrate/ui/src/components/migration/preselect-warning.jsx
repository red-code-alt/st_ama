import React, { useContext } from 'react';

import ExtLink from '../ext-link';
import { MigrationContext } from '../../contexts/migrations';

const PreselectWarning = () => {
  const { basepathPreselect } = useContext(MigrationContext);

  return (
    <div>
      <h2>Migration data not yet chosen.</h2>
      <p>
        <ExtLink href={basepathPreselect} title="Select data to migrate">
          <span>Choose which data to import from your source site</span>
        </ExtLink>
        <span>
          , then you will be guided back to this dashboard to start migrations!
        </span>
      </p>
    </div>
  );
};

export default PreselectWarning;
