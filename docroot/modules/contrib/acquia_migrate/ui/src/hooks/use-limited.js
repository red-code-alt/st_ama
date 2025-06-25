import { useMemo } from 'react';

const useLimited = (all, limiters) => {
  const allHash = all.map((item) => item.id).join();
  const limiterHash = Object.values(limiters)
    .map((limiter) => `${limiter.name}${limiter.value}`)
    .join('_');

  const limitAll = (list) => {
    return list.filter((item) =>
      Object.values(limiters).every((limiter) => limiter.test(item)),
    );
  };

  const limited = useMemo(() => limitAll(all), [allHash, limiterHash]);

  return limited;
};

export default useLimited;
