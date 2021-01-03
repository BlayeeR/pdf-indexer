<?php
include 'vendor/autoload.php';

require_once 'Pdf.php';

//nazwa pliku
$filename="simple_invoice_pl.pdf";

//konwertowanie pliku na wersję 1.4
$cmd = 'gswin64c -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default -dNOPAUSE -dQUIET -dBATCH -dDetectDuplicateImages -dCompressFonts=true -r150 -sOutputFile=' . "input.pdf" . ' ' . $filename;//-sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dCompressFonts=false -dBATCH -dNOPAUSE -sOutputFile=' . "input.pdf" . ' ' . "trudny.pdf";
exec($cmd);

//dekodowanie pliku
$cmd = 'qpdf --decrypt --stream-data=uncompress input.pdf input_uncompressed.pdf';
exec($cmd);

$pdf = new Pdf("input_uncompressed.pdf");

//TEKST
echo "TEKST: ";
foreach($pdf->getPages() as $page) {
    $pageContent = $page->getPageContents();
    echo $pageContent->getText();
    echo "\r\n";
}

//DATY
echo "DATY: ";
$daty = [
    'values'=>[
        ['name'=>"data sprzedaży",'pattern'=> "/data\ssprzedaży/i"],
        ['name'=>"z dnia",'pattern'=> "/z dnia/i"],
        ['name'=>"data wystawienia",'pattern'=> "/data\swystawienia/i"],
        ['name'=>"data dostarczenia",'pattern'=> "/data\sdostarczenia/i"],
        ['name'=>"data dodania",'pattern'=> "/data\sdodania/i"],
        ['name'=>"data zapłaty",'pattern'=> "/data\szapłaty/i"]
    ],
    'patterns'=>["/(\d{2})[.\/-](\d{2})[.\/-](\d{4})/m", "/(\d{4})[.\/-](\d{2})[.\/-](\d{2})/m"]
];

