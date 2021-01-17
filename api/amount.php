<?php

require_once 'vendor/autoload.php';
require_once 'Pdf.php';

use PdfIndexer\Models as Models;

function getTextValueFromRawIndex($index, $textObjects) {
    for($i = count($textObjects) - 1; $i >= 0; $i--) {
        if($index >= $textObjects[$i]->getRawIdx()) {
            return $textObjects[$i];
        }
    }
    return null;
}

function getNearbyYCoordinateRawTexts($y, $textObjects, $searchRadius): array {
    $ret = [];

    foreach($textObjects as $text) {
        if($text->getY() >= $y-$searchRadius && $text->getY() <= $y + $searchRadius) {
            array_push($ret, $text);
        }
    }

    return $ret;
}

function findAmounts(Models\PdfFile $pdf, int $searchradius) {
    $kwotyPatterny = [
        'vat' => "/((?:(\d{1,2})%)|zw|np)/i",
        'decimal' => "/(?:[\s]?\d{1,3})+(?:[.,]\d{2})/"
    ];

    $output = [];

    foreach($pdf->getPages() as $pageKey => $page) {
        $kwoty = [];

        //szukamy w zawartosci strony podatku vat
        if (preg_match_all($kwotyPatterny['vat'], $page->getRawText(), $matches, PREG_OFFSET_CAPTURE)) {
            //iterujemy przez wszystkie znalezione obiekty
            for ($i = 0; $i < count($matches[0]); $i++) {
                $vat = 0;
                //ustalamy wartość podatku
                if ($matches[2][$i][0] != "") {
                    $vat = (float)$matches[2][$i][0] * 0.01;
                } else {
                    $vat = 0;
                }

                $ret = null;

                $index = $matches[0][$i][1];
                //pobieramy ten obiekt tekstowy, w którym znajduje się znaleziony podatek
                $textObj = getTextValueFromRawIndex($index, $page->getTextObjects());
                if ($textObj) {


                    //pobieramy sąsiadujące obiekty tekstowe
                    $nearbyTexts = getNearbyYCoordinateRawTexts($textObj->getY(), $page->getTextObjects(), $searchradius);

                    $decimals = [];
                    $subText = "";
                    foreach ($nearbyTexts as $nearbyText) {
                        $subText .= $nearbyText->getText();
                        //sprawdzamy czy któryś obiekt sąsiadujący jest liczbą, jesli tak to dodajemy go do znalezionych liczb
                    }
                    if (preg_match_all($kwotyPatterny['decimal'], $subText, $decimalMatches)) {
                        foreach ($decimalMatches[0] as $decimal) {
                            $dec = str_replace(
                                [" ", "\n", "\r", "\t", "\f", ","],
                                ["", "", "", "", "", "."],
                                $decimal
                            );
                            array_push($decimals, (float)$dec);
                        }
                    }

                    //iterujemy przez wszystkie znalezione wyżej liczby
                    for ($j = 0; $j < count($decimals); $j++) {
                        //sprawdzamy, czy wśród innych znalezionych liczb, znajduje się taka, która jest kwotą netto dla wybranej liczby i znalezionego podatku vat
                        $key = array_search(round($decimals[$j] * (1 + $vat), 2), $decimals);
                        if ($key !== false && $key != $j) {
                            //taka liczba istnieje, więc dodajemy do wyniku
                            $ret = [
                                'Brutto' => number_format($decimals[$j], 2, '.', ''),
                                'Vat' => ltrim($matches[0][$i][0], "0"),
                                'Netto' => number_format($decimals[$key], 2, '.', '')
                            ];
                        } //taka liczba nie istnieje
                        else {
                            //sprawdzamy, czy wśród innych znalezionych liczb znajduje się taka, która jest kwotą brutto dla wybranej liczby i znalezionego podatku vat
                            $key = array_search(round($decimals[$j] / (1 + $vat), 2), $decimals);
                            if ($key !== false && $key != $j) {
                                //taka liczba istnieje, dodajemy do wyniku
                                $ret = [
                                    'Brutto' => number_format($decimals[$key], 2, '.', ''),
                                    'Vat' => ltrim($matches[0][$i][0], "0"),
                                    'Netto' => number_format($decimals[$j], 2, '.', '')
                                ];
                            }
                        }

                        if ($ret != null) {
                            break;
                        }
                    }
                }

                if (isset($ret) && $ret != null) {
                    array_push($kwoty, $ret);
                }
            }

            //sprawdzanie czy znaleziona kwota jest sumą kwot dla danego  podatku vat
            //iterujemy przez wszystkie znalezione kwoty
            for ($i = 0; $i < count($kwoty); $i++) {
                $sum = 0;

                //iterujemy ponownie, lecz tylko przez inne kwoty w danej tablicy i takie, które nie są sumą innych kwot oraz posiadają taką samą wartość podatku vat
                for ($j = 0; $j < count($kwoty); $j++) {
                    if ($i == $j || $kwoty[$i]['Vat'] != $kwoty[$j]['Vat'] || array_key_exists('Description', $kwoty[$j])) {
                        continue;
                    }
                    //sumujemy te kwoty
                    $sum += $kwoty[$j]['Netto'];
                }
                //jesli suma pozostałych kwot jest równa sumie wybranej kwoty to znaczy, że ta kwota jest sumą pozostałych kwot- dodajemy jako podsumowanie
                if ($sum == $kwoty[$i]['Netto']) {
                    $kwoty[$i] = array('Description' => 'Suma dla VAT: ' . $kwoty[$i]['Vat']) + $kwoty[$i];
                }
            }

            //sortowanie żeby wynik wyglądał przejrzyściej
            usort($kwoty, fn($a, $b) => array_key_exists("Description", $a) > array_key_exists("Description", $b) ? 1 : -1);

            //robimy całkowite podsumowanie wszystkiego
            $sumNetto = 0;
            $sumBrutto = 0;
            for ($i = 0; $i < count($kwoty); $i++) {
                if (!array_key_exists('Description', $kwoty[$i])) {
                    $sumNetto += $kwoty[$i]['Netto'];
                    $sumBrutto += $kwoty[$i]['Brutto'];
                }
            }

            if ($sumNetto != 0 && $sumBrutto != 0) {
                array_push($kwoty, ['Description' => 'Podsumowanie', 'Netto' => number_format($sumNetto, 2, '.', '') , 'Brutto' => number_format($sumBrutto, 2, '.', '')]);
            }

        }

        foreach ($kwoty as $kwota) {
            array_push($output, $kwota);
        }
    }

    return $output;
}