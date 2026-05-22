<?php
/*********************************************************************
    ajax.notifications.php

    AJAX interface for real-time notifications polling.
    Returns recent ticket events (created, replied, updated, etc.)

    Custom addition for osTicket notification system.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR . 'class.ajax.php';

if(!defined('INCLUDE_DIR')) die('!');

class NotificationsAjaxAPI extends AjaxController {

    /**
     * GET /notifications/poll
     *
     * Polls for recent ticket events since a given timestamp.
     * Returns JSON array of notification objects.
     *
     * Query params:
     *   since - Unix timestamp of last poll (default: 5 minutes ago)
     */
    function poll() {
        global $thisstaff, $cfg;

        if (!$thisstaff)
            Http::response(403, 'Login required');

        // Get the 'since' timestamp from query params, default to 5 min ago
        $since = (isset($_GET['since']) && $_GET['since'] > 0) ? intval($_GET['since']) : (time() - 300);

        // Convert to MySQL datetime in UTC
        $since_dt = date('Y-m-d H:i:s', $since);

        // Query recent thread events that are NOT viewed events
        // and join with tickets to get ticket info
        $sql = "SELECT
                    te.id as event_id,
                    te.thread_id,
                    te.timestamp,
                    te.username,
                    te.uid,
                    te.uid_type,
                    te.data,
                    e.name as event_name,
                    t.ticket_id,
                    t.number as ticket_number,
                    tc.subject as ticket_subject,
                    td.name as dept_name
                FROM " . THREAD_EVENT_TABLE . " te
                JOIN " . EVENT_TABLE . " e ON e.id = te.event_id
                JOIN " . THREAD_TABLE . " th ON th.id = te.thread_id
                LEFT JOIN " . TICKET_TABLE . " t ON t.ticket_id = th.object_id AND th.object_type = 'T'
                LEFT JOIN " . TICKET_CDATA_TABLE . " tc ON tc.ticket_id = t.ticket_id
                LEFT JOIN " . DEPT_TABLE . " td ON td.id = te.dept_id
                WHERE te.timestamp > FROM_UNIXTIME(" . db_input($since) . ")
                AND e.name IN ('created', 'closed', 'reopened', 'assigned', 'transferred', 'overdue', 'edited')
                AND te.annulled = 0
                AND t.ticket_id IS NOT NULL
                ORDER BY te.timestamp DESC
                LIMIT 50";

        $notifications = array();
        if (($res = db_query($sql))) {
            while ($row = db_fetch_array($res)) {
                $notifications[] = array(
                    'id'             => (int) $row['event_id'],
                    'event'          => $row['event_name'],
                    'ticket_id'      => (int) $row['ticket_id'],
                    'ticket_number'  => $row['ticket_number'],
                    'ticket_subject' => $row['ticket_subject'] ?: __('(No Subject)'),
                    'dept'           => $row['dept_name'] ?: '',
                    'username'       => $row['username'],
                    'timestamp'      => $row['timestamp'],
                    'unix_ts'        => strtotime($row['timestamp']),
                );
            }
        }

        // Also check for new thread entries (messages/replies) since the timestamp
        $sql2 = "SELECT
                    entry.id as entry_id,
                    entry.thread_id,
                    entry.type,
                    entry.poster,
                    entry.created,
                    entry.staff_id,
                    entry.user_id,
                    t.ticket_id,
                    t.number as ticket_number,
                    tc.subject as ticket_subject
                FROM " . THREAD_ENTRY_TABLE . " entry
                JOIN " . THREAD_TABLE . " th ON th.id = entry.thread_id
                LEFT JOIN " . TICKET_TABLE . " t ON t.ticket_id = th.object_id AND th.object_type = 'T'
                LEFT JOIN " . TICKET_CDATA_TABLE . " tc ON tc.ticket_id = t.ticket_id
                WHERE entry.created > FROM_UNIXTIME(" . db_input($since) . ")
                AND entry.type IN ('M', 'R')
                AND t.ticket_id IS NOT NULL
                ORDER BY entry.created DESC
                LIMIT 50";

        if (($res2 = db_query($sql2))) {
            while ($row = db_fetch_array($res2)) {
                $event_type = ($row['type'] == 'M') ? 'message' : 'reply';

                $notifications[] = array(
                    'id'             => 'entry_' . $row['entry_id'],
                    'event'          => $event_type,
                    'ticket_id'      => (int) $row['ticket_id'],
                    'ticket_number'  => $row['ticket_number'],
                    'ticket_subject' => $row['ticket_subject'] ?: __('(No Subject)'),
                    'dept'           => '',
                    'username'       => $row['poster'],
                    'timestamp'      => $row['created'],
                    'unix_ts'        => strtotime($row['created']),
                );
            }
        }

        // Sort by timestamp descending and remove duplicates
        usort($notifications, function($a, $b) {
            return $b['unix_ts'] - $a['unix_ts'];
        });

        // Deduplicate - use composite key of event+ticket_id
        $seen = array();
        $unique = array();
        foreach ($notifications as $n) {
            $key = $n['event'] . '_' . $n['ticket_id'] . '_' . $n['unix_ts'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $n;
            }
        }

        $result = array(
            'notifications' => array_values(array_slice($unique, 0, 20)),
            'server_time'   => time(),
            'count'         => count($unique),
        );

        return $this->json_encode($result);
    }

    /**
     * GET /notifications/count
     *
     * Returns the count of unread/recent notifications.
     */
    function count() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');

        // Count events in the last hour
        $since = date('Y-m-d H:i:s', time() - 3600);

        $sql = "SELECT COUNT(*) as cnt
                FROM " . THREAD_EVENT_TABLE . " te
                JOIN " . EVENT_TABLE . " e ON e.id = te.event_id
                JOIN " . THREAD_TABLE . " th ON th.id = te.thread_id
                LEFT JOIN " . TICKET_TABLE . " t ON t.ticket_id = th.object_id AND th.object_type = 'T'
                WHERE te.timestamp > FROM_UNIXTIME(" . db_input($since) . ")
                AND e.name IN ('created', 'closed', 'reopened', 'assigned', 'transferred', 'overdue')
                AND te.annulled = 0
                AND t.ticket_id IS NOT NULL
                AND NOT (te.uid_type = 'S' AND te.uid = " . db_input($thisstaff->getId()) . ")";

        $count = 0;
        if (($res = db_query($sql)) && ($row = db_fetch_array($res)))
            $count = (int) $row['cnt'];

        return $this->json_encode(array('count' => $count));
    }
}
?>
