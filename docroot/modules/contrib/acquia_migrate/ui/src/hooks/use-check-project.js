import { useEffect, useReducer } from 'react';

import useLocalStorage from './use-local-storage';

const apiURL = 'https://www.drupal.org/api-d7/node.json';

const initialConfig = {
  status: 'Checking drupal.org project pages...',
  completed: false,
  projects: [],
};

const projectReducer = (state, action) => {
  switch (action.type) {
    case 'reset':
      return { ...initialConfig };

    case 'addAll':
      return {
        ...state,
        completed: true,
        projects: [...action.stored.value],
        status: `Found ${action.stored.value.length} matching projects on drupal.org`,
        timestamp: action.stored.timestamp,
        reset: action.reset,
      };

    default:
      return state;
  }
};

/**
 * Fetch project info from drupal.org api and save results in localStorage.
 *
 * @param {array} modules
 *   The unknown modules list.
 * @return {{projectInfo: React.ReducerStateWithoutAction<function(*, *): ({projects: [], completed: boolean, status: string})>}}
 */
const useCheckProject = (modules) => {
  const { stored, storeValue } = useLocalStorage('unknownSources', []);
  const [projectInfo, dispatchProjectInfo] = useReducer(
    projectReducer,
    initialConfig,
  );

  const checkProjectPage = (id) =>
    fetch(`${apiURL}?field_project_machine_name=${id}`, {
      method: 'GET',
      mode: 'cors',
      headers: {
        Accept: 'application/json',
      },
    }).then((response) => {
      if (response.ok) {
        return response.json().then((json) => {
          // api-d7 always returns 200, but list will be empty if no project.
          if (json.list.length) {
            return id;
          }
        });
      }
    });

  // Resetting the stored value triggers a reset once complete.
  const resetStored = () => {
    storeValue([]);
  };

  // When projects update is completed, store project list.
  useEffect(() => {
    if (projectInfo.completed && !stored.value.length) {
      storeValue(projectInfo.projects);
    }
  }, [projectInfo]);

  // If stored value is updated, replace projects if changed.
  useEffect(() => {
    if (stored.value.length !== projectInfo.projects.length) {
      if (stored.value.length) {
        dispatchProjectInfo({ type: 'addAll', stored, reset: resetStored });
      } else {
        dispatchProjectInfo({ type: 'reset' });
      }
    }
  }, [stored]);

  // When modules updates, set projects to stored value or fetch info.
  // This runs either the first time modules populates, or whenever
  // projects is reset to initialConfig.
  useEffect(() => {
    if (modules.length && !projectInfo.projects.length) {
      if (stored.value.length) {
        // Prefer the stored value.
        dispatchProjectInfo({ type: 'addAll', stored, reset: resetStored });
      } else {
        try {
          Promise.all(
            modules.map((module) => {
              return checkProjectPage(module.id);
            }),
          ).then((projects) => {
            // Store the found projects only.
            storeValue(projects.filter((project) => project));
          });
        } catch (error) {
          console.error(error);
        }
      }
    }
  }, [modules.length, projectInfo.projects]);

  return { projectInfo };
};

export default useCheckProject;
