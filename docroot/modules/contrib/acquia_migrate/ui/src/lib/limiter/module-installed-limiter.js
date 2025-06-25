import BoolLimiter from './bool-limiter';

export default class ModuleInstalledLimiter extends BoolLimiter {
  withValue(value) {
    return new ModuleInstalledLimiter({ name: this.name, value });
  }

  test(module) {
    const { replacementCandidates } = module;
    const installed = replacementCandidates
      ? replacementCandidates.some(
          (replacement) =>
            replacement.modules &&
            replacement.modules.some(
              (replacementModule) => !!replacementModule.installed,
            ),
        )
      : false;
    return super.test(installed);
  }
}
