import React from 'react';
import PropTypes from 'prop-types';

import { useSpring, animated } from 'react-spring';

const AnimatedNumber = ({ value, className }) => {
  const props = useSpring({ value });
  return (
    <animated.span className={`tabular ${className}`}>
      {props.value.interpolate(Math.round)}
    </animated.span>
  );
};

export default AnimatedNumber;

AnimatedNumber.propTypes = {
  value: PropTypes.oneOfType([PropTypes.number, PropTypes.string]).isRequired,
  className: PropTypes.string,
};

AnimatedNumber.defaultProps = {
  className: '',
};
