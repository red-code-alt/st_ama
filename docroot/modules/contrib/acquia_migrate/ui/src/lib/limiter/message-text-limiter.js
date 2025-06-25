import TextLimiter from './text-limiter';

export default class MessageTextLimiter extends TextLimiter {
  withValue(value) {
    return new MessageTextLimiter({ name: this.name, value });
  }

  test(message) {
    return super.test([
      message.migration,
      message.plugin,
      message.text,
      message.solution,
    ]);
  }
}
