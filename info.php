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

$daneOsoboowe = [
    'values' => [
        ['name' => "nadawca", 'pattern' => "/nadawca/i"],
        ['name' => "adresat", 'pattern' => "/adresat/i"],
        ['name' => "odbiorca", 'pattern' => "/odbiorca/i"],
        ['name' => "sprzedawca", 'pattern' => "/sprzedawca/i"],
        ['name' => "nabywca", 'pattern' => "/nabywca/i"],
        ['name' => "płatnik", 'pattern' => "/płatnik/i"],
        ['name' => "podatnik", 'pattern' => "/podatnik/i"],
    ]
];

$ghostscriptCommand = "gs";
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $ghostscriptCommand = "gswin64c";
}

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

    echo '<form method="post" action="infoResult.php">';
    echo '<input type="hidden" name="pages" value="' . count($pdf->getPages()) . '">';

    foreach($pdf->getPages() as $pageKey => $page) {
        $pageContent = $page->getPageContents();
        echo "<b>Strona: " . $pageKey + 1 . "</b><br><div style='padding-left: 20px'>";

        //ustawiamy zasieg szukania sąsiadujących obiektów- im większy tym większa szansa, że znajdziemy niechciany obiekt, im mniejszy tym mniejsza szansa, że znajdziemy pożądany obiekt
        $pageContent->setSearchRadius($_GET['radius']);

        $output = [];
        foreach ($daneOsoboowe['values'] as $daneKey) {
            //sprawdzamy czy tekst zawiera klucz danego rodzaju daty
            if (preg_match_all($daneKey['pattern'], $pageContent->getText(), $matches, PREG_OFFSET_CAPTURE)) {
                //iterujemy przez każdy klucz znaleziony w tekście
                foreach ($matches[0] as $match) {
                    $index = $match[1];

                    $textObj = $pageContent->getTextValueFromIndex($index);
                    if ($textObj) {
                        $nearbyTexts = $pageContent->getNearbyTexts($textObj['x'], $textObj['y']);

                        if (preg_match_all("/[\p{Lu}](?:[\p{Ll}]+|[\p{Lu}]+)/um", $pageContent->getText(), $matchesMiasto, PREG_OFFSET_CAPTURE)) {
                            $nearby = [];
                            foreach ($matchesMiasto[0] as $matchMiasto) {
                                $miastoIndex = $matchMiasto[1];
                                $miastoTextObj = $pageContent->getTextValueFromIndex($miastoIndex);
                                if ($miastoTextObj && isset($nearbyTexts[$miastoTextObj['index']])) {
                                    array_push($nearby, $matchMiasto[0]);
                                }
                            }
                            if (count($nearby) > 0) {
                                array_push($output, [
                                    'name' => $match[0],
                                    'nearby' => $nearby]);
                            }
                        }
                    }
                }
            }
        }

        if(count($output) > 0) {
            echo "znaznacz wartości dla znalezionych słów kluczowych<br><br>";
        }

        foreach($output as $o) {
            echo $o['name'];
            echo '<br>';
            foreach($o['nearby'] as $key => $nearby) {
                echo '<input type="checkbox" name="' . $pageKey . ";" . $key . ";" . $o['name'] . '" value="'.$nearby.'">'.$nearby.'<br>';
            }

            echo "<br><br>";
        }

        echo "</div><br><br>";
    }

    echo "<input type='submit' value='Zatwierdź' >";

}

?>

<form
</body>
</html>