import BoolLimiter from './bool-limiter';

export default class ModuleVettedLimiter extends BoolLimiter {
  withValue(value) {
    return new ModuleVettedLimiter({ name: this.name, value });
  }

  test(module) {
    const { replacementCandidates } = module;
    const vetted = replacementCandidates
      ? replacementCandidates.some((candidate) => candidate.vetted)
      : false;
    return super.test(vetted);
  }
}
