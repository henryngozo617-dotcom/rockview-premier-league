<?php

require_once "auth.php";

require_once "../db.php";

/* =========================
   GET ADMIN INFORMATION
   ========================= */

$adminName = $_SESSION["rpl_admin_name"] ?? "Administrator";
$adminRole = $_SESSION["rpl_admin_role"] ?? "admin";


/* =========================
   QUICK STATISTICS
   ========================= */

function getCount($conn, $table) {

    $allowedTables = [
        "players",
        "teams",
        "matches",
        "goals",
        "news"
    ];

    if (!in_array($table, $allowedTables)) {
        return 0;
    }

    $result = $conn->query(
        "SELECT COUNT(*) AS total FROM `$table`"
    );

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int)$row["total"];
}


$playersCount = getCount($conn, "players");
$teamsCount   = getCount($conn, "teams");
$matchesCount = getCount($conn, "matches");
$goalsCount   = getCount($conn, "goals");
$newsCount    = getCount($conn, "news");

?>

<?php

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>RPL Admin Dashboard</title>

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
                #06150e;

            color: white;

        }


        /* =========================
           SIDEBAR
           ========================= */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;
            bottom: 0;

            width: 250px;

            background:
                linear-gradient(
                    180deg,
                    #031b10,
                    #06140e
                );

            border-right:
                1px solid
                rgba(32,224,122,0.2);

            padding: 28px 18px;

        }


        .logo {

            text-align: center;

            margin-bottom: 35px;

        }


        .logo h1 {

            margin: 0;

            font-size: 42px;

            font-weight: 900;

            color: #20e07a;

        }


        .logo p {

            margin: 4px 0 0;

            font-size: 11px;

            color: #7fcda3;

            letter-spacing: 1px;

        }


        .admin-badge {

            padding: 12px;

            margin-bottom: 25px;

            border-radius: 10px;

            background:
                rgba(32,224,122,0.08);

            border:
                1px solid
                rgba(32,224,122,0.2);

            text-align: center;

        }


        .admin-badge strong {

            display: block;

            color: white;

            margin-bottom: 5px;

        }


        .admin-badge span {

            font-size: 11px;

            color: #20e07a;

            text-transform: uppercase;

        }


        .menu-title {

            font-size: 10px;

            color: #5d9275;

            letter-spacing: 1.5px;

            margin: 20px 10px 8px;

        }


        .nav-link {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 12px 14px;

            margin-bottom: 5px;

            border-radius: 9px;

            color: #b5d0c1;

            text-decoration: none;

            font-size: 14px;

            transition: 0.2s;

        }


        .nav-link:hover {

            background:
                rgba(32,224,122,0.1);

            color: #20e07a;

        }


        .nav-link.active {

            background:
                linear-gradient(
                    90deg,
                    #0b6e3d,
                    #0a4d2d
                );

            color: white;

        }


        .logout {

            position: absolute;

            left: 18px;
            right: 18px;
            bottom: 20px;

            display: block;

            padding: 12px;

            text-align: center;

            border-radius: 9px;

            background:
                rgba(220,50,50,0.1);

            border:
                1px solid
                rgba(220,50,50,0.2);

            color: #ff9999;

            text-decoration: none;

            font-size: 13px;

        }


        /* =========================
           MAIN
           ========================= */

        .main {

            margin-left: 250px;

            padding: 35px;

            min-height: 100vh;

        }


        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 30px;

        }


        .topbar h2 {

            margin: 0;

            font-size: 28px;

        }


        .topbar p {

            margin: 7px 0 0;

            color: #80b99b;

        }


        .view-site {

            padding: 11px 18px;

            border-radius: 9px;

            background:
                #20e07a;

            color: #062015;

            text-decoration: none;

            font-weight: bold;

            font-size: 13px;

        }


        /* =========================
           STAT CARDS
           ========================= */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(5, 1fr);

            gap: 18px;

            margin-bottom: 35px;

        }


        .stat-card {

            padding: 22px;

            border-radius: 15px;

            background:
                linear-gradient(
                    145deg,
                    #0b2a1c,
                    #081b13
                );

            border:
                1px solid
                rgba(32,224,122,0.18);

        }


        .stat-icon {

            font-size: 25px;

            margin-bottom: 15px;

        }


        .stat-number {

            font-size: 30px;

            font-weight: 900;

            color: white;

        }


        .stat-label {

            margin-top: 5px;

            font-size: 12px;

            color: #78b893;

        }


        /* =========================
           MANAGEMENT
           ========================= */

        .section-title {

            margin-bottom: 18px;

        }


        .section-title h3 {

            margin: 0;

            font-size: 20px;

        }


        .section-title p {

            margin: 5px 0;

            color: #6fa488;

            font-size: 13px;

        }


        .management-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

        }


        .management-card {

            position: relative;

            padding: 25px;

            min-height: 155px;

            border-radius: 15px;

            background:
                linear-gradient(
                    145deg,
                    #101f31,
                    #0b1727
                );

            border:
                1px solid
                rgba(255,255,255,0.07);

            text-decoration: none;

            color: white;

            transition: 0.25s;

            overflow: hidden;

        }


        .management-card:hover {

            transform:
                translateY(-5px);

            border-color:
                rgba(32,224,122,0.5);

            box-shadow:
                0 12px 30px
                rgba(0,0,0,0.35);

        }


        .management-card .icon {

            font-size: 32px;

            margin-bottom: 15px;

        }


        .management-card h4 {

            margin: 0 0 7px;

            font-size: 17px;

        }


        .management-card p {

            margin: 0;

            color: #8298aa;

            font-size: 12px;

            line-height: 1.5;

        }


        .arrow {

            position: absolute;

            right: 18px;

            bottom: 15px;

            color: #20e07a;

            font-size: 18px;

        }


        /* =========================
           RESPONSIVE
           ========================= */

        @media (max-width: 1100px) {

            .stats {

                grid-template-columns:
                    repeat(3, 1fr);

            }

            .management-grid {

                grid-template-columns:
                    repeat(3, 1fr);

            }

        }


        @media (max-width: 800px) {

            .sidebar {

                width: 210px;

            }

            .main {

                margin-left: 210px;

                padding: 25px;

            }

            .stats {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .management-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 600px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

            }

            .logout {

                position: static;

                margin-top: 20px;

            }

            .main {

                margin-left: 0;

            }

            .stats,
            .management-grid {

                grid-template-columns: 1fr;

            }

            .topbar {

                display: block;

            }

            .view-site {

                display: inline-block;

                margin-top: 15px;

            }

        }

    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
     ========================= -->

