import Limiter from './limiter';

import { searchTextFields } from '../utils';

export default class TextLimiter extends Limiter {
  test(values) {
    return searchTextFields(this.value, values);
  }
}
