
<script type="text/javascript" src="lib//dojo/dojo/dojo.js" djConfig="parseOnLoad: true, usePlainJson:true, isDebug: false, gfxRenderer: 'svg,silverlight,vml'"></script>

<link rel="stylesheet" type="text/css" href="lib/dojo/dijit/themes/tundra/tundra.css">

<script language="javascript">


dojo.require("dijit.Dialog");

dojo.require("dojox.form.FileUploader");
// dojo.require("dojox.embed.Flash");

dojo.require("dojo.parser");

///////////////////////////////////////////////////
function addslashes(str) {
	str=str.replace(/\\/g,'\\\\');
	str=str.replace(/\'/g,'\\\'');
	str=str.replace(/\"/g,'\\"');
	str=str.replace(/\0/g,'\\0');
	return str;
}
function stripslashes(str) {
	str=str.replace(/\\'/g,'\'');
	str=str.replace(/\\"/g,'"');
	str=str.replace(/\\0/g,'\0');
	str=str.replace(/\\\\/g,'\\');
	return str;
}
///////////////////////////////////////////////////



var auto_db='<? echo $auto_db;?>';
var auto_collection='<? echo $auto_collection;?>';


//liste des dbs
function listDBs(){
	
	dojo.byId('action').value='listDBs';
	dojo.byId('info').value="";
	waitbox('start');
	
	dojo.xhrPost( {
	url: "phpmongadmin_xhr.php?listDBs",
	handleAs: "json",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		
		var chaine="";
		var host=dojo.byId('select_host').value;
		
		for(var key in data){
			chaine+="<a href='index.php?c="+host+"."+data[key]+"' target='_blank'><div class='img_window1'><img src='img/new_window_LR.png' title='Ouvrir dans une nouvelle fenetre' style='border:0px;'></div></a> <a href='javascript:listCollections(\""+data[key]+"\");'><div id='divdb_"+data[key]+"' class='listdb_off'>"+data[key]+"</div></a><br><div id='cols_"+data[key]+"'></div>";
		}
		
		dojo.byId('divListDbs').innerHTML=chaine;
		waitbox('stop');
		
		if(auto_db!=''){
			listCollections(auto_db);
			auto_db='';
		}
	}});
}

//liste des collections
function listCollections(db){
	
	waitbox('start');
	razMain();
	dojo.byId('db').value=db;
	dojo.byId('collection').value="";
	
	MAJ_menu('raz');
	
	dojo.byId('action').value='listCollections';
	dojo.byId('info').value=db;
	
	dojo.byId('divdb_'+db).className='listdb_on';
	
	dojo.xhrPost( {
	url: "phpmongadmin_xhr.php?listCollections",
	handleAs: "json",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		
		var chaine="";
		var host=dojo.byId('select_host').value;
		
		for(var key in data){
			chaine+="<a href='index.php?c="+host+"."+db+"."+data[key]+"' target='_blank'><div class='img_window2'><img src='img/new_window_LR.png' title='Ouvrir dans une nouvelle fenetre' style='border:0px;'></div></a> <a href='javascript:displayCollection(\""+data[key]+"\", 0, \"\");'><div id='divdb_"+data[key]+"' class='listcol_off'>"+data[key]+"</div></a><br>";
		}
		
		dojo.byId('cols_'+db).innerHTML=chaine;
		waitbox('stop');
		
		if(auto_collection!=''){
			displayCollection(auto_collection, 0, '');
			auto_collection='';
		}
	}});
}


//demande le nom de la  nouvelle base
function ask_createDB(){
	var titre="Ajout d'une base de données";
	var msg="<table><tr><td>Nom : </td><td><input type='text' name='info'></td></tr><tr><td colspan='2' align='center'><input type='button' value='Valider' onclick='javascript:createDB();'></td></tr></table>";
	dialog(titre, msg);
}

function createDB(){
	
	dojo.byId('ask_action').value='createDB';
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?createDB",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_ask'),
	load: function(data, ioArgs) {
		dijit.byId('dlg_ask').hide();
		listDBs();
		displayCollection(dojo.byId('collection').value,0,'');
	}});
}

