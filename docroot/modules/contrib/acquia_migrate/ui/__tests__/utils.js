import {
  STATUS,
  progress,
  progressFormat,
  progressAll,
  progressDirection,
  dependencyStatus,
  deepDependencyList,
  getMigrationById,
  migrationStatus,
  getOperations,
  parseUnknownValue,
  getDeep,
  hasCompleteLink,
  sortMigrationsByActive,
  sortMigrationsByIndex,
  sortMigrationsByLastImported,
} from '../src/lib/utils';
import { migrations } from '../__fixtures__/status';

describe('Can retrieve properties from migrations', () => {
  test('Can get deep dependency', () => {
    const examples = [
      // initial.A has import, no dependencies
      {
        id: 'A',
        label: 'A',
        migrations: migrations.initial,
        action: 'import',
        expected: [],
      },
      // initial.B has no import, 1 incomplete
      {
        id: 'B',
        label: 'B',
        migrations: migrations.initial,
        action: 'import',
        expected: [{ id: 'A', label: 'A', incomplete: true }],
      },
      // initial.D has no import, incomplete because of A.
      {
        id: 'D',
        label: 'D',
        migrations: migrations.initial,
        action: 'import',
        expected: [{ id: 'B', label: 'B', incomplete: true }],
      },
      // partialA.B has no import, A has import so is incomplete.
      {
        id: 'B',
        label: 'B',
        migrations: migrations.partialA,
        action: 'import',
        expected: [{ id: 'A', label: 'A', incomplete: true }],
      },
      // partialA.C has 2 unmet.
      {
        id: 'C',
        label: 'C',
        migrations: migrations.partialA,
        action: 'import',
        expected: [
          { id: 'A', label: 'A', incomplete: true },
          { id: 'B', label: 'B', incomplete: true },
        ],
      },
      // partialA.D has no import, incomplete because of A.
      {
        id: 'D',
        label: 'D',
        migrations: migrations.partialA,
        action: 'import',
        expected: [{ id: 'B', label: 'B', incomplete: true }],
      },
      // partialA.E has to recurse down to A to show it is incomplete.
      {
        id: 'E',
        label: 'E',
        migrations: migrations.partialA,
        action: 'import',
        expected: [{ id: 'D', label: 'D', incomplete: true }],
      },
      // partialB.A cannot import but has no dependencies
      {
        id: 'A',
        label: 'A',
        migrations: migrations.partialB,
        action: 'import',
        expected: [],
      },
      // partialB.B depends on A which has no import and no dependencies.
      {
        id: 'B',
        label: 'B',
        migrations: migrations.partialB,
        action: 'import',
        expected: [{ id: 'A', label: 'A', incomplete: false }],
      },
      // partialA.C has 1 met and 1 unmet.
      {
        id: 'C',
        label: 'C',
        migrations: migrations.partialB,
        action: 'import',
        expected: [
          { id: 'A', label: 'A', incomplete: false },
          { id: 'B', label: 'B', incomplete: true },
        ],
      },
      // partialB.D depends on B which has an import link so is incomplete.
      {
        id: 'D',
        label: 'D',
        migrations: migrations.partialB,
        action: 'import',
        expected: [{ id: 'B', label: 'B', incomplete: true }],
      },
      // complete.A cannot import and has no dependencies
      {
        id: 'A',
        label: 'A',
        migrations: migrations.complete,
        action: 'import',
        expected: [],
      },
      // complete.B depends on A which has no import and no dependencies.
      {
        id: 'B',
        label: 'B',
        migrations: migrations.complete,
        action: 'import',
        expected: [{ id: 'A', label: 'A', incomplete: false }],
      },
      // complete.C depends on A and B
      {
        id: 'C',
        label: 'C',
        migrations: migrations.complete,
        action: 'import',
        expected: [
          { id: 'A', label: 'A', incomplete: false },
          { id: 'B', label: 'B', incomplete: false },
        ],
      },
      // partialB.D depends on B.
      {
        id: 'D',
        label: 'D',
        migrations: migrations.complete,
        action: 'import',
        expected: [{ id: 'B', label: 'B', incomplete: false }],
      },
      // partialC.A depends on B, which is still importing despite just reaching completion.
      {
        id: 'A',
        label: 'A',
        migrations: migrations.partialC,
        action: 'import',
        expected: [{ id: 'B', label: 'B', incomplete: true }],
      },
    ];

    examples.forEach((example) => {
      const { id, migrations, action, expected } = example;
      const migration = migrations.find((m) => m.id === id);
      expect(dependencyStatus(migration, migrations, action)).toEqual(expected);
    });
  });

  test('An empty dependency is never considered incomplete', () => {
    expect(
      dependencyStatus(
        getMigrationById('F', migrations.initial),
        migrations.initial,
      ),
    ).toEqual([
      {
        id: 'E',
        incomplete: false,
        label: 'E',
      },
      {
        id: 'A',
        incomplete: true,
        label: 'A',
      },
    ]);
  });

  test('Can handle a migration with an unknown dependency', () => {
    expect(
      dependencyStatus(
        {
          id: 'E',
          label: 'E',
          importedCount: 0,
          totalCount: 100,
          dependencies: [{ id: 'config' }],
        },
        migrations.initial,
      ),
    ).toEqual([]);
  });

  test('Can get deep list of dependencies', () => {
    expect(
      deepDependencyList(
        getMigrationById('A', migrations.initial),
        migrations.initial,
      ),
    ).toEqual([]);
    expect(
      deepDependencyList(
        getMigrationById('B', migrations.initial),
        migrations.initial,
      ),
    ).toEqual(['A']);
    expect(
      deepDependencyList(
        getMigrationById('D', migrations.initial),
        migrations.initial,
      ),
    ).toEqual(['B', 'A']);
    expect(
      deepDependencyList(
        getMigrationById('E', migrations.partialA),
        migrations.partialA,
      ),
    ).toEqual(['D', 'B', 'A']);
    expect(
      deepDependencyList(
        getMigrationById('F', migrations.partialA),
        migrations.partialA,
      ),
    ).toEqual(['D', 'B', 'A', 'C']);
  });

  test('Empty dependencies (and indirect dependencies emanating from those) are omitted', () => {
    expect(
      deepDependencyList(
        getMigrationById('F', migrations.initial),
        migrations.initial,
      ),
    ).toEqual(['A']);
  });

  test('Can escape recursive dependency trap', () => {
    expect(
      deepDependencyList(
        getMigrationById('B', migrations.recursive1),
        migrations.recursive1,
      ),
    ).toEqual(['A']);
    expect(
      deepDependencyList(
        getMigrationById('C', migrations.recursive2),
        migrations.recursive2,
      ),
    ).toEqual(['B', 'A']);
    expect(
      deepDependencyList(
        getMigrationById('D', migrations.recursive3),
        migrations.recursive3,
      ),
    ).toEqual(['C', 'B', 'A']);
    expect(
      deepDependencyList(
        getMigrationById('D', migrations.recursive4),
        migrations.recursive4,
      ),
    ).toEqual(['C', 'B', 'A']);
  });

  test('Can handle a migration with a circular dependency', () => {
    expect(
      dependencyStatus(
        getMigrationById('A', migrations.recursive1),
        migrations.recursive1,
        'import',
      ),
    ).toEqual([{ id: 'B', label: 'B', incomplete: true }]);
    expect(
      dependencyStatus(
        getMigrationById('C', migrations.recursive1),
        migrations.recursive1,
        'import',
      ),
    ).toEqual([
      { id: 'A', label: 'A', incomplete: true },
      { id: 'B', label: 'B', incomplete: true },
    ]);
    expect(
      dependencyStatus(
        getMigrationById('C', migrations.recursive2),
        migrations.recursive2,
        'import',
      ),
    ).toEqual([{ id: 'B', label: 'B', incomplete: true }]);
    expect(
      dependencyStatus(
        getMigrationById('D', migrations.recursive3),
        migrations.recursive3,
        'import',
      ),
    ).toEqual([
      { id: 'C', label: 'C', incomplete: true },
      { id: 'B', label: 'B', incomplete: true },
    ]);
    expect(
      dependencyStatus(
        getMigrationById('D', migrations.recursive4),
        migrations.recursive4,
        'import',
      ),
    ).toEqual([{ id: 'C', label: 'C', incomplete: true }]);
  });
});

