import { useEffect, useState } from 'react';

import { hasInitialImportLink } from '../lib/utils';

const useInitialImport = (links) => {
  const [initialImportLink, setInitialImportLink] = useState(null);
  const [isInitialImporting, setInitialImporting] = useState(false);

  useEffect(() => {
    if (links) {
      setInitialImportLink(
        hasInitialImportLink(links) && !isInitialImporting
          ? links['initial-import']
          : null,
      );
    }
  }, [links]);

  return { initialImportLink, setInitialImporting, isInitialImporting };
};

export default useInitialImport;
