import { useEffect, useState } from 'react';

import Resource from '../lib/resource/resource';

const useResourceGet = ({ href, handleError }) => {
  const [state, setState] = useState({
    isLoading: false,
    document: null,
  });
  const resource = new Resource({ href, handleError });

  const refreshResource = () => {
    setState({ ...state, isLoading: true });
    resource.get().then((document) => {
      setState({ isLoading: false, document });
    });
  };

  useEffect(() => {
    refreshResource();
  }, []);

  return [state, refreshResource];
};

export default useResourceGet;
