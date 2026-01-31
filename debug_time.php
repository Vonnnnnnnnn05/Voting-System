<?php
require_once 'config/config.php';
requireLogin();

$db = getDB();

// Get all elections
$stmt = $db->query("SELECT * FROM elections ORDER BY start_date DESC");
$elections = $stmt->fetchAll();

echo "<!DOCTYPE html><html><head><title>Time Debug</title></head><body>";
echo "<h2>Election Time Debugging</h2>";

echo "<h3>Current Server Time</h3>";
echo "<p><strong>PHP time():</strong> " . time() . "</p>";
echo "<p><strong>PHP date():</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>PHP timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>MySQL NOW():</strong> ";
try {
    $stmt = $db->query("SELECT NOW() as now");
    echo $stmt->fetch()['now'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
echo "</p>";

echo "<hr>";
echo "<h3>Elections Time Analysis</h3>";

foreach ($elections as $election) {
    echo "<div style='border:1px solid #ccc; padding:15px; margin:10px 0;'>";
    echo "<h4>" . htmlspecialchars($election['title']) . "</h4>";
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    
    echo "<tr><td>Start Date (DB)</td><td>" . $election['start_date'] . "</td></tr>";
    echo "<tr><td>End Date (DB)</td><td>" . $election['end_date'] . "</td></tr>";
    
    $startTimestamp = strtotime($election['start_date']);
    $endTimestamp = strtotime($election['end_date']);
    $nowTimestamp = time();
    
    echo "<tr><td>Start Timestamp</td><td>" . $startTimestamp . "</td></tr>";
    echo "<tr><td>End Timestamp</td><td>" . $endTimestamp . "</td></tr>";
    echo "<tr><td>Now Timestamp</td><td>" . $nowTimestamp . "</td></tr>";
    
    echo "<tr><td>Now < Start?</td><td>" . ($nowTimestamp < $startTimestamp ? 'YES (upcoming)' : 'NO') . "</td></tr>";
    echo "<tr><td>Now > End?</td><td>" . ($nowTimestamp > $endTimestamp ? 'YES (ended)' : 'NO') . "</td></tr>";
    echo "<tr><td>Start <= Now <= End?</td><td>" . (($nowTimestamp >= $startTimestamp && $nowTimestamp <= $endTimestamp) ? 'YES (active)' : 'NO') . "</td></tr>";
    
    $status = getElectionStatus($election['start_date'], $election['end_date']);
    echo "<tr><td><strong>Calculated Status</strong></td><td><strong>" . strtoupper($status) . "</strong></td></tr>";
    
    echo "<tr><td>Time until start</td><td>" . round(($startTimestamp - $nowTimestamp) / 60, 2) . " minutes</td></tr>";
    echo "<tr><td>Time until end</td><td>" . round(($endTimestamp - $nowTimestamp) / 60, 2) . " minutes</td></tr>";
    
    echo "</table>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='voter/elections.php'>Back to Elections</a></p>";
echo "</body></html>";
?>
