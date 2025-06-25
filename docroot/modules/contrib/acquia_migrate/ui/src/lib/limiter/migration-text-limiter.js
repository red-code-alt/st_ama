import TextLimiter from './text-limiter';

export default class MigrationTextLimiter extends TextLimiter {
  withValue(value) {
    return new MigrationTextLimiter({ name: this.name, value });
  }

  test(migration) {
    return super.test([migration.label]);
  }
}
