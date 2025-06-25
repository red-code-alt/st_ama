import { parseISO } from 'date-fns';

export const dateFormatString = 'yyyy-MM-dd HH:mm:ss';
export const parseDate = (date) => (date ? parseISO(date) : null);
export const parseDuration = (sec) => {
  const hours = Math.floor(sec / 3600);
  const minutes = Math.floor((sec / 60) % 60);
  const seconds = Math.floor(sec - minutes * 60 - hours * 3600);

  return { hours, minutes, seconds };
};