describe('Can sort migrations by queue', () => {
  const original = ['A', 'B', 'C', 'D', 'E', 'F'];
  const expected1 = ['B', 'C', 'A', 'D', 'E', 'F'];
  const expected2 = ['C', 'B', 'A', 'D', 'E', 'F'];
  const queueNone = [
    { qid: '0-Y', id: 'Y' },
    { qid: '1-Z', id: 'Z' },
  ];
  const queue1 = [
    { qid: '0-B', id: 'B' },
    { qid: '1-C', id: 'C' },
  ];
  const queue2 = [
    { qid: '1-C', id: 'C' },
    { qid: '0-B', id: 'B' },
  ];
  const queueDupe = [
    { qid: '0-B', id: 'B' },
    { qid: '1-C', id: 'C' },
    { qid: '2-B', id: 'B' },
  ];
  const getIds = (list) => list.map((item) => item.id);
  test('Migrations not in the queue are unaffected', () => {
    const sorted1 = sortMigrationsByActive(migrations.partialA, []);
    const sorted2 = sortMigrationsByActive(migrations.partialA, queueNone);
    expect(getIds(sorted1)).toEqual(original);
    expect(getIds(sorted2)).toEqual(original);
  });

  test('Migrations are sorted by queue order first', () => {
    const sorted1 = sortMigrationsByActive(migrations.partialA, queue1);
    const sorted2 = sortMigrationsByActive(migrations.partialA, queue2);
    expect(getIds(sorted1)).toEqual(expected1);
    expect(getIds(sorted2)).toEqual(expected2);
  });

  test('Queues may have multiple items with the same id', () => {
    const sorted = sortMigrationsByActive(migrations.partialA, queueDupe);
    expect(getIds(sorted)).toEqual(expected1);
  });
});

