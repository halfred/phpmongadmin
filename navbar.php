<br>
<div id='divActionDb'><a href='javascript:ask_createDB();'><img src='img/database_add_LR.png' class='imgbutton' title='Créer une Base de Donnees'></a>
<a href='javascript:dropDB();'><img src='img/database_delete_LR.png' class='imgbutton' title='Supprimer une Base de Donnees'></a>
&nbsp;&nbsp;&nbsp;
<a href='javascript:askCreateCollection();'><img src='img/new_collection_LR.png' class='imgbutton' title='Créer une nouvelle collection'></a>
&nbsp;&nbsp;&nbsp;
<a href='javascript:storeallfields();'><img src='img/refresh_LR.png' class='imgbutton' title='Actualiser le stockage des champs'></a>
&nbsp;&nbsp;&nbsp;
<span id='dblist_link'></span>
</div>


Serveur : <select id='select_host' onchange='javascript:reinit();'>

<?

$server_list=ServerList::getServerList();
foreach($server_list as $name => $server){
	if($server_default==$name)
		$default="selected";
	else
		$default="";
	
	echo "<option value='$name' $default>$name</option>";
}

?>

</select>

<div id='divListDbs'></div>
<div id='divListColl'></div>


<br><div id='divOptions'>
<u style='padding-left:15px;'><b>Options : </b></u><br>
<input type='checkbox' name='chk_affichetype' id='chk_affichetype' onclick='javascript:changestate("affichetype", "chk_affichetype");'> Afficher les types des champs<br>
</div><br>


<br><br>

<div dojoType="dijit.Dialog" id='dlg_wait'>
	WAIT
</div>

<div id='divquicksearch' class='divquicksearch'>
<table class='tabQuickSearch'><tr><td colspan='2'><b>Recherche Rapide</b></td></tr>
	<tr><td>champ</td><td><input type='text' id='txtChamp' onkeyPress='javascript:EnterWatch(event, "postquickSearch");'></td></tr>
	<tr><td>valeur</td><td><input type='text' id='txtValeur' onkeyPress='javascript:EnterWatch(event, "postquickSearch");'></td></tr>
	<tr><td colspan='2'><input type='button' value='Rechercher' onclick='javascript:postquickSearch();'></td></tr>
</table>
</div>


<div dojoType="dijit.Dialog" id='dlg_ask'>
	<div id='titredialog'></div>
	<form id='form_ask'>
	<input type='hidden' name='action' id='ask_action'>
	<div id='contenudialog'></div>
	</form>
</div>

<div dojoType="dijit.Dialog" id='dlg_upfile'>
<div id='div_selectFileUp'>
Uploader un fichier<br>
<br>
<span id='selectFileUp' dojoType='dojox.form.FileUploader' onChange='javascript:console.log("change");' onComplete='javascript:console.log("complete");' >Parcourir...</span>
<input type='button' value='Uploader' onclick='javascript:upload_file_validate();'>
</div>
<!-- uploadUrl="phpmongadmin_xhr" -->
<div id='div_responseFileUp'>
<span id='reponseFileUp'></span>
<input type='button' value='Fermer' onclick='javascript:dijit.byId("dlg_upfile").hide();'>
</div>


</div>
