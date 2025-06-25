export default class Limiter {
  constructor({ name, value }) {
    this.name = name;
    this.value = value;
  }

  update(value) {
    return new this({ name: this.name, value });
  }
}
