<?php
require_once 'main.inc.php';

$since = time() - 86400; // Look back 1 day for testing
$since_dt = date('Y-m-d H:i:s', $since);

echo "Testing Notifications Query\n";
echo "Since: $since_dt\n";

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
        WHERE te.timestamp > " . db_input($since_dt) . "
        AND e.name IN ('created', 'closed', 'reopened', 'assigned', 'transferred', 'overdue', 'edited')
        AND te.annulled = 0
        AND t.ticket_id IS NOT NULL
        ORDER BY te.timestamp DESC
        LIMIT 5";

echo "SQL: $sql\n";

$res = db_query($sql);
if (!$res) {
    echo "Query failed: " . db_error() . "\n";
} else {
    echo "Rows found: " . db_num_rows($res) . "\n";
    while ($row = db_fetch_array($res)) {
        print_r($row);
    }
}
?>
