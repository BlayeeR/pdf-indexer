<?php

require_once '../../vendor/autoload.php';
require_once '../../httpResponse.php';
require_once '../../bootstrap.php';

use PdfIndexer\Models as Models;

function mapDocument(\PdfIndexer\Models\PdfFile $file) {
    return ['Id'=>$file->getId(), 'Name'=>$file->getName(), 'Title'=>$file->getTitle(), 'CountDates'=> $file->countDates(), 'CountAmounts'=>$file->countAmounts(), 'CountInfos'=>$file->countInfos()];
}

$files = $entityManager->getRepository("PdfIndexer\Models\PdfFile")->findBy(array('completed' => true), array('id'=>'DESC'));

http_response(200, "Ok", json_encode(array_map("mapDocument", $files)));
