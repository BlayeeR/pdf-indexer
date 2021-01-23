<?php
require_once '../../vendor/autoload.php';
require_once '../../httpResponse.php';
require_once '../../bootstrap.php';
require_once '../../info.php';
require_once '../../date.php';
require_once '../../amount.php';

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

$entityBody = json_decode(file_get_contents('php://input'), true);

$file->setTitle($entityBody['Title']);

function mapVat($vat) {
    switch(trim(strtolower($vat))) {
        case "zw": {
            return 0;
        }
        case "np": {
            return 1;
        }
        case "0%": {
            return 2;
        }
        case "5%": {
            return 3;
        }
        case "8%": {
            return 4;
        }
        case "23%": {
            return 5;
        }
        default: {
            return 6;
        }
    }
}

foreach($file->getDates() as $date) {
    $file->removeDate($date);
}

foreach($file->getAmounts() as $amount) {
    $file->removeAmount($amount);
}

foreach($file->getInfos() as $info) {
    $file->removeInfo($info);
}

foreach(findDates($file, $entityBody['DateSearchRadius']) as $key => $dateBody) {
    $date = new \PdfIndexer\Models\Date();
    $date->setName($dateBody['Name']);
    $date->setValue($dateBody['Value']);

    $entityManager->persist($date);
    $file->addDate($date);
}

foreach(findAmounts($file, $entityBody['AmountSearchRadius']) as $key => $amountBody) {
    $amount = new \PdfIndexer\Models\Amount();
    $amount->setGross($amountBody['Gross']);
    $amount->setVat(mapVat($amountBody['Vat']));
    $amount->setName($amountBody['Name']);

    $entityManager->persist($amount);
    $file->addAmount($amount);
}

foreach(findInfoStep1($file, $entityBody['InfoSearchRadius']) as $key => $infoBody) {
    $info = new \PdfIndexer\Models\Info();
    $info->setName($infoBody['Name']);
    $val = "";
    foreach($entityBody['Infos'][$key]['Selected'] as $selected) {
        $val .= $infoBody['Nearby'][$selected] . " ";
    }    $info->setValue(rtrim($val));
    $entityManager->persist($info);

    $file->addInfo($info);
}

$file->setCompleted(true);

$entityManager->flush();



function mapDate(\PdfIndexer\Models\Date $date) {
    return ['Id'=> $date->getId(), 'Name' => $date->getName(), 'Value'=> $date->getValue()];
}

function mapAmount(\PdfIndexer\Models\Amount $amount) {
    return ['Id'=> $amount->getId(), 'Gross' => $amount->getGross(), 'Vat'=> $amount->getVat()];
}

function mapInfo(\PdfIndexer\Models\Info $info) {
    return ['Id'=> $info->getId(), 'Name' => $info->getName(), 'Value'=> $info->getValue()];
}

function mapDocument(\PdfIndexer\Models\PdfFile $file) {
    return ['Id'=>$file->getId(), 'Name'=>$file->getName(), 'Dates'=> array_map("mapDate", $file->getDates()->toArray()), 'Amounts'=>array_map("mapAmount", $file->getAmounts()->toArray()), 'Infos'=>array_map("mapInfo", $file->getInfos()->toArray())];
}

http_response(200, "Ok", json_encode(mapDocument($file)));