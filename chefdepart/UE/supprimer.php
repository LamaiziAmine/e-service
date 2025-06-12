<?php
include_once __DIR__ . '/../config.php';

if(isset($_GET["id"])){
    $id=$_GET["id"];
    $sql="DELETE FROM units WHERE id=$id";
    $connection->query($sql);


}
header("location: /PROJECT/UE/index.php");
        exit;
?>