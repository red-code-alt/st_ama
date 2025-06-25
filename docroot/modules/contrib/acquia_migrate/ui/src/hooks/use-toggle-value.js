import { useState } from 'react';

const useToggleValue = () => {
  const [value, setValue] = useState(null);
  const isValue = (toggle) => toggle === value;
  const toggleValue = (toggle) => {
    if (isValue(toggle)) {
      setValue(null);
    } else {
      setValue(toggle);
    }
  };

  return [isValue, toggleValue];
};

export default useToggleValue;