// Suppression de la base de données
function dropDB(){
	var db=dojo.byId('db').value;
	
	if(confirm("Supprimer la base "+db+" ?")){
		
		dojo.byId('action').value='dropDB';
		dojo.byId('info').value=db;
		
		dojo.xhrPost( {
		url: "phpmongadmin_xhr.php?dropDB",
		handleAs: "json",
		timeout: 60000,
		form:dojo.byId('form_post'),
		load: function(data, ioArgs) {
			dojo.byId('db').value='';
			listDBs();
		}});
	}
}


//affiche le contenu de la collection
function displayCollection(collection, page, tri){
	
	waitbox('start');
	MAJ_menu('maj');
	
	dojo.byId('search_request').value="";
	dojo.byId('collection').value=collection;
	dojo.byId('divdb_'+collection).className="listcol_on";
	
	dojo.byId('action').value='displayCollection';
	dojo.byId('info').value=page;
	if(tri!="")
		dojo.byId('tri').value=tri;
	
	dojo.xhrPost( {
	url: "phpmongadmin_xhr.php?displayCollection",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		dojo.byId('reponsexhr').innerHTML=data;
		
		affichDiv('reponsexhr');
		waitbox('stop');
	}});
}




//execution d'une requete rapide
function postquickSearch(){
	
	dojo.byId('champ').value=dojo.byId('txtChamp').value;
	dojo.byId('valeur').value=dojo.byId('txtValeur').value;
	
	dojo.byId('action').value='quickSearch';
	dojo.byId('info').value="";
	
	dojo.xhrPost( {
	url: "phpmongadmin_xhr.php?quickSearch",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		dojo.byId('reponsexhr').innerHTML=data;
	}});
}

//moniteur d'appui de la touche EntrÃ©e
function EnterWatch(evt, function_type, function_info){
	
	var touche = window.event ? evt.keyCode : evt.which;
	
	if(touche==13){
		
		switch (function_type){
			case 'postquickSearch':
				postquickSearch();
				break;
			case 'search':
				search_validate(0,'');
				break;
			case 'modifRow':
				modifRow(function_info);
				break;
		}
	}
}

//suppression d'un enregistrement
function delRow(id){
	
	if(confirm("Supprimer cet enregistrement ?")){
		
		dojo.byId('action').value='delRow';
		dojo.byId('info').value=id;
		
		dojo.xhrPost( {
		url: "phpmongadmin_xhr.php?delRow",
		handleAs: "text",
		timeout: 60000,
		form:dojo.byId('form_post'),
		load: function(data, ioArgs) {
			if(dojo.byId('search_request').value=="")
				displayCollection(dojo.byId('collection').value,0,'');
			else
				search_validate(0,'');
		}});
	}
}