<aside class="sidebar">


    <div class="logo">

        <h1>RPL</h1>

        <p>
            ADMINISTRATION
        </p>

    </div>


    <div class="admin-badge">

        <strong>
            <?= htmlspecialchars($adminName) ?>
        </strong>

        <span>
            <?= htmlspecialchars($adminRole) ?>
        </span>

    </div>


    <div class="menu-title">
        MAIN
    </div>


  <a
    href="dashboard.php"
    class="nav-link active"
>
  <a href="dashboard.php" class="nav-link active">
    📊 Dashboard
</a>


<div class="menu-title">
    MANAGEMENT
</div>


<a href="players.php" class="nav-link">
    👥 Players
</a>


<a href="teams.php" class="nav-link">
    ⚽ Teams
</a>


<a href="matches.php" class="nav-link">
    🏟️ Matches
</a>


<a href="standings.php" class="nav-link">
    📊 Standings
</a>


<a href="news.php" class="nav-link">
    📰 News & Updates
</a>


<a href="goalkeepers.php" class="nav-link">
    🧤 Goalkeepers
</a>


<a href="statistics.php" class="nav-link">
    ⚽ Statistics
</a>


<a href="logout.php" class="logout">
    🚪 Logout
</a>


<!-- =========================
     MAIN CONTENT
     ========================= -->

