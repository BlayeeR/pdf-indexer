<?php
include 'vendor/autoload.php';

require_once 'Pdf.php';

$filename="trudny.pdf";

$cmd = 'gswin64c -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default -dNOPAUSE -dQUIET -dBATCH -dDetectDuplicateImages -dCompressFonts=true -r150 -sOutputFile=' . "input.pdf" . ' ' . $filename;//-sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dCompressFonts=false -dBATCH -dNOPAUSE -sOutputFile=' . "input.pdf" . ' ' . "trudny.pdf";
exec($cmd);

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
    $pageContent = $page->getPageContents();
    $text = $pageContent->getRawText();

    foreach($daty['values'] as $dateKey) {
        if (preg_match_all($dateKey['pattern'], $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $index = $match[1];
                $textObj = $pageContent->getTextValueFromRawIndex($index);
                if ($textObj) {
                    $nearbyTexts = $pageContent->getNearbyTexts($textObj['x'], $textObj['y']);

                    $ret = null;
                    foreach ($nearbyTexts as $nearbyText) {
                        foreach ($daty['patterns'] as $pattern) {
                            if (preg_match($pattern, $nearbyText['text'], $dateMatch)) {
                                $ret = [
                                    'name' => $dateKey['name'],
                                    'value' => $dateMatch[0]
                                ];
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
    'decimal'=> "/\d+(?:[.,]\d{2})/"
];
$kwoty =[];

foreach($pdf->getPages() as $page) {
    $pageContent = $page->getPageContents();
    $text = $pageContent->getRawText();

    if(preg_match_all($kwotyPatterny['vat'], $text, $matches, PREG_OFFSET_CAPTURE)) {
        for($i = 0; $i < count($matches[0]); $i++) {
            $vat = 0;
            if($matches[2][$i][0] != "") {
                $vat = (float)$matches[2][$i][0] * 0.01;
            }
            else {
                $vat = 0;
            }

            $ret = null;

            $index = $matches[0][$i][1];
            $textObj = $pageContent->getTextValueFromRawIndex($index);
            if ($textObj) {
                $nearbyTexts = $pageContent->getNearbyYCoordinateTexts($textObj['y']);

                $decimals = [];
                foreach ($nearbyTexts as $nearbyText) {
                    if(preg_match_all($kwotyPatterny['decimal'], $nearbyText['text'], $decimalMatches)) {
                        foreach($decimalMatches[0] as $decimal) {
                            array_push($decimals, (float)str_replace(",", ".", $decimal));
                        }
                    }
                }

                for($j = 0; $j < count($decimals); $j++) {
                    $key = array_search(round($decimals[$j] * (1 + $vat), 2), $decimals);
                    if($key !== false && $key != $j) {
                        $ret = [
                            'kwota brutto'=>number_format($decimals[$j], 2, '.', ''),
                            'vat'=>$matches[0][$i][0],
                            'kwota netto'=>number_format($decimals[$key], 2, '.', '')
                        ];
                    }
                    else {
                        $key = array_search(round($decimals[$j]/(1 + $vat), 2), $decimals);
                        if($key !== false && $key != $j) {
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

        for($i = 0; $i < count($kwoty); $i++) {
            $sum = 0;
            for($j = 0; $j < count($kwoty); $j++) {
                if($i == $j || $kwoty[$i]['vat'] != $kwoty[$j]['vat'] || array_key_exists('opis', $kwoty[$j])) {
                    continue;
                }
                $sum += $kwoty[$j]['kwota netto'];
            }
            if($sum == $kwoty[$i]['kwota netto'] ) {
                $kwoty[$i] = array('opis' => 'suma dla vat: ' . $kwoty[$i]['vat']) + $kwoty[$i];
            }
        }

        usort($kwoty, fn($a, $b) => array_key_exists("opis", $a) > array_key_exists("opis", $b) ? 1 : -1);

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



