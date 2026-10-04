<?php

header("Content-Type: application/json");

require_once "../db.php";

try {

    $sql = "
        SELECT
            t.id,
            t.name AS team_name,

            COUNT(m.id) AS played,

            SUM(
                CASE
                    WHEN m.home_team_id = t.id
                         AND m.home_score > m.away_score THEN 1

                    WHEN m.away_team_id = t.id
                         AND m.away_score > m.home_score THEN 1

                    ELSE 0
                END
            ) AS wins,

            SUM(
                CASE
                    WHEN m.home_team_id = t.id
                         AND m.home_score = m.away_score THEN 1

                    WHEN m.away_team_id = t.id
                         AND m.away_score = m.home_score THEN 1

                    ELSE 0
                END
            ) AS draws,

            SUM(
                CASE
                    WHEN m.home_team_id = t.id
                         AND m.home_score < m.away_score THEN 1

                    WHEN m.away_team_id = t.id
                         AND m.away_score < m.home_score THEN 1

                    ELSE 0
                END
            ) AS losses,

            SUM(
                CASE
                    WHEN m.home_team_id = t.id
                        THEN m.home_score

                    WHEN m.away_team_id = t.id
                        THEN m.away_score

                    ELSE 0
                END
            ) AS goals_for,

            SUM(
                CASE
                    WHEN m.home_team_id = t.id
                        THEN m.away_score

                    WHEN m.away_team_id = t.id
                        THEN m.home_score

                    ELSE 0
                END
            ) AS goals_against

        FROM teams t

        LEFT JOIN matches m
            ON (
                m.home_team_id = t.id
                OR m.away_team_id = t.id
            )
            AND m.status = 'Finished'

        GROUP BY t.id, t.name
    ";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $standings = [];

    while ($row = $result->fetch_assoc()) {

        $played = (int)$row["played"];
        $wins = (int)$row["wins"];
        $draws = (int)$row["draws"];
        $losses = (int)$row["losses"];

        $goalsFor = (int)$row["goals_for"];
        $goalsAgainst = (int)$row["goals_against"];

        $goalDifference = $goalsFor - $goalsAgainst;

        $points = ($wins * 3) + $draws;

        $standings[] = [
            "team_id" => (int)$row["id"],
            "team_name" => $row["team_name"],
            "played" => $played,
            "wins" => $wins,
            "draws" => $draws,
            "losses" => $losses,
            "goals_for" => $goalsFor,
            "goals_against" => $goalsAgainst,
            "goal_difference" => $goalDifference,
            "points" => $points
        ];
    }

    /*
     * Sort the standings in PHP.
     *
     * Priority:
     * 1. Points
     * 2. Goal difference
     * 3. Goals scored
     */

    usort($standings, function ($a, $b) {

        if ($a["points"] != $b["points"]) {
            return $b["points"] <=> $a["points"];
        }

        if ($a["goal_difference"] != $b["goal_difference"]) {
            return $b["goal_difference"] <=> $a["goal_difference"];
        }

        return $b["goals_for"] <=> $a["goals_for"];
    });

    echo json_encode([
        "success" => true,
        "count" => count($standings),
        "standings" => $standings
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}

?>