describe('Can sort migrations by property', () => {
  const migrations = [
    {
      id: 'B',
      index: 1,
      lastImported: {
        startTime: '2020-06-24T21:38:37+00:00',
        endTime: '2020-06-24T21:38:37+00:00',
        duration: 0,
      },
    },
    {
      id: 'A',
      index: 0,
      lastImported: {
        startTime: '2020-06-24T21:34:39+00:00',
        endTime: '2020-06-24T21:34:40+00:00',
        duration: 1,
      },
    },
    {
      id: 'D',
      index: 3,
      lastImported: null,
    },
    {
      id: 'C',
      index: 2,
      lastImported: {
        startTime: '2020-06-24T21:41:36+00:00',
        endTime: '2020-06-24T21:41:36+00:00',
        duration: 0,
      },
    },
  ];
  test('Migrations can be sorted by collection index', () => {
    const sorted = sortMigrationsByIndex(migrations);
    expect(sorted.map((migration) => migration.id)).toEqual([
      'A',
      'B',
      'C',
      'D',
    ]);
  });
  test('Migrations can be sorted by lastImported.endTime', () => {
    const recent = sortMigrationsByLastImported(migrations);
    console.log(
      'last imported',
      recent.map((migration) => migration.id),
    );
    expect(recent.map((migration) => migration.id)).toEqual([
      'C',
      'B',
      'A',
      'D',
    ]);
  });
});