//recupération d'un enregistrement
function getRow(id){
	
	dojo.byId('action').value='getRow';
	dojo.byId('info').value=id;
	
	dojo.xhrPost( {
	url: "phpmongadmin_xhr.php?getRow",
	handleAs: "json",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		
		var chaine="";
		chaine+="<center><h3>MODIFICATION D'UN ENREGISTREMENT</h3><br>";
		chaine+="<form id='form_postEdit'><input type='hidden' name='action' id='actionEdit'><input type='hidden' name='info' id='infoEdit'><input type='hidden' name='host' id='hostEdit'><input type='hidden' name='db' id='dbEdit'><input type='hidden' name='collection' id='collectionEdit'>";
		chaine+="<table id='table_getRow'>";
		
		for(var key in data){
			if(key!="_id"){
				chaine+="<tr valign='top'><td><a href='javascript:unsetField(\""+id+"\", \""+key+"\");'><img src='img/unset_LR.png' title='Supprimer ce champ de cet enregistrement'></a></td><td>"+key+"</td>";
				if(data[key]['type']=='txt'){
					chaine+="<td><select name='type_"+key+"'><option value='txt' selected>Texte</option><option value='array'>Tableau</option><option value='mongoDate'>MongoDate</option><option value='int'>Entier</option></select></td>";
					if(data[key]['value'].length<50){
						chaine+="<td> <input type='text' id='"+key+"' name='"+key+"' value=\""+data[key]['value']+"\" size='44' onkeyup='javascript:EnterWatch(event, \"modifRow\", \""+id+"\");'>";
					}
					else{
						chaine+="<td> <textarea rows='5' cols='50' id='"+key+"' name='"+key+"' onkeyup='javascript:EnterWatch(event, \"modifRow\", \""+id+"\");'>"+data[key]['value']+"</textarea>";
					}
				}
				else if(data[key]['type']=='array'){
					chaine+="<td><select name='type_"+key+"'><option value='txt'>Texte</option><option value='array' selected>Tableau</option><option value='mongoDate'>MongoDate</option><option value='int'>Entier</option></select></td>";
					chaine+="<td> <textarea rows='5' cols='50' id='"+key+"' name='"+key+"' onkeyup='javascript:EnterWatch(event, \"modifRow\", \""+id+"\");'>"+data[key]['value']+"</textarea>";
				}
				else if(data[key]['type']=='mongoDate'){
					chaine+="<td><select name='type_"+key+"'><option value='txt'>Texte</option><option value='array'>Tableau</option><option value='mongoDate' selected>MongoDate</option><option value='int'>Entier</option></select></td>";
					chaine+="<td> <input type='text' id='"+key+"' name='"+key+"' value=\""+data[key]['value']+"\" size='44' onkeyup='javascript:EnterWatch(event, \"modifRow\", \""+id+"\");'>";
				}
				else if(data[key]['type']=='int'){
					chaine+="<td><select name='type_"+key+"'><option value='txt'>Texte</option><option value='array'>Tableau</option><option value='mongoDate'>MongoDate</option><option value='int' selected>Entier</option></select></td>";
					chaine+="<td> <input type='text' id='"+key+"' name='"+key+"' value=\""+data[key]['value']+"\" size='44' onkeyup='javascript:EnterWatch(event, \"modifRow\", \""+id+"\");'>";
				}
				chaine+="</td><td><a href='javascript:modifRow(\""+id+"\");'><img src='img/enter_LR.png' title='Valider la modification'></a>";
				chaine+="</td></tr>";
			}
		}
		
		chaine+="<tr><td colspan='5' align='center'><input type='button' value='Ajouter un champ' onclick='javascript:addOne(\""+id+"\");'></td></tr>";
		chaine+="</table></form>";
		
		chaine+="<br><br><input type='button' value='Annuler' onclick='javascript:affichDiv(\"reponsexhr\");'>";
		chaine+=" <input type='button' value='Valider' onclick='javascript:modifRow(\""+id+"\");'>";
		chaine+=" <input type='button' value='Sauvegarder un nouvel enregistrement' onclick='javascript:postDuplicate(\""+id+"\");'>";
		chaine+="</center>";
		
		dojo.byId('divedit').innerHTML=chaine;
		
		affichDiv('divedit');
		
	}});
}

//modification d'un enregistrement
function modifRow(id){

	dojo.byId('hostEdit').value=dojo.byId('host').value;
	dojo.byId('dbEdit').value=dojo.byId('db').value;
	dojo.byId('collectionEdit').value=dojo.byId('collection').value;
	
	dojo.byId('actionEdit').value='modifRow';
	dojo.byId('infoEdit').value=id;
	
	dojo.xhrPost( {
	url: "phpmongadmin_xhr.php?modifRow",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_postEdit'),
	load: function(data, ioArgs) {
		if(dojo.byId('search_request').value=="")
			displayCollection(dojo.byId('collection').value,0,'');
		else
			search_validate(0,'');
	}});
}

//ajout d'une copie
function postDuplicate(id){
	
	dojo.byId('hostEdit').value=dojo.byId('host').value;
	dojo.byId('dbEdit').value=dojo.byId('db').value;
	dojo.byId('collectionEdit').value=dojo.byId('collection').value;
	
	dojo.byId('actionEdit').value='duplicateRow';
	dojo.byId('infoEdit').value=id;
	
	dojo.xhrPost( {
	url: "phpmongadmin_xhr.php?duplicateRow",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_postEdit'),
	load: function(data, ioArgs) {
		if(dojo.byId('search_request').value=="")
			displayCollection(dojo.byId('collection').value,0,'');
		else
			search_validate(0,'');
	}});
}

//confirmation de suppression d'une collection
function dropColl(host, db, collection){
	if(confirm("Supprimer la collection '"+host+"."+db+"."+collection+"' ??")){
		dropCollectionValidate(collection);
	}
}

//suppression d'une collection
function dropCollectionValidate(collection){
	
	dojo.byId('action').value='dropCollection';
	dojo.byId('info').value=collection;
	
	dojo.xhrPost( {
	url: "phpmongadmin_xhr.php?dropCollection",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		listCollections(dojo.byId('db').value);
		dojo.byId('reponsexhr').innerHTML="";
	}});
}

