import { useState } from 'react';

const useToggle = (initialBoolean) => {
  const [toggled, setToggled] = useState(initialBoolean);
  return [
    toggled,
    () => {
      setToggled(!toggled);
    },
  ];
};

export default useToggle;
