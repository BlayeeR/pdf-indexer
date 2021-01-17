<?php

require_once 'vendor/autoload.php';
require_once 'Pdf.php';

use PdfIndexer\Models as Models;

function getTextValueFromIndex($index, $textObjects) {
    for($i = count($textObjects) - 1; $i >= 0; $i--) {
        if($index >= $textObjects[$i]->getIdx()) {
            return $textObjects[$i];
        }
    }
    return null;
}

function getNearbyTexts($x, $y, $textObjects, $searchRadius): array {
    $ret = [];

    foreach($textObjects as $text) {
        if(($text->getX() >= $x-$searchRadius) && $text->getY() >= $y-$searchRadius && $text->getY() <= $y +$searchRadius) {
            $ret[$text->getIdx()] = $text;
        }
    }
    return $ret;
}

function findInfoStep1(Models\PdfFile $file, int $searchRadius) {
    $daneOsoboowe = [
        'values' => [
            ['name' => "Nadawca", 'pattern' => "/nadawca/i"],
            ['name' => "Adresat", 'pattern' => "/adresat/i"],
            ['name' => "Odbiorca", 'pattern' => "/odbiorca/i"],
            ['name' => "Sprzedawca", 'pattern' => "/sprzedawca/i"],
            ['name' => "Nabywca", 'pattern' => "/nabywca/i"],
            ['name' => "Płatnik", 'pattern' => "/płatnik/i"],
            ['name' => "Podatnik", 'pattern' => "/podatnik/i"],
        ]
    ];

    $output = [];

    foreach($file->getPages() as $pageKey => $page) {

        foreach ($daneOsoboowe['values'] as $daneKey) {
            //sprawdzamy czy tekst zawiera klucz danego rodzaju daty
            if (preg_match_all($daneKey['pattern'], $page->getText(), $matches, PREG_OFFSET_CAPTURE)) {
                //iterujemy przez każdy klucz znaleziony w tekście
                foreach ($matches[0] as $match) {
                    $index = $match[1];

                    $textObj = getTextValueFromIndex($index, $page->getTextObjects());
                    if ($textObj) {
                        $nearbyTexts = getNearbyTexts($textObj->getX(), $textObj->getY(), $page->getTextObjects(), $searchRadius);

                        if (preg_match_all("/[\p{Lu}](?:[\p{Ll}]+|[\p{Lu}]+)/um", $page->getText(), $matchesMiasto, PREG_OFFSET_CAPTURE)) {
                            $nearby = [];
                            foreach ($matchesMiasto[0] as $matchMiasto) {
                                $miastoIndex = $matchMiasto[1];
                                $miastoTextObj = getTextValueFromIndex($miastoIndex, $page->getTextObjects());
                                if ($miastoTextObj && isset($nearbyTexts[$miastoTextObj->getIdx()])) {
                                    array_push($nearby, $matchMiasto[0]);
                                }
                            }
                            if (count($nearby) > 0) {
                                array_push($output, [
                                    'Name' => $match[0],
                                    'Nearby' => $nearby]);
                            }
                        }
                    }
                }
            }
        }
    }

    return $output;
}