//creation d'une nouvelle collection
function createCollection(){
	dojo.byId('action').value='createCollection';
	dojo.byId('info').value=dojo.byId('newColl').value;
	
	dojo.xhrPost( {
	url: "phpmongadmin_xhr.php?createCollection",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		dijit.byId('dlg_ask').hide();
		listCollections(dojo.byId('db').value);
		displayCollection(dojo.byId('newColl').value,0,'');
		
	}});
}


//vidage d'une collection
function clearColl(host, db, collection){
	if(confirm("Vider la collection '"+host+"."+db+"."+collection+"' ??")){
		dojo.byId('action').value='clearCollection';
		dojo.byId('info').value=collection;
		
		dojo.xhrPost({
		url: "phpmongadmin_xhr.php?clearCollection",
		handleAs: "text",
		timeout: 60000,
		form:dojo.byId('form_post'),
		load: function(data, ioArgs) {
			displayCollection(dojo.byId('collection').value,0,'');
		}});
	}
}

//insert d'un enregistrement dans la collection
function insertField(host, db, collection){
	var chaine="";
	
	chaine+="<br><center> <input type='button' value='Ajouter un champ' onclick='newField();'><br>";
	
	chaine+="<form id='form_addField'>";
	chaine+="<input type='hidden' name='action' id='action_addField' value='addRow'>";
	chaine+="<input type='hidden' name='host' id='addField_host' value='"+host+"'>";
	chaine+="<input type='hidden' name='db' id='addField_db' value='"+db+"'>";
	chaine+="<input type='hidden' name='collection' id='addField_collection' value='"+collection+"'>";
	chaine+="<table align='center' id='tab_addField'><tr><th>Champ</th><th>Valeur</th></tr>";
	
	chaine+="<tr id='tr_Field1'><td><input type='text' name='txt_Field1' id='txt_Field1'></td><td><input type='text' name='Field1' id='Field1'></td></tr>";
	chaine+="</table></form>";
	
	
	chaine+="<br><input type='button' value='Annuler' onclick='javascript:affichDiv(\"reponsexhr\");'> <input type='button' value='Valider' onclick='insertField_validate();'></center>";
	
	dojo.byId('divaddField').innerHTML=chaine;
	affichDiv('divaddField');
}

//insert d'un enregistrement dans la collection
function insertField_validate(){
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?addRow",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_addField'),
	load: function(data, ioArgs) {
		displayCollection(dojo.byId('collection').value,0,'');
	}});
}

//ajout d'un champ dans l'ajout d'un enregistrement
function newField(){
	var nblignes=dojo.byId('tab_addField').rows.length;
	nblignes++;
	
	var newRow = dojo.byId('tab_addField').insertRow(-1);
	newRow.id="tr_Field"+nblignes;
	
	var newCell = newRow.insertCell(0);
	newCell.innerHTML = "<input type='text' name='txt_Field"+nblignes+"' id='txt_Field"+nblignes+"'>";
	newCell = newRow.insertCell(1);
	newCell.innerHTML = "<input type='text' name='Field"+nblignes+"' id='Field"+nblignes+"'>";
}

//unset un champ de l'id
function unsetField(id, field){
	if(confirm("Supprimer le champ "+field+" de l'id "+id+"' ??")){
		dojo.byId('action').value='unsetField';
		dojo.byId('info').value=id+"|"+field;
		
		dojo.xhrPost({
		url: "phpmongadmin_xhr.php?unsetField",
		handleAs: "text",
		timeout: 60000,
		form:dojo.byId('form_post'),
		load: function(data, ioArgs) {
			getRow(id);
		}});
	}
}

//ajout d'un champ pour un enregistrement
function addOne(id){
	
	var msg="<input type='hidden' name='action' value='addField'><input type='hidden' name='host' value='"+dojo.byId('host').value+"'><input type='hidden' name='db' value='"+dojo.byId('db').value+"'><input type='hidden' name='collection' value='"+dojo.byId('collection').value+"'><input type='hidden' name='info' value='"+id+"'>";
	msg+="<table><tr><th>Champ</th><th>Type</th><th>Valeur</th></tr>";
	msg+="<tr><td><input type='text' name='champ' id='addOneField'></td>";
	msg+="<td><select name='type_"+id+"'><option value='txt'>Texte</option><option value='array'>Tableau</option><option value='mongoDate' selected>MongoDate</option><option value='int'>Entier</option></select></td>";
	msg+="<td><input type='text' name='valeur' id='addOneValue'></td></tr>";
	msg+="<tr><td colspan='2' align='center'> <br><br><input type='button' value='Annuler' javascript:dijit.byId(\"dlg_ask\").hide();> <input type='button' value='Valider' onclick='javascript:addOne_validate();'></td></tr></table>";
	
	var titre='Ajouter un champ';
	dialog(titre, msg);
}

