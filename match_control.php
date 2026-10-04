<?php

require_once "auth.php";

require_once "../db.php";

/*
=========================================================
ROCKVIEW PREMIER LEAGUE
ADMIN MATCH CONTROL
MYSQLI VERSION
=========================================================
*/


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   GET MATCH ID
========================================================= */

$match_id = (int)($_GET["match_id"] ?? $_POST["match_id"] ?? 0);

if ($match_id <= 0) {
    die("Invalid match ID.");
}


/* =========================================================
   MESSAGE VARIABLES
========================================================= */

$message = "";
$error = "";


/* =========================================================
   LOAD MATCH
========================================================= */

$stmt = $conn->prepare("
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
    WHERE m.id = ?
");

$stmt->bind_param("i", $match_id);
$stmt->execute();

$result = $stmt->get_result();

$match = $result->fetch_assoc();

$stmt->close();

if (!$match) {
    die("Match not found.");
}


/* =========================================================
   HANDLE ACTIONS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    /* =====================================================
       ADD GOAL
    ===================================================== */

    if ($action === "add_goal") {

        $team_id   = (int)($_POST["team_id"] ?? 0);
        $player_id = (int)($_POST["player_id"] ?? 0);
        $minute    = (int)($_POST["minute"] ?? 0);

        if ($team_id <= 0 || $minute <= 0) {

            $error = "Please provide a valid team and minute.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO goals
                (match_id, team_id, player_id, minute)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "iiii",
                $match_id,
                $team_id,
                $player_id,
                $minute
            );

            if ($stmt->execute()) {
                $message = "Goal added successfully.";
            } else {
                $error = "Could not add goal: " . $stmt->error;
            }

            $stmt->close();
        }
    }


    /* =====================================================
       DELETE GOAL
    ===================================================== */

    elseif ($action === "delete_goal") {

        $goal_id = (int)($_POST["goal_id"] ?? 0);

        if ($goal_id > 0) {

            $stmt = $conn->prepare("
                DELETE FROM goals
                WHERE id = ?
                AND match_id = ?
            ");

            $stmt->bind_param(
                "ii",
                $goal_id,
                $match_id
            );

            if ($stmt->execute()) {
                $message = "Goal deleted successfully.";
            } else {
                $error = "Could not delete goal.";
            }

            $stmt->close();
        }
    }


    /* =====================================================
       ADD CARD
    ===================================================== */

    elseif ($action === "add_card") {

        $team_id   = (int)($_POST["team_id"] ?? 0);
        $player_id = (int)($_POST["player_id"] ?? 0);
        $card_type = trim($_POST["card_type"] ?? "");
        $minute    = (int)($_POST["minute"] ?? 0);

        if (
            $team_id <= 0 ||
            $player_id <= 0 ||
            $minute <= 0 ||
            !in_array($card_type, ["Yellow", "Red"], true)
        ) {

            $error = "Please provide valid card information.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO cards
                (match_id, player_id, team_id, card_type, minute)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "iiisi",
                $match_id,
                $player_id,
                $team_id,
                $card_type,
                $minute
            );

            if ($stmt->execute()) {
                $message = "Card added successfully.";
            } else {
                $error = "Could not add card: " . $stmt->error;
            }

            $stmt->close();
        }
    }


    /* =====================================================
       DELETE CARD
    ===================================================== */

    elseif ($action === "delete_card") {

        $card_id = (int)($_POST["card_id"] ?? 0);

        if ($card_id > 0) {

            $stmt = $conn->prepare("
                DELETE FROM cards
                WHERE id = ?
                AND match_id = ?
            ");

            $stmt->bind_param(
                "ii",
                $card_id,
                $match_id
            );

            if ($stmt->execute()) {
                $message = "Card deleted successfully.";
            } else {
                $error = "Could not delete card.";
            }

            $stmt->close();
        }
    }


    /* =====================================================
       ADD SUBSTITUTION
    ===================================================== */

    elseif ($action === "add_substitution") {

        $team_id    = (int)($_POST["team_id"] ?? 0);
        $player_out = (int)($_POST["player_out_id"] ?? 0);
        $player_in  = (int)($_POST["player_in_id"] ?? 0);
        $minute     = (int)($_POST["minute"] ?? 0);


        if (
            $team_id <= 0 ||
            $player_out <= 0 ||
            $player_in <= 0 ||
            $minute <= 0
        ) {

            $error = "Please complete all substitution fields.";

        } elseif ($player_out === $player_in) {

            $error = "Player OUT and Player IN cannot be the same player.";

        } else {


            /* ---------------------------------------------
               VERIFY PLAYER OUT
            --------------------------------------------- */

            $stmt = $conn->prepare("
                SELECT id
                FROM players
                WHERE id = ?
                AND team_id = ?
                AND status = 'Approved'
            ");

            $stmt->bind_param(
                "ii",
                $player_out,
                $team_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $out_exists = $result->fetch_assoc();

            $stmt->close();


            /* ---------------------------------------------
               VERIFY PLAYER IN
            --------------------------------------------- */

            $stmt = $conn->prepare("
                SELECT id
                FROM players
                WHERE id = ?
                AND team_id = ?
                AND status = 'Approved'
            ");

            $stmt->bind_param(
                "ii",
                $player_in,
                $team_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $in_exists = $result->fetch_assoc();

            $stmt->close();


            if (!$out_exists || !$in_exists) {

                $error =
                    "Both players must belong to the selected team.";

            } else {

                $stmt = $conn->prepare("
                    INSERT INTO substitutions
                    (
                        match_id,
                        team_id,
                        player_out_id,
                        player_in_id,
                        minute
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iiiii",
                    $match_id,
                    $team_id,
                    $player_out,
                    $player_in,
                    $minute
                );

                if ($stmt->execute()) {

                    $message =
                        "🔄 Substitution added successfully.";

                } else {

                    $error =
                        "Could not add substitution: " .
                        $stmt->error;
                }

                $stmt->close();
            }
        }
    }


    /* =====================================================
       DELETE SUBSTITUTION
    ===================================================== */

    elseif ($action === "delete_substitution") {

        $substitution_id =
            (int)($_POST["substitution_id"] ?? 0);

        if ($substitution_id > 0) {

            $stmt = $conn->prepare("
                DELETE FROM substitutions
                WHERE id = ?
                AND match_id = ?
            ");

            $stmt->bind_param(
                "ii",
                $substitution_id,
                $match_id
            );

            if ($stmt->execute()) {

                $message =
                    "Substitution deleted successfully.";

            } else {

                $error =
                    "Could not delete substitution.";
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   RECALCULATE SCORE
========================================================= */

$stmt = $conn->prepare("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN team_id = ? THEN 1
                    ELSE 0
                END
            ), 0
        ) AS home_score,

        COALESCE(
            SUM(
                CASE
                    WHEN team_id = ? THEN 1
                    ELSE 0
                END
            ), 0
        ) AS away_score

    FROM goals

    WHERE match_id = ?
");

$stmt->bind_param(
    "iii",
    $match["home_team_id"],
    $match["away_team_id"],
    $match_id
);

$stmt->execute();

$result = $stmt->get_result();

$score = $result->fetch_assoc();

$stmt->close();

$home_score = (int)$score["home_score"];
$away_score = (int)$score["away_score"];


/* =========================================================
   UPDATE MATCH SCORE
========================================================= */

$stmt = $conn->prepare("
    UPDATE matches
    SET home_score = ?, away_score = ?
    WHERE id = ?
");

$stmt->bind_param(
    "iii",
    $home_score,
    $away_score,
    $match_id
);

$stmt->execute();

$stmt->close();


/* =========================================================
   LOAD PLAYERS
========================================================= */

$players = [];

$result = $conn->query("
    SELECT
        id,
        full_name,
        team_id,
        jersey_number,
        position
    FROM players
    WHERE status = 'Approved'
    ORDER BY full_name ASC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $players[] = $row;
    }
}


/* =========================================================
   LOAD GOALS
========================================================= */

$goals = [];

$stmt = $conn->prepare("
    SELECT
        g.id,
        g.minute,
        g.team_id,
        g.player_id,
        t.name AS team_name,
        p.full_name AS player_name
    FROM goals g
    INNER JOIN teams t
        ON g.team_id = t.id
    LEFT JOIN players p
        ON g.player_id = p.id
    WHERE g.match_id = ?
    ORDER BY g.minute ASC, g.id ASC
");

$stmt->bind_param("i", $match_id);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $goals[] = $row;
}

$stmt->close();


/* =========================================================
   LOAD CARDS
========================================================= */

$cards = [];

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.minute,
        c.card_type,
        c.team_id,
        c.player_id,
        t.name AS team_name,
        p.full_name AS player_name
    FROM cards c
    INNER JOIN teams t
        ON c.team_id = t.id
    INNER JOIN players p
        ON c.player_id = p.id
    WHERE c.match_id = ?
    ORDER BY c.minute ASC, c.id ASC
");

$stmt->bind_param("i", $match_id);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $cards[] = $row;
}

$stmt->close();


/* =========================================================
   LOAD SUBSTITUTIONS
========================================================= */

$substitutions = [];

$stmt = $conn->prepare("
    SELECT
        s.id,
        s.minute,
        s.team_id,
        s.player_out_id,
        s.player_in_id,

        t.name AS team_name,

        pout.full_name AS player_out_name,
        pout.jersey_number AS player_out_jersey,

        pin.full_name AS player_in_name,
        pin.jersey_number AS player_in_jersey

    FROM substitutions s

    INNER JOIN teams t
        ON s.team_id = t.id

    INNER JOIN players pout
        ON s.player_out_id = pout.id

    INNER JOIN players pin
        ON s.player_in_id = pin.id

    WHERE s.match_id = ?

    ORDER BY s.minute ASC, s.id ASC
");

$stmt->bind_param("i", $match_id);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $substitutions[] = $row;
}

$stmt->close();


/* =========================================================
   UPDATE DISPLAY SCORE
========================================================= */

$match["home_score"] = $home_score;
$match["away_score"] = $away_score;

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
RPL Match Control -
<?= e($match["home_team"]) ?>
vs
<?= e($match["away_team"]) ?>
</title>


<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #07111f,
            #102a43,
            #07111f
        );

    color: #ffffff;

    min-height: 100vh;
}

.container {
    width: min(1200px, 94%);

    margin: 30px auto 60px;
}

.header {
    text-align: center;

    margin-bottom: 25px;
}

.header h1 {
    margin: 0;

    font-size: 32px;
}

.header p {
    color: #b9c8d8;

    margin-top: 8px;
}

.scoreboard {

    background:
        linear-gradient(
            135deg,
            #0f2740,
            #173b5e
        );

    border:
        1px solid rgba(255,255,255,.12);

    border-radius: 22px;

    padding: 30px;

    text-align: center;

    box-shadow:
        0 20px 50px rgba(0,0,0,.35);

    margin-bottom: 25px;
}

.teams-score {

    display: grid;

    grid-template-columns:
        1fr
        auto
        1fr;

    align-items: center;

    gap: 25px;
}

.team-name {

    font-size: 24px;

    font-weight: 800;
}

.score {

    font-size: 58px;

    font-weight: 900;

    letter-spacing: 5px;
}

.vs {

    font-size: 15px;

    color: #9db3c8;

    margin-bottom: 5px;
}

.meta {

    margin-top: 15px;

    color: #b9c8d8;
}

.message {

    background: #123e27;

    border:
        1px solid #2bd477;

    color: #8ff0b5;

    padding: 14px 18px;

    border-radius: 12px;

    margin-bottom: 20px;
}

.error {

    background: #4a1c1c;

    border:
        1px solid #ff6b6b;

    color: #ffb0b0;

    padding: 14px 18px;

    border-radius: 12px;

    margin-bottom: 20px;
}

.grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;
}

.card {

    background:
        rgba(14, 32, 52, .96);

    border:
        1px solid rgba(255,255,255,.10);

    border-radius: 18px;

    padding: 22px;

    box-shadow:
        0 15px 35px rgba(0,0,0,.25);
}

.card h2 {

    margin-top: 0;

    font-size: 21px;

    border-bottom:
        1px solid rgba(255,255,255,.10);

    padding-bottom: 12px;
}

.full {

    grid-column: 1 / -1;
}

label {

    display: block;

    margin-top: 12px;

    margin-bottom: 6px;

    color: #b9c8d8;

    font-size: 14px;
}

select,
input {

    width: 100%;

    padding: 12px 14px;

    border-radius: 10px;

    border:
        1px solid #36536d;

    background: #091a2b;

    color: white;

    outline: none;
}

select:focus,
input:focus {

    border-color: #35a7ff;
}

button {

    border: none;

    border-radius: 10px;

    padding: 12px 18px;

    margin-top: 15px;

    cursor: pointer;

    font-weight: 800;

    color: white;

    background:
        linear-gradient(
            135deg,
            #087cff,
            #00b8ff
        );

    transition: .2s;
}

button:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 8px 20px
        rgba(0,160,255,.3);
}

.danger {

    background:
        linear-gradient(
            135deg,
            #d71919,
            #ff4b4b
        );

    padding: 8px 12px;

    margin: 0;
}

.event {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding: 14px;

    border-radius: 12px;

    background:
        rgba(255,255,255,.045);

    margin-bottom: 10px;
}

.event-main {

    flex: 1;
}

.event-title {

    font-weight: 800;
}

.event-info {

    color: #9fb4c8;

    font-size: 13px;

    margin-top: 4px;
}

.minute {

    font-weight: 900;

    color: #46b7ff;

    min-width: 45px;
}

.substitution {

    border-left:
        4px solid #00d084;
}

.sub-out {

    color: #ff7474;
}

.sub-in {

    color: #50e58f;
}

.empty {

    color: #8da1b5;

    padding: 15px 0;
}

@media (max-width: 800px) {

    .grid {

        grid-template-columns: 1fr;
    }

    .full {

        grid-column: auto;
    }

    .teams-score {

        grid-template-columns: 1fr;

        gap: 8px;
    }

    .score {

        font-size: 48px;
    }
}

</style>

</head>


<body>

<div class="container">


<!-- =====================================================
     HEADER
====================================================== -->

<div class="header">

    <h1>
        ⚽ RPL MATCH CONTROL
    </h1>

    <p>

        <?= e($match["match_date"]) ?>

        <?php if (!empty($match["match_time"])): ?>

            • <?= e($match["match_time"]) ?>

        <?php endif; ?>

        <?php if (!empty($match["venue"])): ?>

            • <?= e($match["venue"]) ?>

        <?php endif; ?>

    </p>

</div>


<!-- =====================================================
     SCOREBOARD
====================================================== -->

<div class="scoreboard">

    <div class="teams-score">

        <div class="team-name">

            <?= e($match["home_team"]) ?>

        </div>


        <div>

            <div class="vs">
                MATCH SCORE
            </div>

            <div class="score">

                <?= $home_score ?>

                -

                <?= $away_score ?>

            </div>

        </div>


        <div class="team-name">

            <?= e($match["away_team"]) ?>

        </div>

    </div>


    <div class="meta">

        Status:

        <strong>
            <?= e($match["status"]) ?>
        </strong>

    </div>

</div>


<!-- =====================================================
     MESSAGES
====================================================== -->

<?php if ($message): ?>

<div class="message">

    <?= e($message) ?>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="error">

    ❌ <?= e($error) ?>

</div>

<?php endif; ?>


<div class="grid">


<!-- =====================================================
     ADD GOAL
====================================================== -->

<div class="card">

<h2>
    ⚽ Add Goal
</h2>

<form method="POST">

<input
    type="hidden"
    name="match_id"
    value="<?= $match_id ?>"
>

<input
    type="hidden"
    name="action"
    value="add_goal"
>


<label>
    Team
</label>

<select
    name="team_id"
    class="team-select"
    data-player-target="goalPlayer"
    required
>

<option value="">
    Select Team
</option>

<option value="<?= $match["home_team_id"] ?>">
    <?= e($match["home_team"]) ?>
</option>

<option value="<?= $match["away_team_id"] ?>">
    <?= e($match["away_team"]) ?>
</option>

</select>


<label>
    Scorer
</label>

<select
    name="player_id"
    id="goalPlayer"
>

<option value="">
    Select Player
</option>

</select>


<label>
    Minute
</label>

<input
    type="number"
    name="minute"
    min="1"
    max="150"
    placeholder="e.g. 45"
    required
>


<button type="submit">

    ⚽ Add Goal

</button>

</form>

</div>


<!-- =====================================================
     ADD CARD
====================================================== -->

<div class="card">

<h2>
    🟨 Add Card
</h2>

<form method="POST">

<input
    type="hidden"
    name="match_id"
    value="<?= $match_id ?>"
>

<input
    type="hidden"
    name="action"
    value="add_card"
>


<label>
    Team
</label>

<select
    name="team_id"
    class="team-select"
    data-player-target="cardPlayer"
    required
>

<option value="">
    Select Team
</option>

<option value="<?= $match["home_team_id"] ?>">
    <?= e($match["home_team"]) ?>
</option>

<option value="<?= $match["away_team_id"] ?>">
    <?= e($match["away_team"]) ?>
</option>

</select>


<label>
    Player
</label>

<select
    name="player_id"
    id="cardPlayer"
    required
>

<option value="">
    Select Player
</option>

</select>


<label>
    Card Type
</label>

<select
    name="card_type"
    required
>

<option value="">
    Select Card
</option>

<option value="Yellow">
    🟨 Yellow Card
</option>

<option value="Red">
    🟥 Red Card
</option>

</select>


<label>
    Minute
</label>

<input
    type="number"
    name="minute"
    min="1"
    max="150"
    placeholder="e.g. 72"
    required
>


<button type="submit">

    🟨 Add Card

</button>

</form>

</div>


<!-- =====================================================
     SUBSTITUTION
====================================================== -->

<div class="card full">

<h2>
    🔄 Make Substitution
</h2>

<form method="POST">

<input
    type="hidden"
    name="match_id"
    value="<?= $match_id ?>"
>

<input
    type="hidden"
    name="action"
    value="add_substitution"
>


<label>
    Team
</label>

<select
    name="team_id"
    id="subTeam"
    required
>

<option value="">
    Select Team
</option>

<option value="<?= $match["home_team_id"] ?>">
    <?= e($match["home_team"]) ?>
</option>

<option value="<?= $match["away_team_id"] ?>">
    <?= e($match["away_team"]) ?>
</option>

</select>


<div style="
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
">


<div>

<label>
    🔴 Player OUT
</label>

<select
    name="player_out_id"
    id="playerOut"
    required
>

<option value="">
    Select Player OUT
</option>

</select>

</div>


<div>

<label>
    🟢 Player IN
</label>

<select
    name="player_in_id"
    id="playerIn"
    required
>

<option value="">
    Select Player IN
</option>

</select>

</div>

</div>


<label>
    ⏱️ Minute
</label>

<input
    type="number"
    name="minute"
    min="1"
    max="150"
    placeholder="e.g. 65"
    required
>


<button type="submit">

    🔄 Make Substitution

</button>

</form>

</div>


<!-- =====================================================
     GOALS
====================================================== -->

<div class="card">

<h2>
    ⚽ Goals
</h2>

<?php if (!$goals): ?>

<div class="empty">
    No goals recorded yet.
</div>

<?php else: ?>

<?php foreach ($goals as $goal): ?>

<div class="event">

<div class="minute">

    <?= (int)$goal["minute"] ?>'

</div>


<div class="event-main">

<div class="event-title">

    ⚽

    <?= e(
        $goal["player_name"]
        ?? "Unknown Player"
    ) ?>

</div>


<div class="event-info">

    <?= e($goal["team_name"]) ?>

</div>

</div>


<form method="POST">

<input
    type="hidden"
    name="match_id"
    value="<?= $match_id ?>"
>

<input
    type="hidden"
    name="action"
    value="delete_goal"
>

<input
    type="hidden"
    name="goal_id"
    value="<?= (int)$goal["id"] ?>"
>


<button
    type="submit"
    class="danger"
>

    Delete

</button>

</form>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>


<!-- =====================================================
     CARDS
====================================================== -->

<div class="card">

<h2>
    🟨 Cards
</h2>

<?php if (!$cards): ?>

<div class="empty">
    No cards recorded yet.
</div>

<?php else: ?>

<?php foreach ($cards as $card): ?>

<div class="event">

<div class="minute">

    <?= (int)$card["minute"] ?>'

</div>


<div class="event-main">

<div class="event-title">

    <?= $card["card_type"] === "Red"
        ? "🟥"
        : "🟨"
    ?>

    <?= e($card["player_name"]) ?>

</div>


<div class="event-info">

    <?= e($card["team_name"]) ?>

    •

    <?= e($card["card_type"]) ?>

</div>

</div>


<form method="POST">

<input
    type="hidden"
    name="match_id"
    value="<?= $match_id ?>"
>

<input
    type="hidden"
    name="action"
    value="delete_card"
>

<input
    type="hidden"
    name="card_id"
    value="<?= (int)$card["id"] ?>"
>


<button
    type="submit"
    class="danger"
>

    Delete

</button>

</form>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>


<!-- =====================================================
     SUBSTITUTIONS
====================================================== -->

<div class="card full">

<h2>
    🔄 Substitutions
</h2>

<?php if (!$substitutions): ?>

<div class="empty">

    No substitutions recorded yet.

</div>

<?php else: ?>

<?php foreach ($substitutions as $sub): ?>

<div class="event substitution">


<div class="minute">

    <?= (int)$sub["minute"] ?>'

</div>


<div class="event-main">

<div class="event-title">

    🔄 <?= e($sub["team_name"]) ?>

</div>


<div class="event-info">

<span class="sub-out">

    🔴 OUT:

    <?= e($sub["player_out_name"]) ?>

    <?php if ($sub["player_out_jersey"]): ?>

        (#<?= e($sub["player_out_jersey"]) ?>)

    <?php endif; ?>

</span>


&nbsp;&nbsp;


<span class="sub-in">

    🟢 IN:

    <?= e($sub["player_in_name"]) ?>

    <?php if ($sub["player_in_jersey"]): ?>

        (#<?= e($sub["player_in_jersey"]) ?>)

    <?php endif; ?>

</span>

</div>

</div>


<form method="POST">

<input
    type="hidden"
    name="match_id"
    value="<?= $match_id ?>"
>

<input
    type="hidden"
    name="action"
    value="delete_substitution"
>

<input
    type="hidden"
    name="substitution_id"
    value="<?= (int)$sub["id"] ?>"
>


<button
    type="submit"
    class="danger"
>

    Delete

</button>

</form>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>


<!-- =====================================================
     SUMMARY
====================================================== -->

<div class="card full">

<h2>
    📊 Match Summary
</h2>

<div style="
    display:grid;
    grid-template-columns:
        repeat(4, 1fr);
    gap:12px;
">


<div class="event">

    ⚽ Goals:

    <strong>
        <?= count($goals) ?>
    </strong>

</div>


<div class="event">

    🟨 Cards:

    <strong>
        <?= count($cards) ?>
    </strong>

</div>


<div class="event">

    🔄 Subs:

    <strong>
        <?= count($substitutions) ?>
    </strong>

</div>


<div class="event">

    🏟️ Status:

    <strong>
        <?= e($match["status"]) ?>
    </strong>

</div>


</div>

</div>


</div>

</div>


<script>

/* =========================================================
   PLAYERS FROM PHP
========================================================= */

const players =
    <?= json_encode(
        $players,
        JSON_UNESCAPED_UNICODE
    ) ?>;


/* =========================================================
   POPULATE PLAYERS
========================================================= */

function populatePlayers(
    teamId,
    target,
    excludeId = null
) {

    target.innerHTML = "";


    const defaultOption =
        document.createElement("option");

    defaultOption.value = "";

    defaultOption.textContent =
        "Select Player";

    target.appendChild(defaultOption);


    if (!teamId) {
        return;
    }


    players
        .filter(function(player) {

            return String(player.team_id) ===
                   String(teamId);

        })

        .forEach(function(player) {

            if (
                excludeId !== null &&
                String(player.id) ===
                String(excludeId)
            ) {

                return;
            }


            const option =
                document.createElement("option");


            option.value =
                player.id;


            const jersey =
                player.jersey_number
                    ? " #" +
                      player.jersey_number
                    : "";


            option.textContent =
                player.full_name +
                jersey +
                " - " +
                player.position;


            target.appendChild(option);

        });
}


/* =========================================================
   GOAL + CARD FILTER
========================================================= */

document
    .querySelectorAll(".team-select")
    .forEach(function(select) {

        select.addEventListener(
            "change",
            function() {

                const target =
                    document.getElementById(
                        this.dataset.playerTarget
                    );


                populatePlayers(
                    this.value,
                    target
                );

            }
        );

    });


/* =========================================================
   SUBSTITUTION
========================================================= */

const subTeam =
    document.getElementById("subTeam");

const playerOut =
    document.getElementById("playerOut");

const playerIn =
    document.getElementById("playerIn");


if (subTeam) {

    subTeam.addEventListener(
        "change",
        function() {

            populatePlayers(
                this.value,
                playerOut
            );

            populatePlayers(
                this.value,
                playerIn
            );

        }
    );

}


/* =========================================================
   PLAYER OUT CHANGED
========================================================= */

if (playerOut) {

    playerOut.addEventListener(
        "change",
        function() {

            populatePlayers(
                subTeam.value,
                playerIn,
                this.value
            );

        }
    );

}

</script>

</body>

</html>