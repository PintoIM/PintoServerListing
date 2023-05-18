<?php
    include "internal/database.php";
    include "internal/utils.php";
    header("Content-Type: application/json");

    if ($_SERVER['REQUEST_METHOD'] !== "POST") {
        showStatus(405, null, "Bad HTTP method");
    }

    $database = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $officialTagWhitelist = array("127.0.0.1");

    if (!$data || !array_key_exists("name", $data) || !array_key_exists("port", $data) || !array_key_exists("users", $data) ||
     !array_key_exists("maxUsers", $data) || !array_key_exists("tags", $data) || !isset($data["name"]) ||
     !is_int($data["port"]) || !is_int($data["users"]) || !is_int($data["maxUsers"]) || !isset($data["tags"])) {
        showStatus(400, null, "Bad request");
    }

    $serverName = $data["name"];
    $serverIP = $_SERVER["REMOTE_ADDR"];
    $serverPort = $data["port"];
    $serverUsers = $data["users"];
    $serverMaxUsers = $data["maxUsers"];
    $serverTags = $data["tags"];
    $serverTagsParsed = explode(",", $data["tags"]);

    if ($serverTagsParsed && in_array("official", $serverTagsParsed) && !in_array($serverIP, $officialTagWhitelist)) {
        showStatus(403, null, "Unauthorized");
    }

    $server = new PintoServer($serverName, $serverIP, intval($serverPort), intval($serverUsers), intval($serverMaxUsers),
     gmdate("Y-m-d H:i:s", strval(strtotime(date("Y-m-d H:i:s") . "+ 1 minute"))), $serverTags);

    if ($database->isServerAdded($server)) {
        if ($database->updateServer($server)) {
            showStatus(200, "Updated server");
        } else {
            showStatus(500, null, "Unable to update the server entry");
        }
    } else {
        if ($database->addServer($server)) {
            showStatus(200, "Created server");
        } else {
            showStatus(500, null, "Unable to create the server entry");
        }
    }
?>