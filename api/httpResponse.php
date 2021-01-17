<?php

function http_response(int $code, string $msg, mixed $body = null) {
    $phpSapiName    = substr(php_sapi_name(), 0, 3);
    if ($phpSapiName == 'cgi' || $phpSapiName == 'fpm') {
        header('Status: '.$code.' '.$msg);
    } else {
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
        header($protocol.' '.$code.' '.$msg);
    }
    echo json_encode($body);
}