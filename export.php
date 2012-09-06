<?php
include_once "lib/classes.class.php";
$host=$_GET["host"];
$db=$_GET["db"];
$collection=$_GET["collection"];
$type=$_GET["type"];
echo $_GET['requete'];
$requete=json_decode($_GET['requete']);

if($requete=='')
	$requete=array();

print_r($requete);

$date=date('Y-m-d');

$mongo = new PhpMongAdminConnect($host);
$mongo_connect=$mongo->getConnection();
$mongo_db = $mongo_connect->selectDB("$db");
$mongo_collection = $mongo_db->selectCollection("$collection");
$resultat=$mongo_collection->find($requete);

if($type=='php'){
	header('Content-type: text/plain');
	header("Content-disposition: attachment;filename=mongexport_$db-$collection"."_$date.txt");
	
	echo "\$tabexport_$db"."_$collection=array();\n";
	$i=0;
	foreach($resultat as $clef => $tabresultat){
		foreach($tabresultat as $clef2 => $valeur2){
			if($clef2!='_id')
				echo "\$tabexport_$db"."_$collection".'['."'$i'".']['."'$clef2'".']'."='$valeur2';\n";
		}
		$i++;
	}
}
else if($type=='csv'){
	header('Content-type: text/plain');
	header("Content-disposition: attachment;filename=mongexport_$db-$collection"."_$date.csv");
	
	$tabheader=array();
	$tabcontent=array();
	
	$i=0;
	foreach($resultat as $clef => $tabresultat){
		foreach($tabresultat as $clef2 => $valeur2){
			$tabcontent[$i][$clef2]=$valeur2;
			$tabheader[$clef2]=$clef2;
		}
		$i++;
	}
	
	
	foreach($tabheader as $clef => $titre){
		echo "$titre;";
	}
	echo "\n";
	
	foreach($tabcontent as $i => $tabvaleur){
		foreach($tabheader as $clef => $titre){
			echo $tabcontent[$i][$clef].";";
		}
		echo "\n";
	}
}
else{
	echo "ERREUR: FORMAT D'EXPORT INCONNU";
}
?>