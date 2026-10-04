<?php

require_once "auth.php";

require_once "../db.php";

if (!isset($_SESSION["rpl_admin_id"])) {

    exit("Unauthorized");

}

require_once "../db.php";


$action = $_POST["action"] ?? "";
$id = $_POST["id"] ?? "";


if (!$id || !$action) {

    exit("Invalid request");

}



if ($action == "approve") {


    $sql = "
    UPDATE players
    SET status='Approved'
    WHERE id='$id'
    ";


}


elseif ($action == "reject") {


    $sql = "
    UPDATE players
    SET status='Rejected'
    WHERE id='$id'
    ";


}


elseif ($action == "delete") {


    $sql = "
    DELETE FROM players
    WHERE id='$id'
    ";


}


else {

    exit("Unknown action");

}



if(mysqli_query($conn,$sql)){


    header("Location: players.php");


}
else{


    echo mysqli_error($conn);


}

?>