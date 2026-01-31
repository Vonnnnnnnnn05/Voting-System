<?php
// Quick fix to sync server time with database
require_once 'config/config.php';
requireAdmin();

$db = getDB();

echo "<!DOCTYPE html><html><head><title>Time Sync</title></head><body>";
echo "<h2>Time Synchronization Fix</h2>";

// Get current timezone settings
echo "<h3>Current Settings</h3>";
echo "<p>PHP Timezone: " . date_default_timezone_get() . "</p>";
echo "<p>PHP Time: " . date('Y-m-d H:i:s') . "</p>";

try {
    $stmt = $db->query("SELECT @@global.time_zone, @@session.time_zone");
    $tz = $stmt->fetch();
    echo "<p>MySQL Global TZ: " . $tz['@@global.time_zone'] . "</p>";
    echo "<p>MySQL Session TZ: " . $tz['@@session.time_zone'] . "</p>";
    
    $stmt = $db->query("SELECT NOW() as now");
    echo "<p>MySQL NOW(): " . $stmt->fetch()['now'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Election Times</h3>";

$stmt = $db->query("SELECT id, title, start_date, end_date, NOW() as current_time FROM elections");
$elections = $stmt->fetchAll();

foreach ($elections as $election) {
    echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
    echo "<h4>" . htmlspecialchars($election['title']) . "</h4>";
    echo "<p>Current MySQL Time: <strong>" . $election['current_time'] . "</strong></p>";
    echo "<p>Start: " . $election['start_date'] . "</p>";
    echo "<p>End: " . $election['end_date'] . "</p>";
    
    $isActive = $election['current_time'] >= $election['start_date'] && $election['current_time'] <= $election['end_date'];
    echo "<p>Should be active? " . ($isActive ? '<span style="color:green;">YES</span>' : '<span style="color:red;">NO</span>') . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Actions</h3>";
echo "<p><a href='debug_time.php'>View Detailed Debug Info</a></p>";
echo "<p><a href='elections.php'>Back to Elections</a></p>";

echo "</body></html>";
?>
