<?php
    include 'vendor/autoload.php';

    require_once 'Pdf.php';

    $target_dir = "pliki/";

    if(array_key_exists("fileToUpload", $_FILES)){
        $filePath = $target_dir . $_FILES["fileToUpload"]["name"];
        move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $filePath);
    }

    $dateRadius = 15;
    if(array_key_exists("dateRadius", $_POST) && is_numeric($_POST['dateRadius'])) {
        $dateRadius = (int)$_POST['dateRadius'];
    }

    $decimalRadius = 10;
    if(array_key_exists("decimalRadius", $_POST) && is_numeric($_POST['decimalRadius'])) {
        $decimalRadius = (int)$_POST['decimalRadius'];
    }

    $ghostscriptCommand = "gs";
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $ghostscriptCommand = "gswin64c";
    }
?>

<html>
    <body>
        <div style="width: 200px">
            <form method="post" action="index.php" enctype="multipart/form-data" style="display: flex;flex-direction: column">
            <br>
            Zasięg szukania dat
            <input type="number" value="<?php echo (isset($dateRadius))?$dateRadius:15;?>" id="dateRadius" name="dateRadius" >
            <br>
            Zasięg szukania kwot
            <input type="number" value="<?php echo (isset($decimalRadius))?$decimalRadius:10;?>" id="decimalRadius" name="decimalRadius" >
            <br>
            <input type="file" name="fileToUpload" id="fileToUpload" accept="application/pdf">
            <input type="submit" value="Indeksuj" name="submit">
            </form>
        </div>
    </body>
</html>

<?php
    if(isset($filePath) && $filePath != "") {
        //konwertowanie pliku na wersję 1.4
        $cmd = $ghostscriptCommand . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default -dNOPAUSE -dQUIET -dBATCH -dDetectDuplicateImages -dCompressFonts=true -r150 -sOutputFile=' . "input.pdf" . ' "' . $filePath . '"';//-sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dCompressFonts=false -dBATCH -dNOPAUSE -sOutputFile=' . "input.pdf" . ' ' . "trudny.pdf";
        exec($cmd, $output, $return);

        if($return) {
            echo "ghostscript problem <br>";
            echo json_encode($output);
            return;
        }

        //dekodowanie pliku
        $cmd = 'qpdf --decrypt --stream-data=uncompress input.pdf input_uncompressed.pdf';
        exec($cmd, $output, $return);

        if($return) {
            echo "qpdf problem";
        }

        $pdf = new Pdf("input_uncompressed.pdf");

        //DATY
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

        foreach($pdf->getPages() as $pageKey => $page) {
            echo "<b>Strona: " . $pageKey + 1 . "</b><br><div style='padding-left: 20px'>";

            //pobranie zawartości strony
            $pageContent = $page->getPageContents();

            echo "TEKST: <br>";
            echo $pageContent->getText();
            echo "<br><br>";

            //pobranie tekstu bez białych znaków
            $text = $pageContent->getRawText();

            //ustawiamy zasieg szukania sąsiadujących obiektów- im większy tym większa szansa, że znajdziemy niechciany obiekt, im mniejszy tym mniejsza szansa, że znajdziemy pożądany obiekt
            $pageContent->setSearchRadius($dateRadius);

            $output = [];

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

            echo "DATY: <br>";
            foreach($output as $data) {
                echo $data['name'] . ": " . $data['value'];
                echo "<br>";
            }
            echo "<br>";
        
            //KWOTY
            echo "KWOTY: <br>";
            $kwotyPatterny = [
                'vat'=> "/((?:(\d{1,2})%)|zw|np)/i",
                'decimal'=> "/(?:[\s]?\d{1,3})+(?:[.,]\d{2})/"
            ];
        
            $kwoty =[];

            //ustawiamy zasieg szukania sąsiadujących obiektów- im większy tym większa szansa, że znajdziemy niechciany obiekt, im mniejszy tym mniejsza szansa, że znajdziemy pożądany obiekt
            $pageContent->setSearchRadius($decimalRadius);

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
                                    [" ","\n", "\r", "\t", "\f", ","],
                                    ["", "", "", "", "", "."],
                                    $decimal
                                );
                                array_push($decimals, (float)$dec);
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
                                    'vat'=>ltrim($matches[0][$i][0], "0"),
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
                                        'vat'=>ltrim($matches[0][$i][0], "0"),
                                        'kwota netto'=>number_format($decimals[$j], 2, '.', '')
                                    ];
                                }
                            }

                            if($ret != null) {
                                break;
                            }
                        }
                    }

                    if(isset($ret) && $ret != null) {
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
                    if(!array_key_exists('opis', $kwoty[$i])) {
                        $sumNetto = $kwoty[$i]['kwota netto'];
                        $sumBrutto = $kwoty[$i]['kwota brutto'];
                    }
                }

                if($sumNetto != 0 && $sumBrutto != 0) {
                    array_push($kwoty, ['opis'=>'podsumowanie', 'kwota netto'=>$sumNetto, 'kwota brutto'=>$sumBrutto]);
                }

            }

            foreach($kwoty as $kwota) {
                if(array_key_exists("opis", $kwota)) {
                    echo $kwota['opis'] . "<br>";
                }
                echo "brutto: " . $kwota['kwota brutto'] . " zł<br>";
                echo "netto: " . $kwota['kwota netto'] . " zł<br>";
                if(array_key_exists("vat", $kwota)) {
                    echo "vat: " . $kwota['vat'] . "<br><br>";
                }
            }
            echo "<br></div>";
        }
    }


?>