//ajout d'un champ pour un enregistrement
function addOne_validate(){
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?addField",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_ask'),
	load: function(data, ioArgs) {
		dijit.byId('dlg_ask').hide();
		if(dojo.byId('search_request').value=="")
			displayCollection(dojo.byId('collection').value,0,'');
		else
			search_validate(0,'');
	}});
}

//affichage exclusif d'un div
function affichDiv(divid){
	dojo.byId('divaddField').style.display="none";
	dojo.byId('divedit').style.display="none";
	dojo.byId('reponsexhr').style.display="none";
	dojo.byId('divSearch').style.display="none";
	
	dojo.byId(divid).style.display="block";
}

//ajout d'un index
function ensureIndex(champ){
	
	dojo.byId('action').value='ensureIndex';
	dojo.byId('info').value=champ;
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?ensureIndex",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		if(dojo.byId('search_request').value=="")
			displayCollection(dojo.byId('collection').value,0,'');
		else
			search_validate(0,'');
	}});
}

//suppression d'un index
function deleteIndex(champ){
	
	dojo.byId('action').value='deleteIndex';
	dojo.byId('info').value=champ;
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?deleteIndex",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		if(dojo.byId('search_request').value=="")
			displayCollection(dojo.byId('collection').value,0,'');
		else
			search_validate(0,'');
	}});
}

function MAJ_menu(action){
	var tabdivs=document.getElementsByTagName('div');
	
	for(var i in tabdivs){
		if(tabdivs[i]!=null){
			if(tabdivs[i].className=='listdb_on'){
				tabdivs[i].className='listdb_off';
			}
			else if(tabdivs[i].className=='listcol_on'){
				if(tabdivs[i].id!='cols_'+dojo.byId('collection').value){
					tabdivs[i].className='listcol_off';
				}
			}
			if((tabdivs[i].id!=null)&&(action=='raz')){
				if(tabdivs[i].id.substr(0,5)=='cols_'){
					dojo.byId(tabdivs[i].id).innerHTML='';
				}
			}
		}
	}
}


//recherche
function search(host, db, collection){
	
	waitbox('start');
	
	dojo.byId('action').value='getFields';
	dojo.byId('info').value="";
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?getFields",
	handleAs: "json",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		
		var chaine="";
		chaine+="<center><h3>Rechercher dans "+db+"."+collection+"</h3><br>";
		
		chaine+="<form id='form_search'>";
		chaine+="<input type='hidden' name='affichetype' id='affichetype_serach' value='"+dojo.byId('affichetype').value+"'>";
		chaine+="<input type='hidden' name='action' id='action_search' value='search'>";
		chaine+="<input type='hidden' name='host' id='search_host' value='"+host+"'>";
		chaine+="<input type='hidden' name='db' id='search_db' value='"+db+"'>";
		chaine+="<input type='hidden' name='collection' id='search_collection' value='"+collection+"'>";
		chaine+="<input type='hidden' name='tri' id='search_tri' value=''>";
		chaine+="<table align='center' id='tab_search' class='tabsearch'><tr><th>Champ</th><th>Operateur</th><th>Valeur</th></tr>";
		
		for(var key in data){
			chaine+="<tr><td align='right'>"+key+"</td>";
			chaine+="<td><select name='op_"+key+"' id='op_"+key+"'><option value='dblequal' selected>==</option><option value='equal'>=</option><option value='notequal'>!=</option><option value='sup'>></option><option value='supequal'>>=</option><option value='inf'><</option><option value='infequal'><=</option><option value='like'>LIKE</option></select></td>";
			chaine+="<td><input type='text' name='txt_"+key+"' id='txt_"+key+"' onkeyup='javascript:EnterWatch(event, \"search\", \"\");'></td>";
			chaine+="<td><a href='javascript:search_validate(0,\"\");'><img src='img/enter_LR.png' title='Valider la recherche'></a></td></tr>";
		}
		
		chaine+="</table>";
		chaine+="</form><br><input type='button' value='Annuler' onclick='javascript:affichDiv(\"reponsexhr\");'> <input type='button' value='Valider' onclick='search_validate(0,\"\");'></center>";
		
		dojo.byId('divSearch').innerHTML=chaine;
		affichDiv('divSearch');
		waitbox('stop');
	}});
}


