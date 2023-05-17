<?php
    include "internal/database.php";
    header("Content-Type: application/json");
    echo json_encode(getDB()->getServers());
?>