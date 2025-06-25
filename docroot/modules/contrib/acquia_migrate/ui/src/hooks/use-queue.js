import { useContext, useEffect, useReducer } from 'react';
import { useAsync } from 'react-async';

import useToggle from './use-toggle';
import { process } from '../lib/api';
import { queueCompleted } from '../lib/queue';
import { ErrorContext } from '../errors/try-catch';

const isActiveItem = (item, active) => item.qid === active.qid;
// If no active item return a default object.
const getActiveItem = (queue, active) =>
  queue.find((item) => isActiveItem(item, active)) || {
    id: null,
    qid: null,
    start: null,
    next: null,
    operation: null,
    pending: null,
  };

const isActiveItemPending = (queue, active) =>
  getActiveItem(queue, active).pending;

const incrementQueue = (queue, active) => {
  const index = queue.findIndex((item) => item.qid === active.qid);
  const nextItem = queue[index + 1];
  return nextItem ? { qid: nextItem.qid, id: nextItem.id } : {};
};

/**
 * Modify the queue, unless queue is unchanged the reducer returns a copy.
 *
 * @param {array} state
 *   Current queue
 * @param {object} action
 *   The reducer type and data.
 * @param {string} action.type
 *   The type determines how the new item affects the existing queue.
 *   Add: Gives item a default progressRatio and appends to the end of queue.
 *   Request: After a request, the link is removed so it can be replaced with
 *   any new link from the response. "status" is used for the queue display.
 *   Update: Merge values from response into the queue item, increment active if
 *   the batch is finished for this item.
 *   Cleanup: Remove any completed items from the queue.
 *   Remove: The matching item is removed from the queue, if it was active the
 *   queue is incremented.
 *   Reset: If the queue is paused and a stop link is called, any next links are
 *   now invalid. The start link was removed after requested, so the copy stored
 *   in the "reset" property is copied back to put the queue item in close to
 *   its original state. The status "paused" is currently the only status which
 *   is not solely for display. If the stop link is called after a start or next
 *   the start or next will still have a response which triggers "update".
 *   Having the status set to "paused" ignores that response and prevents active
 *   queue item from being overwritten.
 *   Clear: empty the queue entirely.
 *
 * @param {object} action.item
 *   The item originally has a start url which is used as a unique id. Responses
 *   will have a progressRatio and qid which is saved into the item.
 * @return {{queue: [], active: {}}}
 *   The queueData state.
 */
const queueReducer = (state, action) => {
  switch (action.type) {
    case 'add':
      // Return new array adding item and default progressRatio.
      // qid for referencing _this_ time the item was added.
      // If this is the first item in the queue set it as active.
      const qid = `${state.queue.length}-${action.item.id}`;

      return {
        active: state.active.hasOwnProperty('qid')
          ? state.active
          : { id: action.item.id, qid },
        queue: [
          ...state.queue,
          {
            status: 'queued',
            progressRatio: 0,
            qid,
            ...action.item,
            // Save a copy of the start link to allow for a reset.
            reset: action.item.start,
          },
        ],
      };
    case 'request':
      // Remove requested link from queue item.
      return {
        ...state,
        queue: state.queue.map((item) =>
          item.qid === action.item.qid
            ? {
                ...item,
                pending: true,
                status: action.item.status,
                [action.item.link]: null,
              }
            : item,
        ),
      };

    case 'update':
      return {
        // Increment if the response item matches active but has no "next" link.
        active:
          !action.item.next && action.item.qid === state.active.qid
            ? incrementQueue(state.queue, state.active, action.item)
            : state.active,
        queue: state.queue.map((item) =>
          // Normally the links should all be wiped, but when the queue item is
          // in a reset state, it should not be changed by the update.
          item.qid === action.item.qid && item.status !== 'paused'
            ? {
                ...item,
                start: null,
                next: null,
                pending: false,
                ...action.item,
                status: action.item.next ? action.item.status : 'completed',
              }
            : item,
        ),
      };

    case 'cleanup':
      return {
        ...state,
        queue: state.queue.filter(
          (item) =>
            (item.start || item.next || item.pending) &&
            item.progressRatio !== 1,
        ),
      };

    case 'remove':
      return {
        // If removing active item, increment.
        active:
          state.active && state.active.qid === action.qid
            ? incrementQueue(state.queue, state.active)
            : state.active,
        queue: state.queue.filter((item) => item.qid !== action.qid),
      };

    case 'reset':
      return {
        active: state.active,
        queue: state.queue.map((item) => {
          if (item.qid === action.qid) {
            item.start = item.reset;
            item.next = null;
            item.status = 'paused';
            item.pending = false;
          }

          return item;
        }),
      };

    case 'clear':
      return { active: {}, queue: [] };

    default:
      return state;
  }
};

