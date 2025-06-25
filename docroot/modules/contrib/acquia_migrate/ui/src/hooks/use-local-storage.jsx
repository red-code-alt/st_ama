import { useCallback, useEffect, useState } from 'react';

const wrapValue = (value) => ({ value, timestamp: Date.now() });

const get = (key, initialValue) => {
  try {
    const storedValue = window.localStorage.getItem(key);
    return storedValue ? JSON.parse(storedValue) : wrapValue(initialValue);
  } catch (error) {
    console.error(error);
    return wrapValue(initialValue);
  }
};

const set = (key, value) => {
  try {
    const storedValue = wrapValue(value);
    window.localStorage.setItem(key, JSON.stringify(storedValue));
    return storedValue;
  } catch (error) {
    console.error(error);
  }
};

const useLocalStorage = (key, initialValue) => {
  // If localStorage is set for this key, it will be the initial value.
  const [stored, setStored] = useState(() => get(key, initialValue));

  const storeValue = (value) => {
    try {
      const storedValue = set(key, value);
      setStored(storedValue);
    } catch (error) {
      console.error(error);
    }
  };

  // Update the state if the localStorage was changed.
  const updateStorage = useCallback(
    (event) => {
      if (event.key === key) {
        setStored(JSON.parse(event.newValue));
      }
    },
    [stored],
  );

  useEffect(() => {
    window.addEventListener('storage', updateStorage);
    return () => window.removeEventListener('storage', updateStorage);
  }, [updateStorage]);

  return { stored, storeValue };
};

export default useLocalStorage;
