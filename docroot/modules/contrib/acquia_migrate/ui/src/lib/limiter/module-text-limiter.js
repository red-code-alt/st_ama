import TextLimiter from './text-limiter';

export default class ModuleTextLimiter extends TextLimiter {
  withValue(value) {
    return new ModuleTextLimiter({ name: this.name, value });
  }

  test(module) {
    const { id, title, version, replacementCandidates } = module;
    // Create a text blob of all the interesting properties.
    const replacements = replacementCandidates
      .reduce((text, candidate) => {
        const { category, id: candidateID, modules, note } = candidate;
        text = [...text, category, candidateID, note];
        if (modules) {
          text = [
            ...text,
            ...modules.reduce((candidateModules, candidateModule) => {
              candidateModules = [
                ...candidateModules,
                candidateModule.displayName,
                candidateModule.machineName,
              ];
              return candidateModules;
            }, []),
          ];
        }

        return text;
      }, [])
      .filter((textItem) => textItem)
      .join(' ');
    return super.test([id, title, version, replacements]);
  }
}
