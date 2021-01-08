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

//DATY
$daty = [
    'values' => [
        ['name' => "data sprzedaży", 'pattern' => "/data\ssprzedaży/iu"],
        ['name' => "z dnia", 'pattern' => "/z dnia/i"],
        ['name' => "data wystawienia", 'pattern' => "/data\swystawienia/i"],
        ['name' => "data dostarczenia", 'pattern' => "/data\sdostarczenia/i"],
        ['name' => "data dodania", 'pattern' => "/data\sdodania/i"],
        ['name' => "data zapłaty", 'pattern' => "/data\szapłaty/i"]
    ],
    'patterns' => ["/(\d{2})[.\/-](\d{2})[.\/-](\d{4})/m", "/(\d{4})[.\/-](\d{2})[.\/-](\d{2})/m"]
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

        $pageContent->setSearchRadius($_GET['radius']);

        $output = [];

        //iterujemy po każdym poszukiwanym rodzaju daty
        foreach ($daty['values'] as $dateKey) {
            //sprawdzamy czy tekst zawiera klucz danego rodzaju daty
            if (preg_match_all($dateKey['pattern'], $pageContent->getRawText(), $matches, PREG_OFFSET_CAPTURE)) {
                //iterujemy przez każdy klucz znaleziony w tekście
                foreach ($matches[0] as $match) {
                    $index = $match[1];
                    //pobieramy obiekt tekstowy w którym znajduje się znaleziony klucz
                    $textObj = $pageContent->getTextValueFromRawIndex($index);
                    if ($textObj) {
                        //pobieramy obiekty tekstowe w okolicy znalezionego obiektu
                        $nearbyTexts = $pageContent->getNearbyRawTexts($textObj['x'], $textObj['y']);

                        $ret = null;
                        //sprawdzamy po kolei znalezione obiekty czy zawierają datę
                        foreach ($nearbyTexts as $nearbyText) {
                            foreach ($daty['patterns'] as $pattern) {
                                //jeśli znaleziono datę to dodajemy ją do wyniku i wychodzimy z pętli
                                if (preg_match_all($pattern, $nearbyText['text'], $dateMatches, PREG_OFFSET_CAPTURE)) {
                                    for ($i = 0; $i < count($dateMatches[0]); $i++) {

                                        //zabezpieczenie przed podwójnym użyciem jednej daty
                                        if (!empty(array_filter($output, function ($e) use (&$dateMatches, &$i, &$nearbyText) {
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
        foreach ($output as $data) {
            echo $data['name'] . ": " . $data['value'];
            echo "<br>";
        }
        echo "<br></div>";
    }

}

?>
</body>
</html>