<main class="main">


    <div class="topbar">

        <div>

            <h2>
                RPL Dashboard
            </h2>

            <p>
                Welcome back,
                <?= htmlspecialchars($adminName) ?>.
                Here's what's happening in the league.
            </p>

        </div>


        <a
            href="../index.html"
            class="view-site"
        >
            🌐 VIEW WEBSITE
        </a>

    </div>


    <!-- =========================
         STATISTICS
         ========================= -->

    <div class="stats">


        <div class="stat-card">

            <div class="stat-icon">
                👥
            </div>

            <div class="stat-number">
                <?= $playersCount ?>
            </div>

            <div class="stat-label">
                REGISTERED PLAYERS
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⚽
            </div>

            <div class="stat-number">
                <?= $teamsCount ?>
            </div>

            <div class="stat-label">
                TEAMS
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🏟️
            </div>

            <div class="stat-number">
                <?= $matchesCount ?>
            </div>

            <div class="stat-label">
                MATCHES
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🥅
            </div>

            <div class="stat-number">
                <?= $goalsCount ?>
            </div>

            <div class="stat-label">
                GOALS
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                📰
            </div>

            <div class="stat-number">
                <?= $newsCount ?>
            </div>

            <div class="stat-label">
                NEWS POSTS
            </div>

        </div>


    </div>


    <!-- =========================
         MANAGEMENT
         ========================= -->

    <div class="section-title">

        <h3>
            RPL Management
        </h3>

        <p>
            Manage the league directly from the administration panel.
        </p>

    </div>


    <div class="management-grid">


        <a
            href="#"
            class="management-card"
        >

            <div class="icon">
                👥
            </div>

            <h4>
                Players
            </h4>

            <p>
                Manage registrations,
                player profiles and approvals.
            </p>

            <span class="arrow">
                →
            </span>

        </a>


        <a
            href="#"
            class="management-card"
        >

            <div class="icon">
                ⚽
            </div>

            <h4>
                Teams
            </h4>

            <p>
                Manage teams,
                logos and squad information.
            </p>

            <span class="arrow">
                →
            </span>

        </a>


        <a
            href="#"
            class="management-card"
        >

            <div class="icon">
                🏟️
            </div>

            <h4>
                Matches
            </h4>

            <p>
                Manage fixtures,
                results, venues and match times.
            </p>

            <span class="arrow">
                →
            </span>

        </a>


        <a
            href="#"
            class="management-card"
        >

            <div class="icon">
                📊
            </div>

            <h4>
                Standings
            </h4>

            <p>
                Monitor league positions,
                points and goal difference.
            </p>

            <span class="arrow">
                →
            </span>

        </a>


        <a
            href="#"
            class="management-card"
        >

            <div class="icon">
                📰
            </div>

            <h4>
                News & Updates
            </h4>

            <p>
                Publish announcements,
                updates and RPL stories.
            </p>

            <span class="arrow">
                →
            </span>

        </a>


        <a
            href="#"
            class="management-card"
        >

            <div class="icon">
                🧤
            </div>

            <h4>
                Goalkeepers
            </h4>

            <p>
                Manage saves,
                clean sheets and goalkeeper statistics.
            </p>

            <span class="arrow">
                →
            </span>

        </a>


        <a
            href="#"
            class="management-card"
        >

            <div class="icon">
                ⚽
            </div>

            <h4>
                Statistics
            </h4>

            <p>
                Manage goals,
                cards and player statistics.
            </p>

            <span class="arrow">
                →
            </span>

        </a>


        <a
            href="../index.html"
            class="management-card"
        >

            <div class="icon">
                🌐
            </div>

            <h4>
                Public Website
            </h4>

            <p>
                Open the public RPL
                website.
            </p>

            <span class="arrow">
                →
            </span>

        </a>


    </div>


</main>


</body>

</html>