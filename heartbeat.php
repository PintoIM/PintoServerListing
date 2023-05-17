<?php
    include "internal/database.php";
    include "internal/utils.php";
    header("Content-Type: application/json");

    if ($_SERVER['REQUEST_METHOD'] !== "POST") {
        showStatus(405, null, "Bad HTTP method");
    }

    $database = getDB();
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data == null || $data["name"] == null || !is_int($data["port"]) ||
     !is_int($data["users"]) || !is_int($data["max_users"])) {
        showStatus(400, null, "Bad request");
    }

    $server = new PintoServer($data["name"], $_SERVER["REMOTE_ADDR"],
     intval($data["port"]), intval($data["users"]),
     intval($data["max_users"]), gmdate("Y-m-d H:i:s", strval(strtotime(date("Y-m-d H:i:s") . "+ 1 minute"))));

    if ($database->isServerAdded($server)) {
        if ($database->updateServer($server)) {
            showStatus(200, "OK");
        } else {
            showStatus(500, null, "Unable to update the server entry");
        }
    } else {
        if ($database->addServer($server)) {
            showStatus(200, "OK");
        } else {
            showStatus(500, null, "Unable to create the server entry");
        }
    }
?>