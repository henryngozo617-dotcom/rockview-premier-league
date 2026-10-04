<?php

require_once "../db.php";

header("Content-Type: application/json");

$sql = "
    SELECT
        players.id,
        players.registration_number,
        players.full_name,
        players.position,
        players.jersey_number,
        players.photo,
        players.status,
        teams.id AS team_id,
        teams.name AS team_name,
        teams.short_name AS team_short_name
    FROM players
    INNER JOIN teams
        ON players.team_id = teams.id
    ORDER BY players.id DESC
";

$result = $conn->query($sql);

$players = [];

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }

}

echo json_encode($players);

$conn->close();

?>