describe('Can get migration status', () => {
  const linkErr = {
    'migration-messages': {
      rel: 'https://drupal.org/project/acquia_migrate#link-rel-migration-messages',
    },
  };

  // Unprocessed migration.
  expect(
    migrationStatus({
      importedCount: 0,
      processedCount: 0,
      totalCount: 100,
      completed: false,
      skipped: false,
    }),
  ).toMatchObject(STATUS.ready);
  // Not fully processed successful migration.
  expect(
    migrationStatus({
      importedCount: 45,
      processedCount: 45,
      totalCount: 100,
      completed: false,
      skipped: false,
    }),
  ).toMatchObject(STATUS.partialOK);
  // Not fully processed de-synced migration.
  expect(
    migrationStatus({
      importedCount: 45,
      processedCount: 50,
      totalCount: 100,
      completed: false,
      skipped: false,
    }),
  ).toMatchObject(STATUS.partialNotice);
  // Not fully processed migration with errors.
  expect(
    migrationStatus({
      importedCount: 45,
      processedCount: 50,
      totalCount: 100,
      completed: false,
      skipped: false,
      links: linkErr,
    }),
  ).toMatchObject(STATUS.partialErr);
  // Processed successful migration.
  expect(
    migrationStatus({
      importedCount: 100,
      processedCount: 100,
      totalCount: 100,
      completed: false,
      skipped: false,
    }),
  ).toMatchObject(STATUS.importedOK);
  // Processed successful migration with errors.
  expect(
    migrationStatus({
      importedCount: 100,
      processedCount: 100,
      totalCount: 100,
      completed: false,
      skipped: false,
      links: linkErr,
    }),
  ).toMatchObject(STATUS.importedWarning);
  // Processed incomplete, no errors.
  expect(
    migrationStatus({
      importedCount: 95,
      processedCount: 100,
      totalCount: 100,
      completed: false,
      skipped: false,
    }),
  ).toMatchObject(STATUS.importedNotice);
  // Processed unsuccessful migration.
  expect(
    migrationStatus({
      importedCount: 95,
      processedCount: 100,
      totalCount: 100,
      completed: false,
      skipped: false,
      links: linkErr,
    }),
  ).toMatchObject(STATUS.importedErr);
  // Completed successful migration.
  expect(
    migrationStatus({
      importedCount: 100,
      processedCount: 100,
      totalCount: 100,
      completed: true,
      skipped: false,
    }),
  ).toMatchObject(STATUS.completedOK);
  // Completed migration with new content.
  expect(
    migrationStatus({
      importedCount: 100,
      processedCount: 100,
      totalCount: 110,
      completed: true,
      skipped: false,
    }),
  ).toMatchObject(STATUS.completedOutdated);
  // Skipped migration.
  expect(
    migrationStatus({
      importedCount: 0,
      processedCount: 0,
      totalCount: 0,
      completed: false,
      skipped: true,
    }),
  ).toMatchObject(STATUS.skippedOK);
  // Skipped migration that could be processed?
  expect(
    migrationStatus({
      importedCount: 0,
      processedCount: 0,
      totalCount: 10,
      completed: false,
      skipped: true,
    }),
  ).toMatchObject(STATUS.skippedCheck);
});

describe('Can process various scalar values', () => {
  expect(parseUnknownValue('any text')).toBe('any text');
  expect(parseUnknownValue('')).toBe('""');
  expect(parseUnknownValue(null)).toBe('null');
  expect(parseUnknownValue(true)).toBe('true');
  expect(parseUnknownValue(false)).toBe('false');
  expect(parseUnknownValue(123)).toBe('123');
  expect(parseUnknownValue(3.14)).toBe('3.14');
  expect(parseUnknownValue([])).toBe('[]');
  expect(parseUnknownValue(['text', null, true, 123])).toBe(
    'text; null; true; 123',
  );

  expect(parseUnknownValue({})).toBe('{}');
  expect(parseUnknownValue({ tid: 4 })).toBe('{ tid: 4 }');
  expect(parseUnknownValue({ value: 'Kai Opaca', format: null })).toBe(
    '{ value: Kai Opaca, format: null }',
  );
  expect(
    parseUnknownValue([
      {
        target_id: '9',
      },
      {
        target_id: '14',
      },
      {
        target_id: '17',
      },
    ]),
  ).toBe('{ target_id: 9 }; { target_id: 14 }; { target_id: 17 }');
  expect(
    parseUnknownValue([
      {
        uri: 'internal:/',
        options: {
          attributes: {
            title: '',
          },
        },
        title: 'Home',
      },
    ]),
  ).toBe(
    '{ uri: internal:/, options: { attributes: { title: "" } }, title: Home }',
  );
});

