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

        if(isset($_GET['file']) && $_GET['file'] != "") {
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

                echo "TEKST: <br>";
                echo $pageContent->getText();
                echo "<br><br>";
            }

        }

        ?>
    </body>
</html>