//edition de la recherche
function edit_search(host, db, collection){
	
	waitbox('start');
	
	dojo.byId('action').value='getFields';
	dojo.byId('info').value="";
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?getFields",
	handleAs: "json",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		
		var requete=dojo.byId('filter').value;
		var operateur="";
		requete=requete.split('|');
		
		var tabrequete=new Array();
		for(var i=0;i<requete.length-1;i++){
			tabrequete[requete[i]]=requete[i];
		}
		
		var chaine="";
		chaine+="<center><h3>Rechercher dans "+db+"."+collection+"</h3><br>";
		
		chaine+="<form id='form_search'>";
		chaine+="<input type='hidden' name='action' id='action_search' value='search'>";
		chaine+="<input type='hidden' name='host' id='search_host' value='"+host+"'>";
		chaine+="<input type='hidden' name='db' id='search_db' value='"+db+"'>";
		chaine+="<input type='hidden' name='collection' id='search_collection' value='"+collection+"'>";
		chaine+="<input type='hidden' name='tri' id='search_tri' value=''>";
		chaine+="<table align='center' id='tab_search' class='tabsearch'><tr><th>Champ</th><th>Operateur</th><th>Valeur</th></tr>";
		
		for(var key in data){
			
			var sel_equal="";
			var sel_notequal="";
			var sel_sup="";
			var sel_supequal="";
			var sel_inf="";
			var sel_infequal="";
			var sel_like="";
			var sel_dbllike="selected";
			
			if(key==tabrequete[key]){
				requete=tabrequete[key];
				requete=dojo.byId('txt_'+requete).value;
// 				alert(requete);
// 				alert(dojo.byId('op_'+requete).selectedIndex);
// 				operateur=dojo.byId('op_'+requete).select;
				
				
				
				switch(operateur){
					case 'equal':
						sel_equal='selected';
						break;
					case 'notequal':
						sel_notequal='selected';
						break;
					case 'suo':
						sel_sup='selected';
						break;
					case 'supequal':
						sel_supequal='selected';
						break;
					case 'inf':
						sel_inf='selected';
						break;
					case 'infequal':
						sel_infequal='selected';
						break;
					case 'like':
						sel_like='selected';
						break;
					case 'dbllike':
					default:
						sel_dbllike='selected';
						break;
				}
			}
			else{
				requete="";
			}
			
			chaine+="<tr><td align='right'>"+key+"</td>";
			chaine+="<td><select name='op_"+key+"' id='op_"+key+"'><option value='dblequal' "+sel_dbllike+">==</option><option value='equal' "+sel_equal+">=</option><option value='notequal' "+sel_notequal+">!=</option><option value='sup' "+sel_sup+">></option><option value='supequal' "+sel_supequal+">>=</option><option value='inf' "+sel_inf+"><</option><option value='infequal' "+sel_infequal+"><=</option><option value='like' "+sel_like+">LIKE</option></select></td>";
			chaine+="<td><input type='text' name='txt_"+key+"' id='txt_"+key+"' onkeyup='javascript:EnterWatch(event, \"search\", \"\");' value='"+requete+"'></td>";
			chaine+="<td><a href='javascript:search_validate(0,\"\");'><img src='img/enter_LR.png' title='Valider la recherche'></a></td></tr>";
		}
		
		chaine+="</table>";
		chaine+="</form><br><input type='button' value='Annuler' onclick='javascript:affichDiv(\"reponsexhr\");'> <input type='button' value='Valider' onclick='search_validate(0,\"\");'></center>";
		
		dojo.byId('divSearch').innerHTML=chaine;
		affichDiv('divSearch');
		waitbox('stop');
	}});
}


