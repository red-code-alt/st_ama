export const migrations = {
  initial: [
    {
      id: 'A',
      label: 'A',
      importedCount: 0,
      totalCount: 100,
      dependencies: [],
      activity: 'idle',
      links: {
        import: {
          href: '#',
          title: 'Import',
          rel: '#'
        }
      }
    },
    {
      id: 'B',
      label: 'B',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'A' }],
      activity: 'idle',
    },
    {
      id: 'C',
      label: 'C',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'A' }, { id: 'B' }],
      activity: 'idle',
    },
    {
      id: 'D',
      label: 'D',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'B' }],
      activity: 'idle',
    },
    {
      id: 'E',
      label: 'E',
      importedCount: 0,
      totalCount: 0,
      dependencies: [{ id: 'B' }],
      activity: 'idle',
    },
    {
      id: 'F',
      label: 'F',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'E' }, { id: 'A' }],
      activity: 'idle',
    },
  ],
  partialA: [
    {
      id: 'A',
      label: 'A',
      importedCount: 50,
      totalCount: 100,
      dependencies: [],
      activity: 'idle',
      links: {
        import: {
          href: '#',
          title: 'Import',
          rel: '#'
        },
        rollback: {
          href: '#',
          title: 'Rollback',
          rel: '#'
        },
        rollbackAndImport: {
          href: '#',
          title: 'Rollback & Import',
          rel: '#'
        }
      }
    },
    {
      id: 'B',
      label: 'B',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'A' }],
      activity: 'idle',
    },
    {
      id: 'C',
      label: 'C',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'A' }, { id: 'B' }],
      activity: 'idle',
    },
    {
      id: 'D',
      label: 'D',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'B' }],
      activity: 'idle',
    },
    {
      id: 'E',
      label: 'E',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'D' }],
      activity: 'idle',
    },
    {
      id: 'F',
      label: 'F',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'D' }, { id: 'C' }],
      activity: 'idle',
    },
  ],
  partialB: [
    {
      id: 'A',
      label: 'A',
      importedCount: 100,
      totalCount: 100,
      dependencies: [],
      activity: 'idle',
      links: {
        rollback: {
          href: '#',
          title: 'Rollback',
          rel: '#'
        },
        rollbackAndImport: {
          href: '#',
          title: 'Rollback & Import',
          rel: '#'
        }
      }
    },
    {
      id: 'B',
      label: 'B',
      importedCount: 20,
      totalCount: 100,
      dependencies: [{ id: 'A' }],
      activity: 'idle',
      links: {
        import: {
          href: '#',
          title: 'Import',
          rel: '#'
        },
        rollback: {
          href: '#',
          title: 'Rollback',
          rel: '#'
        },
        rollbackAndImport: {
          href: '#',
          title: 'Rollback & Import',
          rel: '#'
        }
      }
    },
    {
      id: 'C',
      label: 'C',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'A' }, { id: 'B' }],
      activity: 'idle',
    },
    {
      id: 'D',
      label: 'D',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'B' }],
      activity: 'idle',
    },
  ],
  partialC: [
    {
      id: 'A',
      label: 'A',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'B'}],
      activity: 'idle',
    },
    {
      id: 'B',
      label: 'B',
      importedCount: 100,
      totalCount: 100,
      dependencies: [],
      activity: 'importing',
    },
  ],
  complete: [
    {
      id: 'A',
      label: 'A',
      importedCount: 100,
      totalCount: 100,
      dependencies: [],
      activity: 'idle',
      links: {
        rollback: {
          href: '#',
          title: 'Rollback',
          rel: '#'
        },
        rollbackAndImport: {
          href: '#',
          title: 'Rollback & Import',
          rel: '#'
        }
      }
    },
    {
      id: 'B',
      label: 'B',
      importedCount: 100,
      totalCount: 100,
      dependencies: [{ id: 'A' }],
      activity: 'idle',
      links: {
        rollback: {
          href: '#',
          title: 'Rollback',
          rel: '#'
        },
        rollbackAndImport: {
          href: '#',
          title: 'Rollback & Import',
          rel: '#'
        }
      }
    },
    {
      id: 'C',
      label: 'C',
      importedCount: 100,
      totalCount: 100,
      dependencies: [{ id: 'A' }, { id: 'B' }],
      activity: 'idle',
      links: {
        rollback: {
          href: '#',
          title: 'Rollback',
          rel: '#'
        },
        rollbackAndImport: {
          href: '#',
          title: 'Rollback & Import',
          rel: '#'
        }
      }
    },
    {
      id: 'D',
      label: 'D',
      importedCount: 100,
      totalCount: 100,
      dependencies: [{ id: 'B' }],
      activity: 'idle',
      links: {
        rollback: {
          href: '#',
          title: 'Rollback',
          rel: '#'
        },
        rollbackAndImport: {
          href: '#',
          title: 'Rollback & Import',
          rel: '#'
        }
      }
    },
  ],
  recursive1: [
    {
      id: 'A',
      label: 'A',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'B' }],
      activity: 'idle',
    },
    {
      id: 'B',
      label: 'B',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'A' }],
      activity: 'idle',
    },
    {
      id: 'C',
      label: 'C',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'A' }, { id: 'B' }],
      activity: 'idle',
    },
  ],
  recursive2: [
    {
      id: 'A',
      label: 'A',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'C' }],
      activity: 'idle',
    },
    {
      id: 'B',
      label: 'B',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'A' }],
      activity: 'idle',
    },
    {
      id: 'C',
      label: 'C',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'B' }],
      activity: 'idle',
    },
  ],
  recursive3: [
    {
      id: 'A',
      label: 'A',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'C' }],
      activity: 'idle',
    },
    {
      id: 'B',
      label: 'B',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'A' }],
      activity: 'idle',
    },
    {
      id: 'C',
      label: 'C',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'B' }],
      activity: 'idle',
    },
    {
      id: 'D',
      label: 'D',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'C' }, { id: 'B' }],
      activity: 'idle',
    },
  ],
  recursive4: [
    {
      id: 'A',
      label: 'A',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'D' }],
      activity: 'idle',
    },
    {
      id: 'B',
      label: 'B',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'A' }],
      activity: 'idle',
    },
    {
      id: 'C',
      label: 'C',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'B' }, { id: 'A' }],
      activity: 'idle',
    },
    {
      id: 'D',
      label: 'D',
      importedCount: 0,
      totalCount: 100,
      dependencies: [{ id: 'C' }],
      activity: 'idle',
    },
  ],
};
