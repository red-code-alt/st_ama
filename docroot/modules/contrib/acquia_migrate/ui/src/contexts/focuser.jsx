import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { useSpring } from 'react-spring';

/**
 * @type {React.Context<{}>}
 */
const FocuserContext = React.createContext();

const FocuserProvider = ({ parentRef, children }) => {
  const top = parentRef.current ? parentRef.current.scrollTop : 0;
  const [focused, setFocused] = useState('');
  const [, setOffset] = useSpring(() => ({
    offset: 0,
    from: { offset: 0 },
    reset: true,
    onFrame: ({ offset }) => {
      if (parentRef.current) {
        parentRef.current.scrollTo(0, offset);
      }
    },
  }));

  const isChildFocused = (id) => focused === id;

  const focusChild = (id) => {
    setFocused(id);
    setTimeout(() => {
      setFocused('');
    }, 1000);
  };

  const scrollParent = (pos) => {
    setOffset({
      from: { offset: top },
      offset: pos,
    });
  };

  return (
    <FocuserContext.Provider
      value={{ scrollParent, isChildFocused, focusChild }}
    >
      {children}
    </FocuserContext.Provider>
  );
};

export { FocuserContext, FocuserProvider };

FocuserProvider.propTypes = {
  parentRef: PropTypes.shape({
    current: PropTypes.instanceOf(Element),
  }).isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};