describe('Can format and calculate progress', () => {
  test('progress returns percent to  4 places', () => {
    expect(progress(1, 10)).toBe(0.1);
    expect(progress(10, 10)).toBe(1.0);
    expect(progress(3, 10)).toBe(0.3);
    expect(progress(33, 100)).toBe(0.33);
    expect(progress(17360, 36948)).toBe(0.4698);
    expect(progress(0, 0)).toBe(0.0);
    expect(progress(1, 0)).toBe(0.0);
    expect(progress(2, 1)).toBe(1.0);
  });

  test('progress format returns string to 2 places', () => {
    expect(progressFormat(progress(1, 10))).toBe('10.00');
    expect(progressFormat(progress(10, 10))).toBe('100.00');
    expect(progressFormat(progress(3, 10))).toBe('30.00');
    expect(progressFormat(progress(33, 100))).toBe('33.00');
    expect(progressFormat(progress(17360, 36948))).toBe('46.98');
    expect(progressFormat(progress(0, 0))).toBe('0.00');
    expect(progressFormat(progress(1, 0))).toBe('0.00');
    expect(progressFormat(progress(2, 1))).toBe('100.00');
  });

  const sampleData = [
    {
      completed: 0.5,
      rows: [
        {
          importedCount: 1,
          totalCount: 2,
        },
        {
          importedCount: 2,
          totalCount: 4,
        },
      ],
    },
    {
      completed: 0.75,
      rows: [
        {
          importedCount: 1,
          totalCount: 2,
        },
        {
          importedCount: 4,
          totalCount: 4,
        },
      ],
    },
    {
      completed: 0.4849,
      rows: [
        {
          importedCount: 1,
          totalCount: 2,
        },
        {
          importedCount: 17360,
          totalCount: 36948,
        },
      ],
    },
    {
      completed: 0.0,
      rows: [
        {
          importedCount: 0,
          totalCount: 0,
        },
      ],
    },
  ];

  const sampleMigrations = [
    {
      migration: {
        importedCount: 0,
        totalCount: 0,
      },
      forward: 0,
      backward: 0,
    },
    {
      migration: {
        importedCount: 1,
        totalCount: 2,
      },
      forward: 0.5,
      backward: 0.5,
    },
    {
      migration: {
        importedCount: 3,
        totalCount: 4,
      },
      forward: 0.75,
      backward: 0.25,
    },
    {
      migration: {
        importedCount: 99,
        totalCount: 100,
      },
      forward: 0.99,
      backward: 0.01,
    },
  ];

  test('progress all gives overall progress', () => {
    sampleData.forEach((sample) => {
      expect(progressAll(sample.rows)).toBe(sample.completed);
    });
  });

  test('progress depends on migration direction', () => {
    sampleMigrations.forEach((sample) => {
      const { migration, forward, backward } = sample;
      expect(progressDirection(migration, 'import')).toBe(forward);
      expect(progressDirection(migration, 'rollback')).toBe(backward);
    });
  });
});

describe('Can get a deep property', () => {
  const TestGlobal = {
    displace: {
      offsets: {
        top: 42,
      },
    },
  };

  expect(getDeep(TestGlobal, 'displace.offsets')).toMatchObject({ top: 42 });
  expect(getDeep(TestGlobal, 'displace.offsets.top')).toBe(42);
  expect(getDeep(TestGlobal, 'displace.offsets.bottom')).toBe(null);
});

