<?php
include "lib/classes.class.php";
$id = $_GET["id"];
$host = $_GET["host"];
$db = $_GET["db"];
$filename=$_GET["filename"];

$mongoId=new MongoId($id);
$query = array("_id" => $mongoId);
$mongo = new PhpMongAdminConnect($host);
$mongo_connect=$mongo->getConnection();

$file = $mongo_connect->selectDB($db)->getGridFS()->findOne($query);

header('Content-type: application/pdf');

header("Content-disposition: attachment;filename=$filename");
echo $file->getBytes();
?>