<?php
require_once './ParallelUpload.php';
// session_start();
// session_destroy();

if(
    isset($_GET['upload']) &&
    isset($_FILES["file"]) &&
    filesize($_FILES["file"]["tmp_name"]) > 0 &&
    isset($_POST["parts"]) &&
    is_numeric($_POST["parts"]) &&
    $_POST["parts"] > 1 &&
    isset($_POST["part"]) &&
    is_numeric($_POST["part"]) &&
    $_POST["part"] >= 1 ){
    $Upload = new ParallelUpload([]);
    $Upload
    ->parts($_POST["parts"])
    ->upload($_POST["part"], file_get_contents($_FILES["file"]["tmp_name"]), $_FILES["file"]["name"])
    ->dd();

    if($Upload->done()){
        echo "upload done <br>";
        echo $Upload->merge('./fin/'.$_FILES["file"]["name"]);
    }else{
        echo "still in progress";
    }
}else{
    echo "No match";
}
