<?php
    include "internal/database.php";
    include "internal/utils.php";
    header("Content-Type: application/json");

    if ($_SERVER['REQUEST_METHOD'] !== "POST") {
        showStatus(405, null, "Bad HTTP method");
    }

    $database = getDB();
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data == null || !is_int($data["port"])) {
       showStatus(400, null, "Bad request");
    }

    $server = PintoServer::getForSQLServer($_SERVER["REMOTE_ADDR"], intval($data["port"]));

    if ($database->isServerAdded($server)) {
        if ($database->removeServer($server)) {
            showStatus(200, "OK");
        } else {
            showStatus(500, null, "Unable to delete the server entry");
        }
    } else {
        showStatus(500, null, "Server doesn't exist");
    }
?>