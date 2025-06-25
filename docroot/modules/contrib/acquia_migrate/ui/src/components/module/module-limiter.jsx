import React from 'react';

import ClaroTextInput from '../claro/text-input';
import ClaroCheckbox from '../claro/checkbox';

const ModuleLimiter = ({ limiters, update }) => {
  const { searchText, vettedOnly, installedOnly } = limiters;
  // Add a form to let the client-side validation appear.
  const handleSubmit = (e) => {
    e.preventDefault();
  };

  const handleTextChange = (e) => {
    update(searchText.name, e.target.value);
  };

  return (
    <div className="module_limiter">
      <form onSubmit={handleSubmit}>
        <ClaroTextInput
          name={searchText.name}
          label="Search for text"
          value={searchText.value}
          onChange={handleTextChange}
          type="search"
          validation={{ minLength: '3' }}
        />
        <ClaroCheckbox
          name={vettedOnly.name}
          label="Show only modules with an Acquia-vetted migration path"
          checked={vettedOnly.value}
          toggle={() => {
            update(vettedOnly.name, !vettedOnly.value);
          }}
        />
        <ClaroCheckbox
          name={installedOnly.name}
          label="Show only installed modules"
          checked={installedOnly.value}
          toggle={() => {
            update(installedOnly.name, !installedOnly.value);
          }}
        />
      </form>
    </div>
  );
};

export default ModuleLimiter;
