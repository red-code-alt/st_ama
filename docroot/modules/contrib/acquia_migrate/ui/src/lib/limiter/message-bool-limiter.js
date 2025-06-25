import BoolLimiter from './bool-limiter';

export default class MessageBoolLimiter extends BoolLimiter {
  withValue(value) {
    return new MessageBoolLimiter({ name: this.name, value });
  }

  test(message) {
    return super.test(message.solution);
  }
}
