<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

// Only allow admins
if (!isAdmin()) {
    die("Access denied. Admin only.");
}

$db = getDB();

echo "<!DOCTYPE html><html><head><title>Auto-Fix Elections</title>
<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
</style>
</head><body>";

echo "<h1>🔧 Auto-Fix Elections Timing</h1>";
echo "<p class='info'>Current Server Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Get all elections
$stmt = $db->query("
    SELECT id, title, start_date, end_date,
    NOW() as server_time,
    CASE 
        WHEN NOW() < start_date THEN 'upcoming'
        WHEN NOW() > end_date THEN 'ended'
        ELSE 'active'
    END as status
    FROM elections
    ORDER BY created_at DESC
");
$elections = $stmt->fetchAll();

if (empty($elections)) {
    echo "<p class='error'>No elections found!</p>";
    echo "<p><a href='admin/elections.php'>Create an election first</a></p>";
} else {
    $fixed = 0;
    
    foreach ($elections as $election) {
        echo "<h3>Election: " . htmlspecialchars($election['title']) . "</h3>";
        echo "<p>ID: {$election['id']}</p>";
        echo "<p>Current Status: <strong>{$election['status']}</strong></p>";
        echo "<p>Start: {$election['start_date']}</p>";
        echo "<p>End: {$election['end_date']}</p>";
        echo "<p>Server Time: {$election['server_time']}</p>";
        
        // If election is upcoming or ended, make it active
        if ($election['status'] !== 'active') {
            // Set start time to 5 minutes ago, end time to 2 hours from now
            $newStart = date('Y-m-d H:i:s', strtotime('-5 minutes'));
            $newEnd = date('Y-m-d H:i:s', strtotime('+2 hours'));
            
            $updateStmt = $db->prepare("
                UPDATE elections 
                SET start_date = :start, end_date = :end
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':start' => $newStart,
                ':end' => $newEnd,
                ':id' => $election['id']
            ]);
            
            echo "<p class='success'>✓ FIXED! New times:</p>";
            echo "<p>Start: {$newStart}</p>";
            echo "<p>End: {$newEnd}</p>";
            
            $fixed++;
        } else {
            echo "<p class='success'>✓ Already active - no fix needed</p>";
        }
        
        echo "<hr>";
    }
    
    echo "<h2>Summary</h2>";
    echo "<p class='success'>Fixed {$fixed} election(s)</p>";
    echo "<p><a href='voter/elections.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Elections Page</a></p>";
    echo "<p><a href='admin/edit_times.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Manual Time Editor</a></p>";
}

echo "</body></html>";
?>
