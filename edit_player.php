<?php

require_once "auth.php";

require_once "../db.php";

if (!isset($_SESSION["rpl_admin_id"])) {

    header("Location: index.php");
    exit;

}

require_once "../db.php";


$id = $_GET["id"] ?? null;


if(!$id){

    die("Player ID missing");

}



$playerQuery = mysqli_query(
    $conn,
    "
    SELECT *
    FROM players
    WHERE id='$id'
    "
);


$player = mysqli_fetch_assoc($playerQuery);



$teams = mysqli_query(
    $conn,
    "
    SELECT *
    FROM teams
    ORDER BY name ASC
    "
);



if(isset($_POST["update"])) {


    $registration =
    $_POST["registration_number"];


    $name =
    $_POST["full_name"];


    $team =
    $_POST["team_id"];


    $position =
    $_POST["position"];


    $jersey =
    $_POST["jersey_number"];


    $status =
    $_POST["status"];


$photo = $player['photo'];

if (isset($_FILES['photo']) && $_FILES['photo']['name'] != "") {

    $fileName = time() . "_" . basename($_FILES['photo']['name']);

    $target = "../uploads/players/" . $fileName;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
        $photo = "uploads/players/" . $fileName;
    }
}



    $photo = $player['photo'];


if(isset($_FILES['photo']) && $_FILES['photo']['name'] != ""){


    $fileName = time() . "_" . $_FILES['photo']['name'];


    $target = "../uploads/players/" . $fileName;


    move_uploaded_file(
        $_FILES['photo']['tmp_name'],
        $target
    );


    $photo = "uploads/players/" . $fileName;


}



    $update = mysqli_query(
        $conn,
        "
       UPDATE players SET
    registration_number='$registration',
    full_name='$name',
    team_id='$team',
    position='$position',
    jersey_number='$jersey',
    status='$status',
    photo='$photo'
    WHERE id='$id'

        "
    );



    if($update){

        header(
            "Location: players.php"
        );

        exit;

    }


}



?>



<!DOCTYPE html>

<html>

<head>

<title>
Edit Player
</title>


<style>

body{

background:#02190b;

color:white;

font-family:Arial;

padding:40px;

}


.form-box{

background:#062d17;

padding:30px;

border-radius:15px;

max-width:600px;

margin:auto;

}


input,select{

width:100%;

padding:12px;

margin:10px 0;

border-radius:8px;

border:none;

}



button{

background:#18c85c;

color:white;

padding:12px 25px;

border:none;

border-radius:8px;

cursor:pointer;

font-weight:bold;

}


a{

color:white;

}



</style>


</head>


<body>


<div class="form-box">


<a href="players.php">
⬅ Back
</a>


<h1>
✏️ Edit Player
</h1>



<form method="POST" enctype="multipart/form-data">



<label>
Registration Number
</label>

<input
type="text"
name="registration_number"
value="<?= $player['registration_number'] ?>"
>



<label>
Full Name
</label>

<input
type="text"
name="full_name"
value="<?= $player['full_name'] ?>"
>




<label>
Team
</label>


<select name="team_id">


<?php while($team=mysqli_fetch_assoc($teams)){ ?>


<option

value="<?= $team['id'] ?>"

<?=

$team['id']==$player['team_id']

?

"selected"

:

""

?>

>

<?= $team['name'] ?>

</option>


<?php } ?>


</select>




<label>
Position
</label>


<input

type="text"

name="position"

value="<?= $player['position'] ?>"

>




<label>
Jersey Number
</label>


<input

type="number"

name="jersey_number"

value="<?= $player['jersey_number'] ?>"

>




<label>
Player Photo
</label>


<input
type="file"
name="photo"
accept="image/*"
>

<label>Player Photo</label>
<input type="file" name="photo" accept="image/*">

<label>Status</label>


<label>
Status
</label>


<select name="status">


<option
<?= $player['status']=="Pending"?"selected":"" ?>
>
Pending
</option>


<option
<?= $player['status']=="Approved"?"selected":"" ?>
>
Approved
</option>


<option
<?= $player['status']=="Rejected"?"selected":"" ?>
>
Rejected
</option>


</select>



<br><br>


<button name="update">

Save Changes

</button>


</form>


</div>


</body>

</html>