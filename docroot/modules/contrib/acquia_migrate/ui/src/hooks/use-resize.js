import { useEffect, useState } from 'react';

const useResize = (el, property) => {
  const [size, setSize] = useState(0);
  useEffect(() => {
    if (el) {
      const resizeObserver = new ResizeObserver(() => {
        const bounding = el.getBoundingClientRect();
        setSize(bounding[property]);
      });

      resizeObserver.observe(el);

      return () => {
        resizeObserver.unobserve(el);
      };
    }
  }, [el]);

  return size;
};

export default useResize;
