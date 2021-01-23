<?php

require_once '../../vendor/autoload.php';
require_once '../../httpResponse.php';
require_once '../../bootstrap.php';

use PdfIndexer\Models as Models;

if(!array_key_exists("id", $_GET)) {
    http_response(400, "Niepoprawne parametry żądania");
    return;
}

$file = $entityManager->find("PdfIndexer\Models\PdfFile", $_GET['id']);

if(is_null($file)) {
    http_response(404, "Nie znaleziono pliku");
    return;
}

$filePath = dirname(__FILE__)."/../../../files/" . $file->getName() . ".pdf";

header("Content-Type: application/pdf");

http_response(200, "Ok", file_get_contents($filePath, FILE_BINARY));