//recherche
function search_validate(page, tri){
	
	waitbox('start');
	
	dojo.byId('action').value='search';
	dojo.byId('info').value="";
	
	dojo.byId('search_tri').value=tri;
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?search",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_search'),
	load: function(data, ioArgs) {
		dojo.byId('reponsexhr').innerHTML=data;
		dojo.byId('search_request').value=dojo.byId('search_request_tmp').value;
		affichDiv('reponsexhr');
		waitbox('stop');
	}});
}

//actualiser le resultat de la recherche
function refresh_search(){
	waitbox('start');
	
	dojo.byId('action').value='refresh_search';
	dojo.byId('info').value="";
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?refresh_search",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_refresh_search'),
	load: function(data, ioArgs) {
		dojo.byId('reponsexhr').innerHTML=data;
		affichDiv('reponsexhr');
		waitbox('stop');
	}});
}

//demande de création d'une clef unique
function create_unique_key(host, db, collection){
	waitbox('start');
	
	dojo.byId('action').value='getFields';
	dojo.byId('info').value="";
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?getFields",
	handleAs: "json",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		
		var chaine="";
		chaine+="<center><h3>Création de clef unique dans "+db+"."+collection+"</h3><br>";
		
		chaine+="<form id='form_key'>";
		chaine+="<table align='center' id='tab_search' class='tabsearch'><tr><th>Champ</th><th>Selection</th></tr>";
		
		for(var key in data){
			chaine+="<tr><td align='right'>"+key+"</td>";
			chaine+="<td><input type='checkbox' id='chk_"+key+"' onclick='javascript:maj_unique_key_input(\""+key+"\")'></td></tr>";
		}
		
		chaine+="</table>";
		chaine+="<br><input type='button' value='Annuler' onclick='javascript:affichDiv(\"reponsexhr\");'> <input type='button' value='Valider' onclick='javascript:create_unique_key_validate();'><input type='hidden' name='action' value='create_unique_key'><input type='hidden' name='host' value='"+host+"'><input type='hidden' name='db' value='"+db+"'><input type='hidden' name='collection' value='"+collection+"'><input type='hidden' name='hide_clef_unique' id='hide_clef_unique' value=''></center></form>";
		
		dojo.byId('divSearch').innerHTML=chaine;
		affichDiv('divSearch');
		waitbox('stop');
	}});
}


//mise a jour du input caché qui contient les champs qui forment une clef unique
function maj_unique_key_input(champ){
	if(dojo.byId("chk_"+champ).checked==true){
		dojo.byId('hide_clef_unique').value+=champ+"|";
	}
	else{
		var hide=dojo.byId('hide_clef_unique').value;
		var tabhide=hide.split('|');
		var chaine="";
		
		for(var i=0;i<tabhide.length-1;i++){
			if(tabhide[i]!=champ){
				chaine+=tabhide[i]+'|';
			}
			dojo.byId('hide_clef_unique').value=chaine;
		}
	}
}

//creation de la clef unqique
function create_unique_key_validate(){
	var hide=dojo.byId('hide_clef_unique').value;
	
	waitbox('start');
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?create_unique_key",
	handleAs: "json",
	timeout: 60000,
	form:dojo.byId('form_key'),
	load: function(data, ioArgs) {
		affichDiv('reponsexhr');
		waitbox('stop');
	}});
	
}

//stocker les champs
function storeallfields(){
	
	dojo.byId('action').value="storeallfields";
	
	dojo.xhrPost({
	url: "phpmongadmin_xhr.php?storeallfields",
	handleAs: "text",
	timeout: 60000,
	form:dojo.byId('form_post'),
	load: function(data, ioArgs) {
		if(data!=''){
			alert(data);
		}
	}});
}

function askCreateCollection(){
	var db=dojo.byId('db').value;
	
	if(db!=''){
		var content="<center><br>Nom : <input type='text' id='newColl'><br><br><input type='button' onclick='javascript:createCollection();' value='Valider'></center>";
		dialog("Créer une collection dans <b>"+db+"</b>", content);
	}
	else{
		alert('Veuillez sélectionner une base de données\npour y créer une collection');
	}
}

//demande d'upload d'un fichier
function upload_file(host, db){
	dijit.byId("selectFileUp").uploadUrl="phpmongadmin_xhr?host="+host+"&db="+db;
	dojo.byId('div_responseFileUp').style.display="none";
	dojo.byId('div_selectFileUp').style.display="inline";
	dijit.byId('dlg_upfile').show();
}

