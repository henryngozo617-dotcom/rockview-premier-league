<?php

header('Content-Type: application/json');

require_once '../db.php';

$sql = "
    SELECT
        gs.player_id,

        p.full_name,
        p.registration_number,
        p.position,
        p.jersey_number,
        p.photo,

        t.name AS team_name,
        t.short_name AS team_short_name,

        COUNT(DISTINCT gs.match_id) AS appearances,

        SUM(gs.minutes_played) AS minutes_played,

        SUM(gs.shots_on_target_faced) AS shots_on_target_faced,

        SUM(gs.saves) AS saves,

        SUM(gs.goals_conceded) AS goals_conceded,

        SUM(gs.clean_sheet) AS clean_sheets,

        SUM(gs.penalties_saved) AS penalties_saved,

        SUM(gs.expected_goals_on_target) AS expected_goals_on_target,

        (
            SUM(gs.expected_goals_on_target)
            -
            SUM(gs.goals_conceded)
        ) AS goals_prevented,

        CASE
            WHEN SUM(gs.shots_on_target_faced) > 0
            THEN
                (
                    SUM(gs.saves)
                    /
                    SUM(gs.shots_on_target_faced)
                ) * 100
            ELSE NULL
        END AS save_percentage

    FROM goalkeeper_stats gs

    INNER JOIN players p
        ON gs.player_id = p.id

    INNER JOIN teams t
        ON p.team_id = t.id

    WHERE
        p.position LIKE '%GK%'
        OR p.position LIKE '%Goalkeeper%'

    GROUP BY
        gs.player_id,
        p.full_name,
        p.registration_number,
        p.position,
        p.jersey_number,
        p.photo,
        t.name,
        t.short_name

    ORDER BY
        goals_prevented DESC,
        clean_sheets DESC,
        saves DESC
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

$goalkeepers = [];

while ($row = $result->fetch_assoc()) {

    $goalkeepers[] = $row;

}

echo json_encode([
    'success' => true,
    'count' => count($goalkeepers),
    'goalkeepers' => $goalkeepers
]);

?>