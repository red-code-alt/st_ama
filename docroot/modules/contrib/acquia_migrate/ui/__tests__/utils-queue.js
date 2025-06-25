import { queueCompleted, queueCompleteCount } from '../src/lib/queue';

describe('Queue completed status', () => {
  const queue1 = [
    {
      progressRatio: 1,
    },
    {
      progressRatio: 1,
    },
    {
      progressRatio: 1,
    },
  ];
  const queue2 = [
    {
      progressRatio: 1,
    },
    {
      progressRatio: 1,
    },
    {
      progressRatio: 0.5,
      next: 'http://get-next-link',
    },
  ];

  test('Can determine if queue is completed', () => {
    expect(queueCompleted(queue1)).toBe(true);
    expect(queueCompleted(queue2)).toBe(false);
  });

  test('Can count how many queue items are completed', () => {
    expect(queueCompleteCount(queue1)).toMatchObject({
      completed: 3,
      incomplete: 0,
    });
    expect(queueCompleteCount(queue2)).toMatchObject({
      completed: 2,
      incomplete: 1,
    });
  });
});
