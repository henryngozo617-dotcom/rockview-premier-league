<?php

header('Content-Type: application/json');

require_once '../db.php';

$sql = "
    SELECT
        id,
        name,
        short_name
    FROM teams
    ORDER BY name ASC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Database query failed',
        'error' => $conn->error
    ]);
    exit;
}

$teams = [];

while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}

echo json_encode([
    'success' => true,
    'count' => count($teams),
    'teams' => $teams
]);

?>