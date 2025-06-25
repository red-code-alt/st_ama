import Limiter from './limiter';

export default class BoolLimiter extends Limiter {
  test(value) {
    return !this.value || !!value;
  }
}
