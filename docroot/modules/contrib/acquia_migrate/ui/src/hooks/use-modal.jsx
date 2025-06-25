import { useState } from 'react';

const useModal = () => {
  const [modal, setModal] = useState(null);

  const showModal = (title, message, action) => {
    setModal({
      title,
      message,
      action: () => {
        action();
        setModal(null);
      },
      cancel: () => setModal(null),
    });
  };

  return { modal, showModal };
};

export default useModal;