/**
 * Hook for managing queue API calls and displaying updates.
 *
 * This is updated asynchronously by the reducer and the fetches to the API.
 * Each update causes this hook to re-evaluate, and if one of the variables that
 * is used in the second argument of a useEffect is changed, the function in the
 * useEffect will be called.
 *
 * 1. Empty queue: data is undefined.
 * 2. Item added to queue: active set to the new item, POST to start href.
 * 3. Response from fetch: the batch response is added to the queue item.
 * 4. GET the next href or if progressRatio: 1, active is incremented.
 *
 * @return {object}
 *   Data and methods.
 */
const useQueue = () => {
  const { throwError } = useContext(ErrorContext);
  const [isQueueRunning, setQueueRunning] = useToggle(true);
  const [isQueueStopped, setQueueStopped] = useToggle(false);
  const [queueData, dispatchQueueData] = useReducer(queueReducer, {
    queue: [],
    active: {},
  });

  const { queue, active } = queueData;
  const { start, next, pending } = getActiveItem(queue, active);
  const { data, run } = useAsync({ deferFn: process });

  const isQueueCompleted = queueCompleted(queue);
  const isActivePending = isActiveItemPending(queue, active);

  useEffect(() => {
    if (data) {
      dispatchQueueData({ type: 'update', item: data });
    }
  }, [data]);

  useEffect(() => {
    if (start && isQueueRunning && !isQueueStopped) {
      const {
        qid,
        start: { href },
      } = getActiveItem(queue, active);
      // Remove start link, set pending
      if (!pending) {
        dispatchQueueData({
          type: 'request',
          item: { qid, link: 'start', status: 'started' },
        });
        run({
          method: 'POST',
          href,
          qid,
          throwError,
        });
      }
    }
  }, [start, isQueueRunning]);

  useEffect(() => {
    if (next && isQueueRunning && !isQueueStopped) {
      const {
        qid,
        next: { href },
      } = getActiveItem(queue, active);
      // Remove start link, set pending
      if (!pending) {
        dispatchQueueData({
          type: 'request',
          item: { qid, link: 'next', status: 'running' },
        });
        run({
          method: 'GET',
          href,
          qid,
          throwError,
        });
      }
    }
  }, [next, isQueueRunning]);

  // After clearing the queue, unpause.
  useEffect(() => {
    if (queue.length === 0 && !isQueueRunning) {
      setQueueRunning();
    }
  }, [queue.length]);

  // After queue items are complete, remove the completed items.
  useEffect(() => {
    if (isQueueCompleted) {
      setTimeout(() => {
        dispatchQueueData({ type: 'cleanup' });
      }, 1500);
    }
  }, [isQueueCompleted]);

  const addToQueue = (item) => {
    if (isQueueStopped) {
      setQueueStopped();
    }
    dispatchQueueData({ type: 'add', item });
  };

  const removeFromQueue = (qid) => {
    dispatchQueueData({ type: 'remove', qid });
  };

  const resetInQueue = (qid) => {
    dispatchQueueData({ type: 'reset', qid });
  };

  const clearQueue = () => {
    dispatchQueueData({ type: 'clear' });
  };

  const cleanupQueue = () => {
    dispatchQueueData({ type: 'cleanup' });
  };

  const setQueueClear = () => {
    if (!isQueueStopped) {
      setQueueStopped();
    }
  };

  return {
    active,
    queue,
    addToQueue,
    removeFromQueue,
    resetInQueue,
    clearQueue,
    cleanupQueue,
    isQueueRunning,
    setQueueRunning,
    setQueueClear,
    isQueueStopped,
    isQueueCompleted,
    isActivePending,
  };
};

export default useQueue;
