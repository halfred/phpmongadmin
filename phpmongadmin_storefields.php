<?
include_once "lib/classes.class.php";
$server_list=ServerList::getServerList();

foreach($server_list as $name => $server){
	$phpMongAdmin=new phpMongAdmin($name);
	$phpMongAdmin->storeAllFields();
}


?>
