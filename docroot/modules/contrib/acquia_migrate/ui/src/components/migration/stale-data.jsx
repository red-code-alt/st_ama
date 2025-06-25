import React, { useContext } from 'react';

import ClaroThrobber from '../claro/throbber';
import { MigrationContext } from '../../contexts/migrations';

const StaleData = () => {
  const { isRefreshing } = useContext(MigrationContext);
  return (
    <div id="stale-data">
      {isRefreshing ? <ClaroThrobber message="Checking for updates…" /> : null}
    </div>
  );
};

export default StaleData;
