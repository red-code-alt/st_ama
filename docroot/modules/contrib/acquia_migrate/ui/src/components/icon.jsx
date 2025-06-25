import React from 'react';
import PropTypes from 'prop-types';

const IconSprite = ({ icon }) => {
  switch (icon) {
    case 'alert-triangle':
      return (
        <>
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
          <line x1="12" y1="9" x2="12" y2="13" />
          <line x1="12" y1="17" x2="12.01" y2="17" />
        </>
      );
    case 'cancel':
      return (
        <>
          <path strokeWidth="1.5" d="M7 17L17 7" />
          <path strokeWidth="1.5" d="M7 7L17 17" />
          <circle strokeWidth="1.5" cx="12" cy="12" r="7.25" />
        </>
      );
    case 'check-circle':
      return (
        <>
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
          <polyline points="22 4 12 14.01 9 11.01" />
        </>
      );
    case 'x-circle':
      return (
        <>
          <circle cx="12" cy="12" r="10" />
          <line x1="15" y1="9" x2="9" y2="15" />
          <line x1="9" y1="9" x2="15" y2="15" />
        </>
      );
    case 'minus-circle':
      return (
        <>
          <circle cx="12" cy="12" r="10" />
          <line x1="8" y1="12" x2="16" y2="12" />
        </>
      );
    case 'circle':
      return <circle strokeWidth="1.5" cx="12" cy="12" r="7.25" />;
    case 'clock':
      return (
        <>
          <circle cx="12" cy="12" r="10" />
          <polyline points="12 6 12 12 16 14" />
        </>
      );
    case 'chevron-down':
      return <polyline points="6 9 12 15 18 9" />;
    case 'delete':
      return (
        <>
          <path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z" />
          <line x1="18" y1="9" x2="12" y2="15" />
          <line x1="12" y1="9" x2="18" y2="15" />
        </>
      );
    case 'expander':
      return (
        <>
          <path
            fillRule="evenodd"
            clipRule="evenodd"
            d="M23 0H18V1H23V6H24V1V0H23Z"
            fill="#d4d4d4"
          />
          <path
            fillRule="evenodd"
            clipRule="evenodd"
            d="M13.9991 2L20.999 2L21.999 2H21.9991V2.70423L22.0044 2.70955L21.9991 2.71486L21.9991 3H21.999L21.999 9.99366L20.999 9.99366L20.999 3.71413L14.107 10.6007L13.3999 9.89413L20.2995 3L13.9991 3V2Z"
            fill="#333333"
            className="expander_arrow expander_arrow--up"
          />
          <path
            fillRule="evenodd"
            clipRule="evenodd"
            d="M1 24L6 24L6 23L1 23L1 18L5.24537e-07 18L8.74228e-08 23L0 24L1 24Z"
            fill="#d4d4d4"
          />
          <path
            fillRule="evenodd"
            clipRule="evenodd"
            d="M10.0009 22L3.00098 22L2.00098 22L2.00092 22L2.00092 21.2958L1.9956 21.2905L2.00092 21.2851L2.00092 21L2.00098 21L2.00098 14.0063L3.00098 14.0063L3.00098 20.2859L9.89299 13.3993L10.6001 14.1059L3.7005 21L10.0009 21L10.0009 22Z"
            fill="#333333"
            className="expander_arrow expander_arrow--dn"
          />
        </>
      );
    case 'eye':
      return (
        <>
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
          <circle cx="12" cy="12" r="3" />
        </>
      );
    case 'eye-off':
      return (
        <>
          <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
          <line x1="1" y1="1" x2="23" y2="23" />
        </>
      );
    case 'loader':
      return (
        <>
          <line x1="12" y1="2" x2="12" y2="6" />
          <line x1="12" y1="18" x2="12" y2="22" />
          <line x1="4.93" y1="4.93" x2="7.76" y2="7.76" />
          <line x1="16.24" y1="16.24" x2="19.07" y2="19.07" />
          <line x1="2" y1="12" x2="6" y2="12" />
          <line x1="18" y1="12" x2="22" y2="12" />
          <line x1="4.93" y1="19.07" x2="7.76" y2="16.24" />
          <line x1="16.24" y1="7.76" x2="19.07" y2="4.93" />
        </>
      );
    case 'more-vertical':
      return (
        <>
          <circle cx="12" cy="12" r="1" />
          <circle cx="12" cy="5" r="1" />
          <circle cx="12" cy="19" r="1" />
        </>
      );
    case 'pause':
      return (
        <>
          <rect x="6" y="4" width="4" height="16" />
          <rect x="14" y="4" width="4" height="16" />
        </>
      );
    case 'play':
      return <polygon points="5 3 19 12 5 21 5 3" />;
    case 'square':
      return <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />;
    case 'zap':
      return <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />;
    case 'vetted':
      return (
        <path
          fill="#27aae1"
          d="M18.3888 19.1322C16.4894 21.1519 14.4095 21.9533 11.9999 22C6.70586 22 3 17.05 3 12.43C3 7.4941 6.04497 5.15044 8.49991 3.26092C9.92939 2.16068 11.1588 1.21441 11.4705 0C11.8556 1.24007 13.2208 2.11647 14.8024 3.13175C16.0706 3.9459 17.478 4.84936 18.6307 6.10127L11.9465 13.0454L7.96797 8.91211L5.64725 11.3231L9.6258 15.4564L9.58571 15.498L11.9064 17.909L20.4422 9.04124C20.7939 10.01 20.9999 11.1264 20.9999 12.43C21.011 14.9337 20.2882 17.1125 18.3888 19.1322Z"
        />
      );
    case 'abandoned':
      return (
        <path
          fill="#c4c4c4"
          d="M12 2C7.02942 2 3 6.10404 3 11.1667V17.6241C3.02022 18.4527 3.65298 19.3328 4.42302 19.5943L7.5 20.6389V22.4722C7.5 23.3125 8.175 24 9 24H15C15.825 24 16.5 23.3125 16.5 22.4722V20.6389L19.577 19.5943C20.347 19.3328 20.9788 18.4526 20.999 17.6241L21 17.5833V11.1667C21 6.10404 16.9706 2 12 2ZM15.3 11.7778C16.7912 11.7778 18 13.0089 18 14.5278C18 16.0466 16.7912 17.2778 15.3 17.2778C13.8088 17.2778 12.6 16.0466 12.6 14.5278C12.6 13.0089 13.8088 11.7778 15.3 11.7778ZM8.7 11.7778C10.1912 11.7778 11.4 13.0089 11.4 14.5278C11.4 16.0466 10.1912 17.2778 8.7 17.2778C7.20876 17.2778 6 16.0466 6 14.5278C6 13.0089 7.20876 11.7778 8.7 11.7778ZM13.2 19.1111C13.2 19.7861 12.6628 20.3333 12 20.3333C11.3372 20.3333 10.8 19.7861 10.8 19.1111C10.8 18.4361 12 16.6667 12 16.6667C12 16.6667 13.2 18.4361 13.2 19.1111Z"
        />
      );
  }
};

const Icon = ({ icon, size, fill, stroke, strokeWidth }) => {
  return (
    <svg
      viewBox="0 0 24 24"
      width={`${size}px`}
      height={`${size}px`}
      fill={fill}
      stroke={stroke}
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      focusable="false"
    >
      <IconSprite icon={icon} />
    </svg>
  );
};

export default Icon;

IconSprite.propTypes = {
  icon: PropTypes.string.isRequired,
};

Icon.propTypes = {
  icon: PropTypes.string.isRequired,
  size: PropTypes.string,
  fill: PropTypes.string,
  stroke: PropTypes.string,
  strokeWidth: PropTypes.string,
};

Icon.defaultProps = {
  size: '24',
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: '2',
};
