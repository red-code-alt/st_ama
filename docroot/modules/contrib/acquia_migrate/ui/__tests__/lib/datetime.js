import { formatDuration } from 'date-fns';
import { parseDuration } from '../../src/lib/datetime';

test('Can get duration', () => {
  expect(parseDuration(0)).toMatchObject({ hours: 0, minutes: 0, seconds: 0 });
  expect(parseDuration(2)).toMatchObject({ hours: 0, minutes: 0, seconds: 2 });
  expect(parseDuration(62)).toMatchObject({ hours: 0, minutes: 1, seconds: 2 });
  expect(parseDuration(3662)).toMatchObject({
    hours: 1,
    minutes: 1,
    seconds: 2,
  });
  expect(parseDuration(7405)).toMatchObject({
    hours: 2,
    minutes: 3,
    seconds: 25,
  });
  expect(parseDuration(21599)).toMatchObject({
    hours: 5,
    minutes: 59,
    seconds: 59,
  });
  expect(parseDuration(12960000)).toMatchObject({
    hours: 3600,
    minutes: 0,
    seconds: 0,
  });
  expect(formatDuration(parseDuration(0))).toBe('');
  expect(formatDuration(parseDuration(3662))).toBe('1 hour 1 minute 2 seconds');
  expect(formatDuration(parseDuration(7405))).toBe(
    '2 hours 3 minutes 25 seconds',
  );
  expect(formatDuration(parseDuration(21599))).toBe(
    '5 hours 59 minutes 59 seconds',
  );
});
