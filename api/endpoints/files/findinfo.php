<?php
require_once '../../vendor/autoload.php';
require_once '../../httpResponse.php';
require_once '../../bootstrap.php';
require_once '../../info.php';

header("Access-Control-Allow-Origin: http://localhost:4200");

if(!array_key_exists("id", $_GET) || !array_key_exists("searchradius", $_GET)) {
    http_response(400, "Niepoprawne parametry żądania");
    return;
}

$file = $entityManager->find("PdfIndexer\Models\PdfFile", $_GET['id']);

if(is_null($file)) {
    http_response(404, "Nie znaleziono pliku");
    return;
}

http_response(200, "Ok", [ 'Id'=>$file->getId(), 'Name'=>$file->getName(), 'InfoSearchRadius'=>$_GET['searchradius'], 'Infos'=>findInfoStep1($file, $_GET['searchradius'])]);
