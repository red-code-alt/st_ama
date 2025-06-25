import React from 'react';
import { Link } from '@reach/router';

const ClaroNavItem = (props) => (
  <li className="tabs__tab js-tab">
    <Link
      getProps={({ href, location }) => ({
        className: `tabs__link js-tabs-link ${
          href === location.pathname ? 'is-active' : ''
        }`,
      })}
      {...props}
    />
  </li>
);

export default ClaroNavItem;
