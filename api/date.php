<?php

require_once 'vendor/autoload.php';

use PdfIndexer\Models As Models;

function getTextValueFromRawIndex($index, $textObjects) {
    for($i = count($textObjects) - 1; $i >= 0; $i--) {
        if($index >= $textObjects[$i]->getRawIdx()) {
            return $textObjects[$i];
        }
    }
    return null;
}

function getNearbyRawTexts($x, $y, $textObjects, $searchRadius): array {
    $ret = [];

    foreach($textObjects as $text) {
        if(($text->getX() >= $x-$searchRadius) && $text->getY() >= $y-$searchRadius && $text->getY() <= $y +$searchRadius) {
            array_push($ret, $text);
        }
    }
    return $ret;
}

function findDates(Models\PdfFile $pdf, int $searchRadius) {

    $daty = [
        'values' => [
            ['name' => "Data sprzedaży", 'pattern' => "/data\ssprzedaży/iu"],
            ['name' => "Z dnia", 'pattern' => "/z dnia/i"],
            ['name' => "Data wystawienia", 'pattern' => "/data\swystawienia/i"],
            ['name' => "Data dostarczenia", 'pattern' => "/data\sdostarczenia/i"],
            ['name' => "Data dodania", 'pattern' => "/data\sdodania/i"],
            ['name' => "Data zapłaty", 'pattern' => "/data\szapłaty/i"]
        ],
        'patterns' => ["/(\d{2})[.\/-](\d{2})[.\/-](\d{4})/m", "/(\d{4})[.\/-](\d{2})[.\/-](\d{2})/m"]
    ];

    $output = [];

    foreach($pdf->getPages() as $pageKey => $page) {

        //iterujemy po każdym poszukiwanym rodzaju daty
        foreach ($daty['values'] as $dateKey) {
            //sprawdzamy czy tekst zawiera klucz danego rodzaju daty
            if (preg_match_all($dateKey['pattern'], $page->getRawText(), $matches, PREG_OFFSET_CAPTURE)) {
                //iterujemy przez każdy klucz znaleziony w tekście
                foreach ($matches[0] as $match) {
                    $index = $match[1];
                    //pobieramy obiekt tekstowy w którym znajduje się znaleziony klucz
                    $textObj = getTextValueFromRawIndex($index, $page->getTextObjects());

                    if ($textObj) {
                        //pobieramy obiekty tekstowe w okolicy znalezionego obiektu
                        $nearbyTexts = getNearbyRawTexts($textObj->getX(), $textObj->getY(), $page->getTextObjects(), $searchRadius);

                        $ret = null;
                        //sprawdzamy po kolei znalezione obiekty czy zawierają datę
                        foreach ($nearbyTexts as $nearbyText) {
                            foreach ($daty['patterns'] as $pattern) {
                                //jeśli znaleziono datę to dodajemy ją do wyniku i wychodzimy z pętli
                                if (preg_match_all($pattern, $nearbyText->getText(), $dateMatches, PREG_OFFSET_CAPTURE)) {
                                    for ($i = 0; $i < count($dateMatches[0]); $i++) {

                                        //zabezpieczenie przed podwójnym użyciem jednej daty
                                        if (!empty(array_filter($output, function ($e) use (&$dateMatches, &$i, &$nearbyText) {
                                            return $e['Index'] == $nearbyText->getRawIdx() + $dateMatches[0][$i][1];
                                        }))) {
                                            continue;
                                        }

                                        $ret = [
                                            'Name' => $dateKey['name'],
                                            'Value' => $dateMatches[0][$i][0],
                                            'Index' => $nearbyText->getRawIdx() + $dateMatches[0][$i][1]
                                        ];
                                        if ($ret != null) {
                                            break;
                                        }
                                    }
                                }
                                if ($ret != null) {
                                    break;
                                }
                            }
                            if ($ret != null) {
                                break;
                            }
                        }
                        if ($ret != null) {
                            array_push($output, $ret);
                        }
                    }
                }
            }
        }
    }

    return $output;
}