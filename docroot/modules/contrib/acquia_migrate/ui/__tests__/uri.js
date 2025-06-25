import {
  parseQuery,
  parseQueryValue,
  stringifyQueryObj,
  formatURL,
  getQuery,
} from '../src/lib/uri';

describe('Can encode url from params', () => {
  const msgURL =
    'http://drupal.test/acquia-migrate-accelerate/messages?filter=%3Aeq%2Cmigration%2Cfe01e238e97d6349f9a1d68cb889dea2-Private%20files';
  const apiURL =
    'http://drupal.test/acquia-migrate-accelerate/api/messages?filter=%3Aeq%2Cmigration%2Cfe01e238e97d6349f9a1d68cb889dea2-Private%20files';
  const tpl =
    'http://drupal.test/acquia-migrate-accelerate/api/messages{?filter*}';
  const parsed = parseQuery(msgURL);

  test('Can get query from url', () => {
    expect(getQuery(msgURL)).toBe(
      '?filter=%3Aeq%2Cmigration%2Cfe01e238e97d6349f9a1d68cb889dea2-Private%20files',
    );
    expect(
      getQuery('http://drupal.test/acquia-migrate-accelerate/messages'),
    ).toBe('');
  });

  test('Can parse query from url', () => {
    expect(parsed).toMatchObject({
      filter: ':eq,migration,fe01e238e97d6349f9a1d68cb889dea2-Private files',
    });
  });

  test('Can encode with URITemplate', () => {
    expect(formatURL(parsed, tpl)).toBe(apiURL);
  });

  test('Can parse query values', () => {
    expect(parseQueryValue(parsed)).toMatchObject([
      {
        field: 'migration',
        operator: ':eq',
        value: 'fe01e238e97d6349f9a1d68cb889dea2-Private files',
      },
    ]);
  });

  test('Can join query properties', () => {
    expect(
      stringifyQueryObj([
        {
          field: 'migration',
          operator: ':eq',
          value: 'fe01e238e97d6349f9a1d68cb889dea2-Private files',
        },
      ]),
    ).toEqual([':eq,migration,fe01e238e97d6349f9a1d68cb889dea2-Private files']);
  });

  test('Can handle empty query', () => {
    expect(parseQueryValue({})).toMatchObject([]);
  });
});
