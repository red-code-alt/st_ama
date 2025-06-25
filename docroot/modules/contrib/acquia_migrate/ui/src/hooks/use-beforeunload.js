import { useEffect } from 'react';

const useBeforeUnload = (safeToClose, callback = () => {}) => {
  useEffect(() => {
    const unload = (event) => {
      if (!safeToClose) {
        event.preventDefault();
        callback();
        event.returnValue =
          'https://developer.mozilla.org/en-US/docs/Web/API/Window/beforeunload_event#browser_compatibility';
        return 'https://developer.mozilla.org/en-US/docs/Web/API/Window/beforeunload_event#browser_compatibility';
      }
    };

    window.addEventListener('beforeunload', unload);

    return () => {
      window.removeEventListener('beforeunload', unload);
    };
  }, [safeToClose]);
};

export default useBeforeUnload;
