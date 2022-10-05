<?php
require_once './ParallelUpload.php';
// session_start();
// session_destroy();

if(isset($_GET['upload'])){
    $Upload = new ParallelUpload([]);
    $Upload
    ->parts(1)
    ->upload(1, file_get_contents($_FILES["file"]["tmp_name"]), $_FILES["file"]["name"])
    ->dd();

    if($Upload->done()){
        echo "upload done <br>";
        echo $Upload->merge('example.png');
    }else{
        echo "still in progress";
    }
}
