<html>
<body>
<div style="width: 200px">
    <form method="post" action="index.php" enctype="multipart/form-data" style="display: flex;flex-direction: column">
        <input type="submit" value="Powrót" name="return">
    </form>
</div>

<?php

include 'vendor/autoload.php';

require_once 'Pdf.php';

$ghostscriptCommand = "gs";
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $ghostscriptCommand = "gswin64c";
}

//KWOTY
$kwotyPatterny = [
    'vat' => "/((?:(\d{1,2})%)|zw|np)/i",
    'decimal' => "/(?:[\s]?\d{1,3})+(?:[.,]\d{2})/"
];

if(isset($_GET['file']) && $_GET['file'] != "" && isset($_GET['radius'])) {
    //konwertowanie pliku na wersję 1.4
    $cmd = $ghostscriptCommand . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default -dNOPAUSE -dQUIET -dBATCH -dDetectDuplicateImages -dCompressFonts=true -r150 -sOutputFile=' . "input.pdf" . ' "pliki/' . urldecode($_GET['file']) . '"';//-sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dCompressFonts=false -dBATCH -dNOPAUSE -sOutputFile=' . "input.pdf" . ' ' . "trudny.pdf";
    exec($cmd, $output, $return);

    if ($return) {
        echo "ghostscript problem <br>";
        echo json_encode($output);
        return;
    }

    //dekodowanie pliku
    $cmd = 'qpdf --decrypt --stream-data=uncompress input.pdf input_uncompressed.pdf';
    exec($cmd, $output, $return);

    if ($return) {
        echo "qpdf problem";
        echo json_encode($output);
        return;
    }

    $pdf = new Pdf("input_uncompressed.pdf");

    foreach($pdf->getPages() as $pageKey => $page) {
        $pageContent = $page->getPageContents();
        echo "<b>Strona: " . $pageKey + 1 . "</b><br><div style='padding-left: 20px'>";

        //ustawiamy zasieg szukania sąsiadujących obiektów- im większy tym większa szansa, że znajdziemy niechciany obiekt, im mniejszy tym mniejsza szansa, że znajdziemy pożądany obiekt
        $pageContent->setSearchRadius($_GET['radius']);

        $kwoty = [];

        //szukamy w zawartosci strony podatku vat
        if (preg_match_all($kwotyPatterny['vat'], $pageContent->getRawText(), $matches, PREG_OFFSET_CAPTURE)) {
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
                $textObj = $pageContent->getTextValueFromRawIndex($index);
                if ($textObj) {
                    //pobieramy sąsiadujące obiekty tekstowe
                    $nearbyTexts = $pageContent->getNearbyYCoordinateRawTexts($textObj['y']);

                    $decimals = [];
                    $subText = "";
                    foreach ($nearbyTexts as $nearbyText) {
                        $subText .= $nearbyText['text'];
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
                                'kwota brutto' => number_format($decimals[$j], 2, '.', ''),
                                'vat' => ltrim($matches[0][$i][0], "0"),
                                'kwota netto' => number_format($decimals[$key], 2, '.', '')
                            ];
                        } //taka liczba nie istnieje
                        else {
                            //sprawdzamy, czy wśród innych znalezionych liczb znajduje się taka, która jest kwotą brutto dla wybranej liczby i znalezionego podatku vat
                            $key = array_search(round($decimals[$j] / (1 + $vat), 2), $decimals);
                            if ($key !== false && $key != $j) {
                                //taka liczba istnieje, dodajemy do wyniku
                                $ret = [
                                    'kwota brutto' => number_format($decimals[$key], 2, '.', ''),
                                    'vat' => ltrim($matches[0][$i][0], "0"),
                                    'kwota netto' => number_format($decimals[$j], 2, '.', '')
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
                    if ($i == $j || $kwoty[$i]['vat'] != $kwoty[$j]['vat'] || array_key_exists('opis', $kwoty[$j])) {
                        continue;
                    }
                    //sumujemy te kwoty
                    $sum += $kwoty[$j]['kwota netto'];
                }
                //jesli suma pozostałych kwot jest równa sumie wybranej kwoty to znaczy, że ta kwota jest sumą pozostałych kwot- dodajemy jako podsumowanie
                if ($sum == $kwoty[$i]['kwota netto']) {
                    $kwoty[$i] = array('opis' => 'suma dla vat: ' . $kwoty[$i]['vat']) + $kwoty[$i];
                }
            }

            //sortowanie żeby wynik wyglądał przejrzyściej
            usort($kwoty, fn($a, $b) => array_key_exists("opis", $a) > array_key_exists("opis", $b) ? 1 : -1);

            //robimy całkowite podsumowanie wszystkiego
            $sumNetto = 0;
            $sumBrutto = 0;
            for ($i = 0; $i < count($kwoty); $i++) {
                if (!array_key_exists('opis', $kwoty[$i])) {
                    $sumNetto += $kwoty[$i]['kwota netto'];
                    $sumBrutto += $kwoty[$i]['kwota brutto'];
                }
            }

            if ($sumNetto != 0 && $sumBrutto != 0) {
                array_push($kwoty, ['opis' => 'podsumowanie', 'kwota netto' => number_format($sumNetto, 2, '.', '') , 'kwota brutto' => number_format($sumBrutto, 2, '.', '')]);
            }

        }

        echo "KWOTY: <br>";
        foreach ($kwoty as $kwota) {
            if (array_key_exists("opis", $kwota)) {
                echo $kwota['opis'] . "<br>";
            }
            echo "brutto: " . $kwota['kwota brutto'] . " zł<br>";
            echo "netto: " . $kwota['kwota netto'] . " zł<br>";
            if (array_key_exists("vat", $kwota)) {
                echo "vat: " . $kwota['vat'] . "<br><br>";
            }
        }
        echo "</div><br><br>";

    }

}

?>
</body>
</html>