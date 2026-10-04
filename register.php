<?php
session_start();

require_once "db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    /* =========================
       VALIDATION
       ========================= */

    if ($full_name === "" || $username === "" || $password === "") {

        $error = "Please fill in all required fields.";

    } elseif (strlen($full_name) > 150) {

        $error = "Full name is too long.";

    } elseif (strlen($username) < 3 || strlen($username) > 100) {

        $error = "Username must be between 3 and 100 characters.";

    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {

        $error = "Username can only contain letters, numbers, dots, underscores and hyphens.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters long.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        /* =========================
           CHECK USERNAME
           ========================= */

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        if (!$check) {

            $error = "Database error.";

        } else {

            $check->bind_param("s", $username);
            $check->execute();

            $result = $check->get_result();

            if ($result->num_rows > 0) {

                $error = "That username is already taken.";

            }

            $check->close();
        }


        /* =========================
           CREATE ACCOUNT
           ========================= */

        if ($error === "") {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            /*
             * Public registration creates a Registrar account.
             * Admin accounts must be created securely by an existing
             * administrator/database administrator.
             */

            $role = "Registrar";

            $insert = $conn->prepare("
                INSERT INTO users
                (full_name, username, password, role)
                VALUES (?, ?, ?, ?)
            ");

            if (!$insert) {

                $error = "Unable to prepare registration.";

            } else {

                $insert->bind_param(
                    "ssss",
                    $full_name,
                    $username,
                    $hashed_password,
                    $role
                );

                if ($insert->execute()) {

                    $success = "Registration successful! You can now log in.";

                    $full_name = "";
                    $username = "";

                } else {

                    $error = "Registration failed. Please try again.";
                }

                $insert->close();
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

    <title>Register | Rockview Premier League</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;

            font-family: Arial, Helvetica, sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #0f172a,
                    #1e3a8a,
                    #2563eb
                );

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 25px;
        }

        .container {
            width: 100%;
            max-width: 470px;
        }

        .logo {
            text-align: center;
            color: white;
            margin-bottom: 20px;
        }

        .logo-icon {
            font-size: 48px;
            margin-bottom: 8px;
        }

        .logo h1 {
            font-size: 24px;
        }

        .logo p {
            margin-top: 5px;
            opacity: 0.8;
            font-size: 13px;
        }

        .card {
            background: white;

            border-radius: 18px;

            padding: 30px;

            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.25);
        }

        .card h2 {
            color: #111827;
            margin-bottom: 7px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 24px;
        }

        .alert {
            padding: 13px 15px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 13px;
            font-weight: 600;
        }

        .error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;

            font-size: 13px;

            font-weight: 700;

            color: #374151;

            margin-bottom: 7px;
        }

        input {
            width: 100%;

            padding: 12px 14px;

            border: 1px solid #d1d5db;

            border-radius: 9px;

            font-size: 14px;

            outline: none;
        }

        input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .hint {
            color: #6b7280;
            font-size: 11px;
            margin-top: 5px;
            display: block;
        }

        button {
            width: 100%;

            border: none;

            background: #2563eb;

            color: white;

            padding: 13px;

            border-radius: 9px;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            margin-top: 5px;
        }

        button:hover {
            background: #1d4ed8;
        }

        .bottom {
            text-align: center;

            margin-top: 22px;

            font-size: 13px;

            color: #6b7280;
        }

        .bottom a {
            color: #2563eb;

            font-weight: 700;

            text-decoration: none;
        }

        .back {
            display: block;

            text-align: center;

            margin-top: 15px;

            color: #6b7280;

            text-decoration: none;

            font-size: 12px;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="logo">

        <div class="logo-icon">⚽</div>

        <h1>Rockview Premier League</h1>

        <p>RPL Account Registration</p>

    </div>


    <div class="card">

        <h2>Create Account</h2>

        <p class="subtitle">
            Register for access to the RPL system.
        </p>


        <?php if ($error !== ""): ?>

            <div class="alert error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div class="alert success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="150"
                    value="<?php echo htmlspecialchars($full_name ?? ""); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    maxlength="100"
                    value="<?php echo htmlspecialchars($username ?? ""); ?>"
                    required
                >

                <span class="hint">
                    Letters, numbers, dots, underscores and hyphens.
                </span>

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                >

                <span class="hint">
                    Minimum 8 characters.
                </span>

            </div>


            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    required
                >

            </div>


            <button type="submit">
                CREATE ACCOUNT
            </button>

        </form>


        <div class="bottom">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </div>


        <a
            href="index.html"
            class="back"
        >
            ← Back to RPL
        </a>

    </div>

</div>

</body>

</html>