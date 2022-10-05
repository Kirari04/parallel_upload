<?php
require_once './ParallelUpload.php';
// session_start();
// session_destroy();
$Upload = new ParallelUpload([]);

if(isset($_GET['init'])){
    $Upload->parts(1)->dd();
}

if(isset($_GET['upload'])){
    var_dump($_FILES);
    // $Upload->upload(1, file_get_contents($_FILES["file"]["tmp_name"]))->dd();
}