import { useEffect, useState } from 'react';

import Resource from '../lib/resource/resource';
import CollectionResource from '../lib/resource/collection-resource';

function createHook(ResourceType) {
  return ({
    handleError,
    href = null,
    uriTemplate = null,
    uriTemplateParams = {},
  }) => {
    const [state, setState] = useState({
      isLoading: true,
      document: null,
    });
    const paramsHash = JSON.stringify(uriTemplateParams);

    useEffect(() => {
      setState({ ...state, isLoading: true });
      const resource = new ResourceType({
        href,
        handleError,
        uriTemplate,
        uriTemplateParams,
      });
      return resource.poll({
        updateFn: (document) => {
          setState({ isLoading: false, document });
        },
      });
    }, [href, handleError, uriTemplate, paramsHash]);

    return state;
  };
}

const useResource = createHook(Resource);
const useCollection = createHook(CollectionResource);

export { useResource, useCollection };