$output = [];
foreach($pdf->getPages() as $page) {
    //pobranie zawartości strony
    $pageContent = $page->getPageContents();
    //pobranie tekstu bez białych znaków
    $text = $pageContent->getRawText();

    //ustawiamy zasieg szukania sąsiadujących obiektów- im większy tym większa szansa, że znajdziemy niechciany obiekt, im mniejszy tym mniejsza szansa, że znajdziemy pożądany obiekt
    $pageContent->setSearchRadius(15);

    //iterujemy po każdym poszukiwanym rodzaju daty
    foreach($daty['values'] as $dateKey) {
        //sprawdzamy czy tekst zawiera klucz danego rodzaju daty
        if (preg_match_all($dateKey['pattern'], $text, $matches, PREG_OFFSET_CAPTURE)) {
            //iterujemy przez każdy klucz znaleziony w tekście
            foreach ($matches[0] as $match) {
                $index = $match[1];
                //pobieramy obiekt tekstowy w którym znajduje się znaleziony klucz
                $textObj = $pageContent->getTextValueFromRawIndex($index);
                if ($textObj) {
                    //pobieramy obiekty tekstowe w okolicy znalezionego obiektu
                    $nearbyTexts = $pageContent->getNearbyTexts($textObj['x'], $textObj['y']);

                    $ret = null;
                    //sprawdzamy po kolei znalezione obiekty czy zawierają datę
                    foreach ($nearbyTexts as $nearbyText) {
                        foreach ($daty['patterns'] as $pattern) {
                            //jeśli znaleziono datę to dodajemy ją do wyniku i wychodzimy z pętli
                            if (preg_match_all($pattern, $nearbyText['text'], $dateMatches, PREG_OFFSET_CAPTURE)) {
                                for($i = 0; $i < count($dateMatches[0]); $i++) {

                                    //zabezpieczenie przed podwójnym użyciem jednej daty
                                    if(!empty(array_filter($output, function($e) use (&$dateMatches, &$i, &$nearbyText) {
                                        return $e['index'] == $nearbyText['index'] + $dateMatches[0][$i][1];
                                    }))) {
                                        continue;
                                    }

                                    $ret = [
                                        'name' => $dateKey['name'],
                                        'value' => $dateMatches[0][$i][0],
                                        'index' => $nearbyText['index'] + $dateMatches[0][$i][1]
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
};

echo json_encode($output, JSON_UNESCAPED_UNICODE);
echo "\r\n";

//KWOTY
echo "KWOTY: ";
$kwotyPatterny = [
    'vat'=> "/((?:(\d{1,2})%)|zw|np)/i",
    'decimal'=> "/(?:[\s]?\d{1,3})+(?:[.,]\d{2})/"
];
$kwoty =[];

foreach($pdf->getPages() as $page) {
    //pobranie zawartości strony
    $pageContent = $page->getPageContents();
    //pobranie tekstu bez białych znaków
    $text = $pageContent->getRawText();

    //ustawiamy zasieg szukania sąsiadujących obiektów- im większy tym większa szansa, że znajdziemy niechciany obiekt, im mniejszy tym mniejsza szansa, że znajdziemy pożądany obiekt
    $pageContent->setSearchRadius(10);

    //szukamy w zawartosci strony podatku vat
    if(preg_match_all($kwotyPatterny['vat'], $text, $matches, PREG_OFFSET_CAPTURE)) {
        //iterujemy przez wszystkie znalezione obiekty
        for($i = 0; $i < count($matches[0]); $i++) {
            $vat = 0;
            //ustalamy wartość podatku
            if($matches[2][$i][0] != "") {
                $vat = (float)$matches[2][$i][0] * 0.01;
            }
            else {
                $vat = 0;
            }

            $ret = null;

            $index = $matches[0][$i][1];
            //pobieramy ten obiekt tekstowy, w którym znajduje się znaleziony podatek
            $textObj = $pageContent->getTextValueFromRawIndex($index);
            if ($textObj) {
                //pobieramy sąsiadujące obiekty tekstowe
                $nearbyTexts = $pageContent->getNearbyYCoordinateTexts($textObj['y']);

                $decimals = [];
                $subText="";
                foreach ($nearbyTexts as $nearbyText) {
                    $subText .= $nearbyText['text'];
                    //sprawdzamy czy któryś obiekt sąsiadujący jest liczbą, jesli tak to dodajemy go do znalezionych liczb
                }
                if(preg_match_all($kwotyPatterny['decimal'], $subText, $decimalMatches)) {
                    foreach($decimalMatches[0] as $decimal) {
                        $dec = str_replace(
                            ["\n", "\r", "\t", "\f"],
                            ["", "", "", ""],
                            $decimal
                        );
                        array_push($decimals, (float)str_replace(",", ".", $dec));
                    }
                }

                //iterujemy przez wszystkie znalezione wyżej liczby
                for($j = 0; $j < count($decimals); $j++) {
                    //sprawdzamy, czy wśród innych znalezionych liczb, znajduje się taka, która jest kwotą netto dla wybranej liczby i znalezionego podatku vat
                    $key = array_search(round($decimals[$j] * (1 + $vat), 2), $decimals);
                    if($key !== false && $key != $j) {
                        //taka liczba istnieje, więc dodajemy do wyniku
                        $ret = [
                            'kwota brutto'=>number_format($decimals[$j], 2, '.', ''),
                            'vat'=>$matches[0][$i][0],
                            'kwota netto'=>number_format($decimals[$key], 2, '.', '')
                        ];
                    }
                    //taka liczba nie istnieje
                    else {
                        //sprawdzamy, czy wśród innych znalezionych liczb znajduje się taka, która jest kwotą brutto dla wybranej liczby i znalezionego podatku vat
                        $key = array_search(round($decimals[$j]/(1 + $vat), 2), $decimals);
                        if($key !== false && $key != $j) {
                            //taka liczba istnieje, dodajemy do wyniku
                            $ret = [
                                'kwota brutto'=>number_format($decimals[$key], 2, '.', ''),
                                'vat'=>$matches[0][$i][0],
                                'kwota netto'=>number_format($decimals[$j], 2, '.', '')
                            ];
                        }
                    }

                    if($ret != null) {
                        break;
                    }
                }
            }

            if($ret != null) {
                array_push($kwoty, $ret);
            }
        }

        //sprawdzanie czy znaleziona kwota jest sumą kwot dla danego  podatku vat
        //iterujemy przez wszystkie znalezione kwoty
        for($i = 0; $i < count($kwoty); $i++) {
            $sum = 0;

            //iterujemy ponownie, lecz tylko przez inne kwoty w danej tablicy i takie, które nie są sumą innych kwot oraz posiadają taką samą wartość podatku vat
            for($j = 0; $j < count($kwoty); $j++) {
                if($i == $j || $kwoty[$i]['vat'] != $kwoty[$j]['vat'] || array_key_exists('opis', $kwoty[$j])) {
                    continue;
                }
                //sumujemy te kwoty
                $sum += $kwoty[$j]['kwota netto'];
            }
            //jesli suma pozostałych kwot jest równa sumie wybranej kwoty to znaczy, że ta kwota jest sumą pozostałych kwot- dodajemy jako podsumowanie
            if($sum == $kwoty[$i]['kwota netto'] ) {
                $kwoty[$i] = array('opis' => 'suma dla vat: ' . $kwoty[$i]['vat']) + $kwoty[$i];
            }
        }

        //sortowanie żeby wynik wyglądał przejrzyściej
        usort($kwoty, fn($a, $b) => array_key_exists("opis", $a) > array_key_exists("opis", $b) ? 1 : -1);

        //robimy całkowite podsumowanie wszystkiego
        $sumNetto = 0;
        $sumBrutto = 0;
        for($i = 0; $i < count($kwoty); $i++) {
            if(array_key_exists('opis', $kwoty[$i])) {
                $sumNetto = $kwoty[$i]['kwota netto'];
                $sumBrutto = $kwoty[$i]['kwota brutto'];
            }
        }

        if($sumNetto != 0 && $sumBrutto != 0) {
            array_push($kwoty, ['opis'=>'podsumowanie', 'kwota netto'=>$sumNetto, 'kwota brutto'=>$sumBrutto]);
        }

    }
}

echo json_encode($kwoty);
echo "\r\n";



