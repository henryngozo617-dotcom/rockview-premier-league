<?php
session_start();

require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        $stmt = $conn->prepare("
            SELECT
                id,
                full_name,
                username,
                password,
                role
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "Database error.";

        } else {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {

                    /*
                     * Regenerate the session ID after successful login.
                     * This helps prevent session fixation.
                     */

                    session_regenerate_id(true);

                    $_SESSION["rpl_user_id"] = (int) $user["id"];

                    $_SESSION["rpl_username"] =
                        $user["username"];

                    $_SESSION["rpl_full_name"] =
                        $user["full_name"];

                    $_SESSION["rpl_role"] =
                        $user["role"];


                    /*
                     * The existing RPL admin pages use:
                     * $_SESSION['rpl_admin_id']
                     *
                     * Therefore, only actual Admin accounts receive
                     * the admin session variable.
                     */

                    if ($user["role"] === "Admin") {

                        $_SESSION["rpl_admin_id"] =
                            (int) $user["id"];

                        header("Location: admin/");
                        exit;
                    }


                    /*
                     * Non-admin users return to the public website.
                     */

                    header("Location: index.html");
                    exit;

                } else {

                    $error = "Invalid username or password.";
                }

            } else {

                $error = "Invalid username or password.";
            }

            $stmt->close();
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

    <title>Login | Rockview Premier League</title>

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
            max-width: 430px;
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

        <p>RPL Secure Login</p>

    </div>


    <div class="card">

        <h2>Welcome Back</h2>

        <p class="subtitle">
            Sign in to your RPL account.
        </p>


        <?php if ($error !== ""): ?>

            <div class="alert error">

                ❌ <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    autocomplete="username"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >

            </div>


            <button type="submit">
                LOGIN
            </button>

        </form>


        <div class="bottom">

            Don't have an account?

            <a href="register.php">
                Register
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