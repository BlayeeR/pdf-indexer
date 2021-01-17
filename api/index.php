<?php
    include 'vendor/autoload.php';

    require_once 'Pdf.php';
    require_once 'global.php';

    $target_dir = "pliki/";

    $dateRadius = 15;
    if(array_key_exists("dateRadius", $_POST) && is_numeric($_POST['dateRadius'])) {
        $dateRadius = (int)$_POST['dateRadius'];
    }

    $amountRadius = 10;
    if(array_key_exists("amountRadius", $_POST) && is_numeric($_POST['amountRadius'])) {
        $amountRadius = (int)$_POST['amountRadius'];
    }

    $infoRadius = 25;
    if(array_key_exists("infoRadius", $_POST) && is_numeric($_POST['infoRadius'])) {
        $infoRadius = (int)$_POST['infoRadius'];
    }

    if(array_key_exists("fileToUpload", $_FILES)) {
        $filePath = $target_dir . $_FILES["fileToUpload"]["name"];
        move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $filePath);

        $url = "index.php";
        if(isset($_POST['text'])) {
            $url = "text.php?file=" . urlencode($_FILES["fileToUpload"]["name"]);
        }
        else if(isset($_POST['date'])) {
            $url = "date.php?file=" . urlencode($_FILES["fileToUpload"]["name"]) . "&radius=" . $dateRadius;
        }
        else if(isset($_POST['amount'])) {
            $url = "amount.php?file=" . urlencode($_FILES["fileToUpload"]["name"]) . "&radius=" . $amountRadius;
        }
        else if(isset($_POST['info'])) {
            $url = "info.php?file=" . urlencode($_FILES["fileToUpload"]["name"]) . "&radius=" . $infoRadius;
        }
        header("Location: " . $url);
        die();
    }

?>

<html>
<body>
    <div style="width: 300px">
        <form method="post" action="index.php" enctype="multipart/form-data" style="display: flex;flex-direction: column">
        <br>
        Zasięg szukania dat
        <input type="number" value="<?php echo (isset($dateRadius))?$dateRadius:15;?>" id="dateRadius" name="dateRadius" >
        <br>
        Zasięg szukania kwot
        <input type="number" value="<?php echo (isset($amountRadius))?$amountRadius:10;?>" id="amountRadius" name="amountRadius" >
        <br>
        Zasięg szukania danych osobowych
        <input type="number" value="<?php echo (isset($infoRadius))?$infoRadius:25;?>" id="$infoRadius" name="$infoRadius" >
        <br>
        <input type="file" name="fileToUpload" id="fileToUpload" accept="application/pdf">
        <input type="submit" value="Wyodrębnij tekst" name="text">
        <input type="submit" value="Wyodrębnij daty" name="date">
        <input type="submit" value="Wyodrębnij kwoty" name="amount">
        <input type="submit" value="Wyodrębnij dane osobowe" name="info">
        </form>
    </div>
</body>
</html>

