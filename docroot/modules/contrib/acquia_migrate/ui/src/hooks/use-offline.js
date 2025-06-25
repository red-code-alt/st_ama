import { useEffect, useState } from 'react';

const useOffline = () => {
  const [offline, setOffline] = useState(!window.navigator.onLine);

  const updateOffline = () => {
    setOffline(!window.navigator.onLine);
  };

  useEffect(() => {
    window.addEventListener('online', updateOffline);
    window.addEventListener('offline', updateOffline);

    return () => {
      window.removeEventListener('online', updateOffline);
      window.removeEventListener('offline', updateOffline);
    };
  }, []);

  return offline;
};

export default useOffline;
