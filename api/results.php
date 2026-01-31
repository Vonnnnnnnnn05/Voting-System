<?php
/**
 * API: Election Results (JSON)
 * Returns voting results for Chart.js visualization
 */

require_once '../config/config.php';

header('Content-Type: application/json');

// Get election ID
$electionId = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

if (!$electionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Election ID required']);
    exit;
}

try {
    $db = getDB();
    
    // Verify election exists
    $stmt = $db->prepare("SELECT id, title FROM elections WHERE id = :id");
    $stmt->execute([':id' => $electionId]);
    $election = $stmt->fetch();
    
    if (!$election) {
        http_response_code(404);
        echo json_encode(['error' => 'Election not found']);
        exit;
    }
    
    // Fetch voting results
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.description,
               COUNT(v.id) as vote_count
        FROM candidates c
        LEFT JOIN votes v ON c.id = v.candidate_id
        WHERE c.election_id = :election_id
        GROUP BY c.id
        ORDER BY vote_count DESC, c.name ASC
    ");
    
    $stmt->execute([':election_id' => $electionId]);
    $results = $stmt->fetchAll();
    
    // Prepare data for Chart.js
    $labels = [];
    $votes = [];
    $colors = [];
    
    $colorPalette = [
        'rgba(54, 162, 235, 0.8)',
        'rgba(255, 99, 132, 0.8)',
        'rgba(75, 192, 192, 0.8)',
        'rgba(255, 206, 86, 0.8)',
        'rgba(153, 102, 255, 0.8)',
        'rgba(255, 159, 64, 0.8)',
        'rgba(199, 199, 199, 0.8)',
        'rgba(83, 102, 255, 0.8)'
    ];
    
    $colorIndex = 0;
    foreach ($results as $result) {
        $labels[] = $result['name'];
        $votes[] = (int)$result['vote_count'];
        $colors[] = $colorPalette[$colorIndex % count($colorPalette)];
        $colorIndex++;
    }
    
    // Calculate total votes
    $totalVotes = array_sum($votes);
    
    // Prepare response
    $response = [
        'success' => true,
        'election' => [
            'id' => $election['id'],
            'title' => $election['title']
        ],
        'labels' => $labels,
        'votes' => $votes,
        'colors' => $colors,
        'totalVotes' => $totalVotes,
        'candidateCount' => count($results)
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
