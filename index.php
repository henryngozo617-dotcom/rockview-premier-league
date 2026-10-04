<?php

session_start();

if (isset($_SESSION["rpl_admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION["login_error"] ?? "";
unset($_SESSION["login_error"]);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>RPL Admin Login</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #02150d,
                    #063b20,
                    #01140b
                );

            color: white;

        }


        .login-container {

            width: 420px;

            max-width: 92%;

            padding: 40px;

            border-radius: 22px;

            background:
                rgba(5, 25, 17, 0.95);

            border:
                1px solid
                rgba(32, 224, 122, 0.35);

            box-shadow:
                0 25px 70px
                rgba(0, 0, 0, 0.55);

        }


        .logo {

            text-align: center;

            margin-bottom: 30px;

        }


        .logo h1 {

            margin: 0;

            font-size: 48px;

            font-weight: 900;

            color: #20e07a;

        }


        .logo p {

            margin-top: 6px;

            color: #91dcb3;

            font-size: 13px;

            letter-spacing: 1px;

        }


        .admin-title {

            text-align: center;

            margin-bottom: 25px;

        }


        .admin-title h2 {

            margin: 0 0 8px;

            font-size: 25px;

        }


        .admin-title span {

            color: #20e07a;

            font-size: 13px;

            font-weight: bold;

        }


        .form-group {

            margin-bottom: 18px;

        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            font-size: 13px;

            color: #a7cbb8;

        }


        .form-group input {

            width: 100%;

            padding: 14px 15px;

            border-radius: 10px;

            border:
                1px solid
                rgba(255,255,255,0.12);

            background: #091c14;

            color: white;

            outline: none;

            font-size: 15px;

        }


        .form-group input:focus {

            border-color:
                #20e07a;

            box-shadow:
                0 0 0 3px
                rgba(32,224,122,0.10);

        }


        .login-button {

            width: 100%;

            padding: 15px;

            border: none;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #20e07a,
                    #07994e
                );

            color: white;

            font-size: 16px;

            font-weight: 900;

            cursor: pointer;

            margin-top: 8px;

            transition:
                0.25s ease;

        }


        .login-button:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 10px 25px
                rgba(32,224,122,0.25);

        }


        .error {

            padding: 12px;

            margin-bottom: 18px;

            border-radius: 8px;

            background:
                rgba(220,50,50,0.15);

            border:
                1px solid
                rgba(220,50,50,0.35);

            color: #ff8d8d;

            text-align: center;

            font-size: 13px;

        }


        .back-link {

            display: block;

            margin-top: 22px;

            text-align: center;

            color: #82cfa4;

            text-decoration: none;

            font-size: 13px;

        }


        .back-link:hover {

            color: #20e07a;

        }

    </style>

</head>


<body>


<div class="login-container">


    <div class="logo">

        <h1>RPL</h1>

        <p>
            ROCKVIEW PREMIER LEAGUE
        </p>

    </div>


    <div class="admin-title">

        <h2>
            Admin Portal
        </h2>

        <span>
            AUTHORIZED PERSONNEL ONLY
        </span>

    </div>


    <?php if ($error): ?>

        <div class="error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form
        action="login.php"
        method="POST"
    >


        <div class="form-group">

            <label>
                Username
            </label>

            <input
                type="text"
                name="username"
                placeholder="Enter username"
                required
            >

        </div>


        <div class="form-group">

            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                placeholder="Enter password"
                required
            >

        </div>


        <button
            type="submit"
            class="login-button"
        >

            LOGIN TO RPL

        </button>


    </form>


    <a
        href="../index.html"
        class="back-link"
    >

        ← Back to RPL Website

    </a>


</div>


</body>

</html>