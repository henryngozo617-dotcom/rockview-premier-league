<?php

require_once "auth.php";

require_once "../db.php";

/* =========================================================
   ROCKVIEW PREMIER LEAGUE
   ADMIN - EDIT TEAM
   ========================================================= */

/* ---------------------------------------------------------
   AUTHENTICATION
   --------------------------------------------------------- */
if (!isset($_SESSION['rpl_admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ---------------------------------------------------------
   DATABASE CONNECTION
   --------------------------------------------------------- */
require_once "../db.php";

/* ---------------------------------------------------------
   GET TEAM ID
   --------------------------------------------------------- */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: teams.php");
    exit;
}

$team_id = (int) $_GET['id'];

/* ---------------------------------------------------------
   FETCH TEAM
   --------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT id, name, short_name
    FROM teams
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $team_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: teams.php");
    exit;
}

$team = $result->fetch_assoc();
$stmt->close();

/* ---------------------------------------------------------
   VARIABLES
   --------------------------------------------------------- */
$name = $team['name'];
$short_name = $team['short_name'];

$success = "";
$error = "";

/* ---------------------------------------------------------
   UPDATE TEAM
   --------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $short_name = trim($_POST['short_name'] ?? '');

    /* -----------------------------------------------
       VALIDATION
       ----------------------------------------------- */
    if ($name === '') {
        $error = "Team name is required.";
    } elseif ($short_name === '') {
        $error = "Short name is required.";
    } elseif (strlen($short_name) > 10) {
        $error = "Short name must not be more than 10 characters.";
    } else {

        /* -------------------------------------------
           CHECK FOR DUPLICATE TEAM NAME
           ------------------------------------------- */
        $check = $conn->prepare("
            SELECT id
            FROM teams
            WHERE name = ?
            AND id != ?
            LIMIT 1
        ");

        if (!$check) {
            $error = "Database error: " . $conn->error;
        } else {

            $check->bind_param("si", $name, $team_id);
            $check->execute();

            $duplicate = $check->get_result();

            if ($duplicate->num_rows > 0) {
                $error = "Another team already uses this team name.";
            }

            $check->close();
        }

        /* -------------------------------------------
           UPDATE DATABASE
           ------------------------------------------- */
        if ($error === "") {

            $update = $conn->prepare("
                UPDATE teams
                SET name = ?, short_name = ?
                WHERE id = ?
            ");

            if (!$update) {
                $error = "Database error: " . $conn->error;
            } else {

                $update->bind_param(
                    "ssi",
                    $name,
                    $short_name,
                    $team_id
                );

                if ($update->execute()) {
                    $success = "Team updated successfully.";

                    /* Update displayed values */
                    $team['name'] = $name;
                    $team['short_name'] = $short_name;

                } else {
                    $error = "Unable to update team: " . $update->error;
                }

                $update->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Team | Rockview Premier League</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
            min-height: 100vh;
        }

        /* =================================================
           HEADER
           ================================================= */

        .topbar {
            background: linear-gradient(
                135deg,
                #0f172a,
                #1e3a8a
            );

            color: white;
            padding: 18px 30px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;

            background: rgba(255, 255, 255, 0.15);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
        }

        .brand h1 {
            font-size: 20px;
            margin-bottom: 3px;
        }

        .brand p {
            font-size: 12px;
            opacity: 0.75;
        }

        .back-btn {
            text-decoration: none;
            color: white;

            background: rgba(255, 255, 255, 0.12);

            padding: 10px 16px;
            border-radius: 8px;

            font-size: 14px;
            font-weight: bold;

            transition: 0.2s ease;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.22);
            transform: translateY(-1px);
        }

        /* =================================================
           PAGE
           ================================================= */

        .page {
            max-width: 850px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h2 {
            font-size: 30px;
            color: #111827;
            margin-bottom: 7px;
        }

        .page-heading p {
            color: #6b7280;
            font-size: 14px;
        }

        /* =================================================
           CARD
           ================================================= */

        .card {
            background: white;

            border-radius: 16px;

            padding: 30px;

            box-shadow:
                0 8px 30px rgba(15, 23, 42, 0.08);

            border: 1px solid #e5e7eb;
        }

        /* =================================================
           ALERTS
           ================================================= */

        .alert {
            padding: 14px 16px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 600;
        }

        .success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* =================================================
           FORM
           ================================================= */

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;

            font-size: 14px;
            font-weight: 700;

            color: #374151;

            margin-bottom: 8px;
        }

        .required {
            color: #dc2626;
        }

        input {
            width: 100%;

            padding: 13px 15px;

            border: 1px solid #d1d5db;

            border-radius: 9px;

            font-size: 15px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .hint {
            display: block;

            margin-top: 6px;

            color: #6b7280;

            font-size: 12px;
        }

        /* =================================================
           BUTTONS
           ================================================= */

        .actions {
            display: flex;
            gap: 12px;

            margin-top: 30px;

            padding-top: 25px;

            border-top: 1px solid #e5e7eb;
        }

        .btn {
            border: none;

            padding: 13px 22px;

            border-radius: 9px;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            transition: 0.2s ease;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        /* =================================================
           TEAM INFO
           ================================================= */

        .team-id {
            display: inline-block;

            margin-bottom: 20px;

            padding: 7px 11px;

            border-radius: 7px;

            background: #eff6ff;

            color: #1d4ed8;

            font-size: 12px;

            font-weight: 700;
        }

        /* =================================================
           MOBILE
           ================================================= */

        @media (max-width: 600px) {

            .topbar {
                padding: 15px 18px;
            }

            .brand h1 {
                font-size: 16px;
            }

            .brand p {
                font-size: 10px;
            }

            .back-btn {
                padding: 8px 11px;
                font-size: 12px;
            }

            .page {
                margin: 25px auto;
                padding: 0 14px;
            }

            .page-heading h2 {
                font-size: 24px;
            }

            .card {
                padding: 20px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

    </style>

</head>

<body>

    <!-- =====================================================
         HEADER
         ===================================================== -->

    <header class="topbar">

        <div class="brand">

            <div class="brand-icon">
                ⚽
            </div>

            <div>
                <h1>Rockview Premier League</h1>
                <p>Admin Management System</p>
            </div>

        </div>

        <a href="teams.php" class="back-btn">
            ← Back to Teams
        </a>

    </header>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="page">

        <div class="page-heading">

            <h2>✏️ Edit Team</h2>

            <p>
                Update the information for this RPL team.
            </p>

        </div>


        <div class="card">

            <div class="team-id">
                TEAM ID: #<?php echo htmlspecialchars($team['id']); ?>
            </div>


            <!-- =================================================
                 SUCCESS MESSAGE
                 ================================================= -->

            <?php if ($success !== ""): ?>

                <div class="alert success">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>

            <?php endif; ?>


            <!-- =================================================
                 ERROR MESSAGE
                 ================================================= -->

            <?php if ($error !== ""): ?>

                <div class="alert error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>


            <!-- =================================================
                 EDIT FORM
                 ================================================= -->

            <form
                method="POST"
                action=""
                autocomplete="off"
            >

                <!-- TEAM NAME -->

                <div class="form-group">

                    <label for="name">
                        Team Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($name); ?>"
                        placeholder="Enter team name"
                        maxlength="150"
                        required
                    >

                    <span class="hint">
                        Example: Zambezi FC
                    </span>

                </div>


                <!-- SHORT NAME -->

                <div class="form-group">

                    <label for="short_name">
                        Short Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="short_name"
                        name="short_name"
                        value="<?php echo htmlspecialchars($short_name); ?>"
                        placeholder="Enter short name"
                        maxlength="10"
                        required
                    >

                    <span class="hint">
                        Example: ZFC, RK, BC, GFC
                    </span>

                </div>


                <!-- ACTIONS -->

                <div class="actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        💾 Save Changes
                    </button>

                    <a
                        href="teams.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </main>

</body>
</html>