export const isItemComplete = (item) => item.progressRatio === 1 && !item.next;

/**
 * Checks if all queue items are completed.
 *
 * @param {array} queue
 *   The queue list.
 * @return {boolean}
 *   If all items are complete.
 */
export const queueCompleted = (queue) => queue.every(isItemComplete);

/**
 * Count complete and incomplete queue items.
 *
 * @param queue
 *   The queue list.
 * @return {{completed:number, incomplete:number}}
 *   The current queue count.
 */
export const queueCompleteCount = (queue) =>
  queue.reduce(
    (count, item) => {
      const isComplete = isItemComplete(item) ? 'completed' : 'incomplete';
      count[isComplete] += 1;
      return count;
    },
    { completed: 0, incomplete: 0 },
  );
