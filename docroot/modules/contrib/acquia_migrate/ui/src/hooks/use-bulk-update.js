import { useState } from 'react';

import { atomicTransaction } from '../lib/api';

const useBulkUpdate = () => {
  const [link, setLink] = useState(null);

  /**
   * Post the list of update data.
   * @param {array} linkData
   *   op: 'update' will be included with each item in the payload.
   *
   * @return {Promise<*>}
   */
  const bulkUpdateMigrations = (linkData) => {
    const { href, type } = link;
    return atomicTransaction({
      href,
      type,
      payload: linkData.map((data) => ({ op: 'update', data })),
    });
  };

  return { bulkUpdateMigrations, setLink };
};

export default useBulkUpdate;
