import React, { useContext } from 'react';

import RegionHeader from '../regions/header';
import MessageView from '../components/message/message-view';
import MessageFilters from '../components/message/message-filters';
import MessageLimiter from '../components/message/message-limiter';

import useOffline from '../hooks/use-offline';

import ExtLink from '../components/ext-link';
import ClaroBreadcrumb from '../components/claro/breadcrumb';
import ClaroBreadcrumbItem from '../components/claro/breadcrumb-item';
import ClaroHeader from '../components/claro/header';
import { MessagesContext } from '../contexts/messages';

/**
 * Migration messages page.
 *
 * @return {ReactNode}
 *   <MessagesList />
 */
const MessagesList = () => {
  const { basepathDashboard } = useContext(MessagesContext);
  // Use offline merely to ensure that ClaroHeader conveys this.
  const offline = useOffline();
  return (
    <div className="region-content">
      <RegionHeader>
        <ClaroBreadcrumb>
          <ClaroBreadcrumbItem>
            <ExtLink href="../../" title="Home">
              Home
            </ExtLink>
          </ClaroBreadcrumbItem>
          <ClaroBreadcrumbItem>
            <ExtLink href={basepathDashboard} title="Migrations">
              Migrations
            </ExtLink>
          </ClaroBreadcrumbItem>
          <ClaroBreadcrumbItem>
            <span>Messages</span>
          </ClaroBreadcrumbItem>
        </ClaroBreadcrumb>
        <ClaroHeader title="Messages" />
      </RegionHeader>
      <div>
        <MessageFilters />
        <MessageLimiter />
      </div>
      <div>
        <MessageView />
      </div>
    </div>
  );
};

export default MessagesList;
