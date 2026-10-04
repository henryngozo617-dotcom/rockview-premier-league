<?php

header("Content-Type: application/json");

require_once "../db.php";

try {

    $sql = "
        SELECT
            m.id,
            m.home_team_id,
            m.away_team_id,
            m.match_date,
            m.match_time,
            m.venue,
            m.home_score,
            m.away_score,
            m.status,

            home.name AS home_team,
            away.name AS away_team

        FROM matches m

        INNER JOIN teams home
            ON m.home_team_id = home.id

        INNER JOIN teams away
            ON m.away_team_id = away.id

        ORDER BY
            m.match_date ASC,
            m.id ASC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $matches = [];

    while ($row = $result->fetch_assoc()) {

        $matchDate = $row["match_date"];

        /*
         * RPL MATCHDAY DATES
         */

        $matchdays = [
            "2026-09-22" => 1,
            "2026-09-23" => 2,
            "2026-09-24" => 3,
            "2026-09-25" => 4,
            "2026-09-26" => 5,
            "2026-09-27" => 6,
            "2026-09-29" => 7,
            "2026-09-30" => 8,
            "2026-10-01" => 9,
            "2026-10-02" => 10,
            "2026-10-03" => 11,
            "2026-10-04" => 12,
            "2026-10-06" => 13,
            "2026-10-07" => 14,
            "2026-10-08" => 15,
            "2026-10-09" => 16,
            "2026-10-10" => 17,
            "2026-10-11" => 18,
            "2026-10-13" => 19,
            "2026-10-14" => 20,
            "2026-10-15" => 21,
            "2026-10-16" => 22
        ];

        $matchday = $matchdays[$matchDate] ?? null;

        $matches[] = [
            "id" => (int)$row["id"],

            "matchday" => $matchday,

            "home_team_id" => (int)$row["home_team_id"],
            "away_team_id" => (int)$row["away_team_id"],

            "home_team" => $row["home_team"],
            "away_team" => $row["away_team"],

            "match_date" => $row["match_date"],
            "match_time" => $row["match_time"],
            "venue" => $row["venue"],

            "home_score" => $row["home_score"] !== null
                ? (int)$row["home_score"]
                : null,

            "away_score" => $row["away_score"] !== null
                ? (int)$row["away_score"]
                : null,

            "status" => $row["status"]
        ];
    }

    echo json_encode([
        "success" => true,
        "count" => count($matches),
        "matches" => $matches
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

?>