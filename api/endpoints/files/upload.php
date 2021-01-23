<?php
require_once '../../vendor/autoload.php';
require_once '../../httpResponse.php';
require_once '../../bootstrap.php';
require_once '../../Pdf.php';
require_once '../../date.php';

use PdfIndexer\Models As Models;

if(array_key_exists("fileKey", $_FILES)) {
    $fileName = $_FILES["fileKey"]["name"];
    $filePath = dirname(__FILE__)."/../../../files/" . $fileName;
    move_uploaded_file($_FILES["fileKey"]["tmp_name"], $filePath);
    if(mime_content_type($filePath) !== "application/pdf" ) {
        unlink($filePath);
        http_response(400, "Wybrany plik nie jest dokumentem PDF!");
    }
    else {
        $ghostscriptCommand = "gs";
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $ghostscriptCommand = "gswin64c";
        }

        $cmd = $ghostscriptCommand . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default -dNOPAUSE -dQUIET -dBATCH -dDetectDuplicateImages -dCompressFonts=true -r150 -sOutputFile=' . '"' . $filePath . "_1" . '"' . ' "' . $filePath . '"';//-sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dCompressFonts=false -dBATCH -dNOPAUSE -sOutputFile=' . "input.pdf" . ' ' . "trudny.pdf";
        exec($cmd, $output, $return);

        if ($return) {
            http_response(400, "Wystąpił problem przy konwersji pliku");
            return;
        }

        //dekodowanie pliku
        $cmd = 'qpdf --decrypt --stream-data=uncompress "' . $filePath . "_1" . '" "' . $filePath . "_2" .'"';
        exec($cmd, $output, $return);

        if ($return) {
            http_response(400, "Wystąpił problem przy konwersji pliku");
            return;
        }

        try {
            unlink($filePath . "_1");
        }
        catch(Exception $e) {

        }

        $pdf = new Pdf($filePath . "_2");

        $file = new Models\PdfFile();
        $file->setName(substr($fileName, 0,  strrpos($fileName, ".")));
        $file->setCompleted(false);
        $entityManager->persist($file);

        foreach($pdf->getPages() as $pageIdx => $pageObj) {
            $page = new Models\Page();
            $page->setPageNumber($pageIdx+1);

            $contents = $pageObj->getPageContents();

            $page->setText($contents->getText());
            $page->setRawText($contents->getRawText());
            $page->setPdfFile($file);

            $textArray = $contents->getTextArray();
            $rawTextArray = $contents->getRawTextArray();

            $entityManager->persist($page);

            for($i = 0; $i < count($contents->getTextArray()); $i++) {
                $text = new Models\TextObject();
                $text->setText($textArray[$i]['text']);
                $text->setX($textArray[$i]['x']);
                $text->setY($textArray[$i]['y']);
                $text->setIdx($textArray[$i]['index']);
                $text->setRawIdx($rawTextArray[$i]['index']);
                $text->setPage($page);

                $entityManager->persist($text);

                $page->addTextObject($text);
            }

            $file->addPage($page);
        }

        $entityManager->flush();

        http_response(200, "Dodano plik", json_encode([ 'Id'=>$file->getId(), 'Name'=>$file->getName()]));
    }
}
else {
    http_response(400, "Nie wybrano pliku!");
}
