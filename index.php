<?


include_once "lib/classes.class.php";
include_once "phpmongadmin_js.php";
include_once "phpmongadmin_css.php";

$server_default=ServerList::getDefaultServer();

$collection=$_GET['c'];

if($collection!=''){
	list($server_default, $auto_db, $auto_collection)=explode('.', $collection);
}

?>

<link rel="icon" type="image/gif" href="img/favicon.png" />
<body class='tundra'>


<form id='form_post'>
<input type='hidden' name='action' id='action'>
<input type='hidden' name='info' id='info'>
<input type='hidden' name='tri' id='tri'>

<input type='hidden' name='host' id='host' value='<? echo $server_default; ?>'>
<input type='hidden' name='db' id='db' >
<input type='hidden' name='collection' id='collection'>

<input type='hidden' name='champ' id='champ'>
<input type='hidden' name='valeur' id='valeur'>

<input type='hidden' name='champ2' id='champ2'>
<input type='hidden' name='valeur2' id='valeur2'>

<input type='hidden' name='affichetype' id='affichetype'>

</form>

<input type='hidden' name='search_request' id='search_request'>

<div id='frameleft' class='frameleft'>
<? include_once "navbar.php"; ?>
</div>

<div id='frameright' class='frameright'>
<? include_once "list.php"; ?>
</div>
