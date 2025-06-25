/**
 * @jest-environment jsdom
 */

import Resource from '../../src/lib/resource/resource';
import 'isomorphic-fetch';

describe('Creating a Resource', () => {
  test('should fail if not created with an href or a URI template.', () => {
    expect(() => {
      new Resource({ handleError: console.error });
    }).toThrow('An href or a URI template is required.');
  });

  test('should succeed if created with an href or a URI template.', () => {
    expect(() => {
      new Resource({ handleError: console.error, href: 'https://example.com' });
    }).not.toThrow();

    expect(() => {
      new Resource({
        handleError: console.error,
        uriTemplate: 'https://example.com{/who}',
      });
    }).not.toThrow();
  });
});

describe('Calling Resource.get', () => {
  const testDocument = {
    data: {
      type: 'example',
      id: 1,
    },
  };
  let testResource, testResourceOptions;
  beforeEach(() => {
    testResourceOptions = {
      handleError: jest.fn(),
      href: 'https://example.com',
    };
    testResource = new Resource(testResourceOptions);
  });

  test('should resolve a document.', async () => {
    testResource.fetcher.fetchFn = (href, options) =>
      Promise.resolve(new Response(JSON.stringify(testDocument)));
    const actual = await testResource.get();
    expect(actual).toEqual(testDocument);
  });

  test('should resolve the same document if the response status code is 304.', async () => {
    testResource.fetcher.fetchFn = (href, options) =>
      Promise.resolve(new Response(JSON.stringify(testDocument)));
    await testResource.get();
    testResource.fetcher.fetchFn = (href, options) =>
      Promise.resolve(
        new Response(null, {
          status: 304,
        }),
      );
    const actual = await testResource.get();
    expect(actual).toEqual(testDocument);
  });

  test('should call the error handler if a response status code is neither OK nor a 304.', async () => {
    testResource.fetcher.fetchFn = (href, options) =>
      Promise.resolve(
        new Response(null, {
          status: 404,
        }),
      );
    await testResource.get();
    expect(testResourceOptions.handleError).toHaveBeenCalledTimes(1);
  });

  test('should not fetch if called multiple times without the refresh flag set.', async () => {
    const counterFn = jest.fn();
    testResource.fetcher.fetchFn = () => {
      counterFn();
      return Promise.resolve(new Response(JSON.stringify({})));
    };
    const expectCount = 2;
    await testResource.get();
    await testResource.get(true);
    expect(counterFn).toHaveBeenCalledTimes(expectCount);
    await testResource.get();
    expect(counterFn).toHaveBeenCalledTimes(expectCount);
  });
});

describe('Calling Resource.poll', () => {
  const waitMilliseconds = (timeout) =>
    new Promise((res) => setTimeout(() => res(true), timeout));
  const testDocument = {
    data: {
      type: 'example',
      id: 1,
    },
  };
  let testResource, testResourceOptions;
  beforeEach(() => {
    testResourceOptions = {
      handleError: jest.fn(),
      href: 'https://example.com',
    };
    testResource = new Resource(testResourceOptions);
  });

  test('should call the given update function until the polling is stopped and then never again.', async () => {
    testResource.fetcher.timeoutInFlux = 0;
    testResource.fetcher.fetchFn = (href, options) =>
      Promise.resolve(new Response(JSON.stringify(testDocument)));
    let firstCount = 0;
    const stopFirstPoll = testResource.poll({ updateFn: () => firstCount++ });
    await waitMilliseconds(25);
    stopFirstPoll();
    const interimCount = firstCount;
    expect(firstCount).toBeGreaterThan(1);
    await waitMilliseconds(25);
    expect(firstCount).toEqual(interimCount);
    let secondCount = 0;
    const stopSecondPoll = testResource.poll({ updateFn: () => secondCount++ });
    await waitMilliseconds(25);
    stopSecondPoll();
    expect(secondCount).toBeGreaterThan(1);
    expect(firstCount).toEqual(interimCount);
  });

  test('should not call the given update function if the polling has been stopped during an unresolved fetch.', async () => {
    testResource.fetcher.timeoutInFlux = 0;
    testResource.fetcher.fetchFn = (href, options) =>
      new Promise((res) => {
        waitMilliseconds(30).then(() => {
          res(new Response(JSON.stringify(testDocument)));
        });
      });
    const canary = jest.fn();
    const stop = testResource.poll({ updateFn: canary });
    await waitMilliseconds(40);
    expect(canary).toHaveBeenCalledTimes(1);
    stop();
    await waitMilliseconds(40);
    expect(canary).toHaveBeenCalledTimes(1);
  });

  test('should ensure that the update function is called whenever Resource.get is called and returns a fresh response.', async () => {
    testResource.fetcher.timeoutInFlux = 30;
    testResource.fetcher.fetchFn = (href, options) =>
      Promise.resolve(new Response(JSON.stringify(testDocument)));
    const canary = jest.fn();
    const stop = testResource.poll({ updateFn: canary });
    await waitMilliseconds(10);
    expect(canary).toHaveBeenCalledTimes(1);
    await testResource.get(/* refresh */ true);
    expect(canary).toHaveBeenCalledTimes(2);
    stop();
  });
});
