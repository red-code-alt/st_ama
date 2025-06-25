import { useEffect, useRef } from 'react';

const useInterval = (callback, delay) => {
  const refCallback = useRef();

  useEffect(() => {
    refCallback.current = callback;
  }, [callback]);

  useEffect(() => {
    function tick() {
      refCallback.current();
    }

    if (delay !== null) {
      const id = setInterval(tick, delay);
      // if useEffect returns a function it is called during cleanup.
      return () => clearInterval(id);
    }
  }, [delay]);
};

export default useInterval;
