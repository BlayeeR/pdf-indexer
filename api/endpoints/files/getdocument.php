<?php

require_once '../../vendor/autoload.php';
require_once '../../httpResponse.php';
require_once '../../bootstrap.php';

use PdfIndexer\Models as Models;

header("Access-Control-Allow-Origin: http://localhost:4200");

if(!array_key_exists("id", $_GET)) {
    http_response(400, "Niepoprawne parametry żądania");
    return;
}

$file = $entityManager->find("PdfIndexer\Models\PdfFile", $_GET['id']);

if(is_null($file)) {
    http_response(404, "Nie znaleziono pliku");
    return;
}

function mapDate(\PdfIndexer\Models\Date $date) {
    return ['Id'=> $date->getId(), 'Name' => $date->getName(), 'Value'=> $date->getValue()];
}

function mapAmount(\PdfIndexer\Models\Amount $amount) {
    return ['Id'=> $amount->getId(), 'Gross' => $amount->getGross(), 'Vat'=> $amount->getVat(), 'Name' => $amount->getName()];
}

function mapInfo(\PdfIndexer\Models\Info $info) {
    return ['Id'=> $info->getId(), 'Name' => $info->getName(), 'Value'=> $info->getValue()];
}

function mapDocument(\PdfIndexer\Models\PdfFile $file) {
    return ['Id'=>$file->getId(), 'Name'=>$file->getName(), 'Title'=>$file->getTitle(), 'Text'=>$file->getText(), 'Dates'=> array_map("mapDate", $file->getDates()->toArray()), 'Amounts'=>array_map("mapAmount", $file->getAmounts()->toArray()), 'Infos'=>array_map("mapInfo", $file->getInfos()->toArray())];
}

http_response(200, "Ok", array_map("mapDocument", array($file))[0]);
