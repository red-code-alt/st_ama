import { useState, useEffect } from 'react';

import { request } from '../lib/api';
import { getStaleDataLinks } from '../lib/utils';

const useStaleData = (links) => {
  const [staleDataLink, setStaleDataLink] = useState(null);
  const [isRefreshing, setIsRefreshing] = useState(false);

  useEffect(() => {
    if (staleDataLink) {
      setIsRefreshing(true);
      // The response from stale-data is not currently needed.
      request({ href: staleDataLink.href }).then(() => {
        setIsRefreshing(false);
      });
    }
  }, [staleDataLink]);

  useEffect(() => {
    if (links) {
      const staleDataLinks = getStaleDataLinks(links);
      setStaleDataLink(staleDataLinks.length ? staleDataLinks[0] : null);
    }
  }, [links]);

  return { isRefreshing };
};

export default useStaleData;
