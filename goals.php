<?php

header("Content-Type: application/json");

require_once "../db.php";

try {

    $sql = "SELECT COUNT(*) AS total FROM goals";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $row = $result->fetch_assoc();

    echo json_encode([
        "success" => true,
        "count" => (int)$row["total"]
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}

?>