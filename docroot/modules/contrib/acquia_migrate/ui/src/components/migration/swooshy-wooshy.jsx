import React from 'react';
import PropTypes from 'prop-types';
import { useSpring, animated } from 'react-spring';

const SwooshyWooshy = ({ active, amount }) => {
  const activeClass = active ? 'swooshy--is-swooshing' : '';
  const props = useSpring({ amount });
  const offset = parseInt(amount, 10);
  const transform = `translateY(-${offset < 100 ? offset : 100}%)`;
  return (
    <div className={`swooshy ${activeClass}`}>
      <div className="swooshy__inner">
        <div className="swooshy__fill" style={{ transform }} />
        <div className="swooshy__amount">
          <animated.span>
            {props.amount.interpolate((val) => parseFloat(val).toFixed(2))}
          </animated.span>
          %
        </div>
      </div>
    </div>
  );
};

export default SwooshyWooshy;

SwooshyWooshy.propTypes = {
  active: PropTypes.bool,
  amount: PropTypes.string,
};

SwooshyWooshy.defaultProps = {
  active: false,
  amount: '0.00',
};
