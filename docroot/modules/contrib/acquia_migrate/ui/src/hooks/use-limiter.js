import { useState } from 'react';

const useLimiter = (defaultLimiters) => {
  const [limiters, setLimiters] = useState(defaultLimiters);

  const updateLimiter = (name, value) => {
    setLimiters({ ...limiters, [name]: limiters[name].withValue(value) });
  };

  return { limiters, updateLimiter };
};

export default useLimiter;