//confirmation d'upload d'un fichier
function upload_file_validate(){
	dijit.byId("selectFileUp").upload();
}

//confirmation d'upload d'un fichier
function upload_complete(){
	dojo.byId('div_responseFileUp').style.display="inline";
	dojo.byId('div_selectFileUp').style.display="none";
}

//affichage de la boite de dialogue avec titre et contenu
function dialog(titre, contenu){
	dojo.byId('titredialog').innerHTML=titre;
	dojo.byId('contenudialog').innerHTML=contenu;
	dijit.byId('dlg_ask').show();
}

//affichage de la boite de dialog de chargement
function waitbox(action){
	if(action=='start')
		dijit.byId('dlg_wait').show();
	else if(action=='stop'){
		dijit.byId('dlg_wait').hide();
	}
}

//changement d'etat d'un champ en fonction d'un autre
function changestate(champ_achanger, champ_source){
	
	if(dojo.byId(champ_source).checked)
		dojo.byId(champ_achanger).value=1;
	else
		dojo.byId(champ_achanger).value=0;
	
}

//selection multiple
function multiaction_select(id){
	dojo.byId('chkmulti_'+id).checked=!dojo.byId('chkmulti_'+id).checked;
	multiaction_chk(id);
}

//mise a jour du champs caché pour la gestion des actions multiples
function multiaction_chk(id){
	
	if(dojo.byId('chkmulti_'+id).checked){
		dojo.byId('strkeys_multiaction').value+="|"+id;
		dojo.byId('tr_'+id).style.backgroundColor="ffdd68";
	}
	else{
		dojo.byId('tr_'+id).style.backgroundColor="";
		var strkeys_multiaction=dojo.byId('strkeys_multiaction').value;
		var tabkeys=strkeys_multiaction.split('|');
		var strkeys_multiaction_new="";
		
		for(var key in tabkeys){
			if((tabkeys[key]!='')&&(tabkeys[key]!=id)){
				strkeys_multiaction_new+="|"+tabkeys[key];
			}
		}
		
		dojo.byId('strkeys_multiaction').value=strkeys_multiaction_new;
	}
}

function multiaction_chkall(){
	var strallkeys=dojo.byId('strallkeys').value;
	var tabkeys=strallkeys.split('|');
	var status=dojo.byId('chkmulti_all').checked;
	
	for(var key in tabkeys){
		if(tabkeys[key]!=''){
// 			alert('chkmulti_'+tabkeys[key]);
			dojo.byId('chkmulti_'+tabkeys[key]).checked=status;
			multiaction_chk(tabkeys[key]);
		}
	}
}

function multiaction_del(){
	
	var strallkeys=dojo.byId('strkeys_multiaction').value;
	
	if(strallkeys!=''){	
		var tabkeys=strallkeys.split('|');
// 		console.log(tabkeys);
		var nbtodel=tabkeys.length-1;
		
		if(confirm('Supprimer les '+nbtodel+' enregistrements sélectionnés ??')){
			dojo.byId('action').value="multiaction_del";
			dojo.byId('info').value=dojo.byId('strkeys_multiaction').value;
			
			dojo.xhrPost({
			url: "phpmongadmin_xhr.php?multiaction_del",
			handleAs: "text",
			timeout: 60000,
			form:dojo.byId('form_post'),
			load: function(data, ioArgs) {
				if(dojo.byId('search_request').value=="")
					displayCollection(dojo.byId('collection').value,0,'');
				else
					search_validate(0,'');
			}});
		}
	}
}

//
function razMain(){
	dojo.byId('reponsexhr').innerHTML="";
	dojo.byId('reponsexhr').style.display='inline';
	dojo.byId('divedit').style.display='none';
	dojo.byId('divaddField').style.display='none';
	dojo.byId('divSearch').style.display='none';
}

//reinitialisation
function reinit(){
	razMain();
	dojo.byId('host').value=dojo.byId('select_host').value;
	init();
}

function init(){
	dojo.byId('dblist_link').innerHTML="<a href='"+dojo.byId('host').value+"_liste_collections.csv'><img src='img/csv_LR.png' width='20px'/></a>";
	dojo.byId('dlg_wait').innerHTML="<img src='img/loader.gif' onclick='javascript:waitbox(\"stop\");'>";
	listDBs();
	dojox.embed.Flash.available = 0;
}


dojo.addOnLoad(init);

</script>