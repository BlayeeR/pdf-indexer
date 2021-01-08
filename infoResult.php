<html>
<body>
<div style="width: 200px">
    <form method="post" action="index.php" enctype="multipart/form-data" style="display: flex;flex-direction: column">
        <input type="submit" value="Powrót" name="return">
    </form>
</div>

<?php

$data = [];
foreach($_POST as $dataKey => $postData) {
    if(preg_match("/(?<pageIndex>\d+);(?<itemIndex>\d+);(?s)(?<infoName>.*)/", $dataKey, $postMatch)) {
        array_push($data, [
            'pageIndex'=> $postMatch['pageIndex'],
            'itemIndex'=> $postMatch['itemIndex'],
            'infoName'=> $postMatch['infoName'],
            'itemValue'=> $postData

        ]);
    }
}

if(isset($_POST['pages'])) {
    for($i =0; $i < $_POST['pages']; $i++) {

        $results = array_filter($data, function($e) use (&$i) {
            return $e['pageIndex'] == $i;
        });

        $arr = array();
        foreach ($results as $key => $item) {
            $arr[$item['infoName']][$key] = $item;
        }
        ksort($arr, SORT_NUMERIC);

        echo "<b>Strona: " . $i + 1 . "</b><br><div style='padding-left: 20px'>";

            foreach($arr as $arrKey => $arrVal) {
                echo $arrKey . ":<br>";
                $val = "";
                foreach($arrVal as $v) {
                    $val .= $v['itemValue'] . " ";
                }
                echo $val . "<br>";
                echo "<br>";
            }

        echo "</div><br><br>";

        $test = "";
    }
}


?>

</body>
</html>