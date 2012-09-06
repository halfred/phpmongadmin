<?php

include_once "lib/classes.class.php";


$action=$_POST['action'];
$info=$_POST['info'];
$tri=$_POST['tri'];

$host=$_POST['host'];
$db=$_POST['db'];
$collection=$_POST['collection'];

$champ=$_POST['champ'];
$valeur=$_POST['valeur'];
$champ2=$_POST['champ2'];
$valeur2=$_POST['valeur2'];

$affichetype=$_POST['affichetype'];

$phpMongAdmin=new phpMongAdmin($host, $db, $collection);

switch($action){
	case 'listDBs':
		$tabresult=$phpMongAdmin->listDBs();
		break;
	case 'createDB':
		$dbname=$info;
		$tabresult=$phpMongAdmin->createDB($dbname);
		break;
	case 'dropDB':
		$dbname=$info;
		$tabresult=$phpMongAdmin->dropDB($dbname);
		break;
	case 'listCollections':
		$tabresult=$phpMongAdmin->listCollections();
		break;
	case 'createCollection':
		$collection=$info;
		$tabresult=$phpMongAdmin->createCollection($collection);
		break;
	case 'dropCollection':
		$collection=$info;
		$tabresult=$phpMongAdmin->dropCollection($collection);
		break;
	case 'clearCollection':
		$collection=$info;
		$tabresult=$phpMongAdmin->clearCollection($collection);
		break;
	case 'displayCollection':
		if($tri!=""){
			list($champ, $sens)=explode('|', $tri);
			$sens=(int)$sens;
			$tabtri=array("$champ" => $sens);
		}
		else
			$tabtri=array('_id' => 1);
		
		$page=$info;
		$options['affichetype']=$affichetype;
		echo $result=$phpMongAdmin->displayCollection($page, $tabtri, $options);
		break;
	case 'addRow':
		
		foreach($_POST as $clef => $valeur){
			if(substr($clef,0,4)=='txt_'){
				$champ=substr($clef,4);
				$tabdata[$valeur]=$_POST[$champ];
			}
		}
		
		$tabresult=$phpMongAdmin->addRow($tabdata);
		break;
	case 'getRow':
		$mongoId=$info;
		$tabresult=$phpMongAdmin->getRow($mongoId);
		break;
	case 'modifRow':
		$mongoId=$info;
		$tabresult=$phpMongAdmin->modifRow($mongoId, $_POST);
		break;
	case 'addField':
// 		echo "$info";
		$mongoId=$info;
		$tabresult=$phpMongAdmin->addField($mongoId, $_POST);
		break;
	case 'unsetField':
		list($mongoId, $field)=explode('|', $info);
		$tabresult=$phpMongAdmin->unsetField($mongoId, $field);
		break;
	case 'duplicateRow':
		$mongoId=$info;
		$tabresult=$phpMongAdmin->duplicateRow($mongoId, $_POST);
		break;
	case 'delRow':
		$mongoId=$info;
		$tabresult=$phpMongAdmin->delRow($mongoId);
		break;
	case 'ensureIndex':
		$field=$info;
		$tabresult=$phpMongAdmin->ensureIndex($field);
		break;
	case 'deleteIndex':
		$field=$info;
		$tabresult=$phpMongAdmin->deleteIndex($field);
		break;
	case 'quickSearch':
		$requete=array("$champ" => "$valeur");
		echo $phpMongAdmin->quickSearch($requete);
		break;
	case 'refresh_search':
	case 'search':
		echo $phpMongAdmin->search($_POST);
		break;
	case 'getFields':
		$tabresult=$phpMongAdmin->getFields();
		break;
	case 'create_unique_key':
		$phpMongAdmin->create_unique_key($_POST['hide_clef_unique']);
		break;
	case 'storeallfields':
		$phpMongAdmin->storeAllFields();
		break;
	case 'multiaction_del':
		$strkeys_multiaction=$info;
		$phpMongAdmin->multiaction_del($strkeys_multiaction);
		break;
	default:
		break;
}



if( isset($_FILES['uploadedfile']) ){
	$phpMongAdmin=new phpMongAdmin("test", "test");
	print_r($_FILES);
	$tabresult=$phpMongAdmin->uploadFile($_FILES);
}
if( isset($_FILES['flashUploadFiles']) ){
	$phpMongAdmin=new phpMongAdmin("test", "test");
	$phpMongAdmin->uploadFile($_FILES);
}



if(count($tabresult)>0)
	echo json_encode($tabresult);

?>