describe('Can get or check for links by rel', () => {
  test('Can check for a complete link with update rel', () => {
    const exampleComplete = [
      {
        links: {
          complete: {
            rel: 'https://drupal.org/project/acquia_migrate#link-rel-update-resource',
          },
        },
        expected: true,
      },
      {
        links: {
          complete: {
            rel: 'unknown-rel',
          },
        },
        expected: false,
      },
      {
        links: {
          complete: {},
        },
        expected: false,
      },
      {
        links: {
          other: {},
        },
        expected: false,
      },
    ];
    exampleComplete.forEach((testCase) => {
      const { links, expected } = testCase;
      expect(hasCompleteLink(links)).toBe(expected);
    });
  });
});

describe('Can get Migration operations', () => {
  const examples = {
    a: {
      id: 'A',
      links: {
        import: {
          href: '#',
          title: 'Import',
          rel: 'https://drupal.org/project/acquia_migrate#link-rel-start-batch-process',
        },
      },
    },
    b: {
      id: 'B',
      links: {
        import: {
          href: '#',
          title: 'Import',
          rel: 'https://drupal.org/project/acquia_migrate#link-rel-start-batch-process',
        },
      },
    },
    c: {
      id: 'C',
      links: {
        rollback: {
          href: '#',
          title: 'Rollback',
          rel: 'https://drupal.org/project/acquia_migrate#link-rel-start-batch-process',
        },
        'rollback-and-import': {
          href: '#',
          title: 'Rollback and import',
          rel: 'https://drupal.org/project/acquia_migrate#link-rel-start-batch-process',
        },
      },
    },
    d: {
      id: 'D',
      links: {
        rollback: {
          href: '#',
          title: 'Rollback',
          rel: 'https://drupal.org/project/acquia_migrate#link-rel-start-batch-process',
        },
        'rollback-and-import': {
          href: '#',
          title: 'Rollback and import',
          rel: 'https://drupal.org/project/acquia_migrate#link-rel-start-batch-process',
        },
      },
    },
  };
  test('Can get available operations from current migrations', () => {
    const result = new Map();
    result.set('import', 'Import');
    expect(getOperations([examples.a, examples.b])).toMatchObject(result);
    result.clear();

    result
      .set('rollback', 'Rollback')
      .set('rollback-and-import', 'Rollback and import');
    expect(getOperations([examples.c, examples.d])).toMatchObject(result);
    result.clear();

    result
      .set('import', 'Import')
      .set('rollback', 'Rollback')
      .set('rollback-and-import', 'Rollback and import');
    expect(getOperations([examples.a, examples.c])).toMatchObject(result);
  });

  test('Can filter to allowed operations', () => {
    const result = new Map();
    result.set('import', 'Import');
    const allowed = {
      importOnly: ['import'],
      importRollback: ['import', 'rollback'],
      importRollbackRefresh: ['import', 'rollback', 'refresh'],
      none: ['none'],
    };
    // Allow op that is found in source
    expect(
      getOperations([examples.a, examples.b], allowed.importOnly),
    ).toMatchObject(result);
    // Allow op that is not found in source with 1 match.
    expect(
      getOperations([examples.a, examples.b], allowed.importRollback),
    ).toMatchObject(result);
    // Allow only 1 op out of 3 found in source.
    expect(
      getOperations([examples.a, examples.c], allowed.importOnly),
    ).toMatchObject(result);
    result.clear();
    result.set('import', 'Import').set('rollback', 'Rollback');
    // Allow 2 ops out of 3 found in source.
    expect(
      getOperations([examples.a, examples.c], allowed.importRollback),
    ).toMatchObject(result);
    // Allow op not found in source with 2 matches.
    expect(
      getOperations([examples.a, examples.c], allowed.importRollbackRefresh),
    ).toMatchObject(result);

    result.set('rollback-and-import', 'Rollback and import');
    // Undefined op should allow all results.
    expect(
      getOperations([examples.a, examples.c], allowed.notExists),
    ).toMatchObject(result);

    result.clear();
    // Allow op not found in source, no results.
    expect(getOperations([examples.a, examples.c], allowed.none)).toMatchObject(
      result,
    );
  });
});
