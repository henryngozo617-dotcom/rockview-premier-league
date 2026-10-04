<?php

require_once "auth.php";

require_once "../db.php";;


$username = "admin";

$password = "RPL@2026";

$full_name = "RPL Administrator";

$role = "super_admin";


$hashedPassword =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


$stmt =
    $conn->prepare(
        "INSERT INTO admin_users
        (
            username,
            password,
            full_name,
            role
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )"
    );


$stmt->bind_param(
    "ssss",
    $username,
    $hashedPassword,
    $full_name,
    $role
);


if ($stmt->execute()) {

    echo "
        <h2>RPL Admin Created Successfully</h2>

        <p>
            Username: <strong>admin</strong>
        </p>

        <p>
            Password: <strong>RPL@2026</strong>
        </p>

        <p>
            DELETE create_admin.php after this.
        </p>
    ";

} else {

    echo
        "Error: " .
        htmlspecialchars(
            $stmt->error
        );

}

?>