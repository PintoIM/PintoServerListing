<?php
    function showStatus(int $code, string $status = null, string $error = null) {
        http_response_code($code);

        $response = array(
            "status" => ($error == null ? $status : "error")
        );
        if ($error != null) {
            $response["error"] = $error;
        }

        die(json_encode($response));
    }
?>