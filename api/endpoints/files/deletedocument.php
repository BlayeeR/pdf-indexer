<?php

require_once '../../vendor/autoload.php';
require_once '../../httpResponse.php';
require_once '../../bootstrap.php';

use PdfIndexer\Models as Models;

header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    return;
}

if(!array_key_exists("id", $_GET)) {
    http_response(400, "Niepoprawne parametry żądania");
    return;
}

$file = $entityManager->find("PdfIndexer\Models\PdfFile", $_GET['id']);

if(is_null($file)) {
    http_response(404, "Nie znaleziono pliku");
    return;
}


try {
    $entityManager->remove($file);
    $entityManager->flush();
}
catch(Exception $e) {

}


http_response(200, "Ok", "Pomyślnie usunięto plik");
