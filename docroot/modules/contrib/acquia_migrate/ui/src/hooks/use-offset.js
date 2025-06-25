import { useEffect, useState } from 'react';

const useOffset = () => {
  const target = document.getElementsByTagName('body')[0];
  const [top, setTop] = useState(parseInt(target.style.paddingTop, 10));

  useEffect(() => {
    const observer = new MutationObserver(() => {
      // Drupal.display.offsets.top is inexplicably out of sync with padding.
      setTop(parseInt(target.style.paddingTop, 10));
    });
    observer.observe(target, { attributes: true, attributeFilter: ['style'] });

    // Cleanup observer on unmount.
    return () => {
      observer.disconnect();
    };
  }, []);

  return top;
};

export default useOffset;
