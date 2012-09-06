<?php

/*
Class phpMongAdmin
*/



class phpMongAdmin {
	
	private $host;
	private $mongo;
	private $mongo_connection;
	private $mongo_db;
	private $mongo_collection;
	private $mongo_collection_cut;
	
	private $tabExceptDB=array('local', 'admin');
	private $tabExceptCollection=array('fs.chunks');
	
	public function __construct($host="", $db="", $collection=""){
		
		if($host=='') Throw new Exception('phpMongAdmin::__construct $host=null');
		
		$this->host=$host;
		$mongo=new PhpMongAdminConnect($host);
		$this->mongo_connection=$mongo->getConnection();
		$this->mongo=$mongo;
		
		if($db!=''){
			$this->selectDB($db);
			
			if($collection!=''){
				$this->selectCollection($collection);
				$this->mongo_collection_cut=$collection;
			}
		}
	}
	
	//liste de tous les champs de toutes les collections de toutes les bases de données puis sauvegarde pour recherche
	public function storeAllFields(){
		$tabDB=$this->listDBs();
		
		$listecsv="base;collection\n";
		foreach($tabDB as $clef => $dbname){
			if(!in_array($dbname, $this->tabExceptDB)){
				$this->selectDB($dbname);
				$tabCollections=$this->listCollections();
				
				if(count($tabCollections)>0){
					foreach($tabCollections as $clef2 => $collname){
						$listecsv.="$dbname;$collname\n";
// 						echo $this->host." $dbname $collname \n";
						if(!in_array($collname, $this->tabExceptCollection)){
							$this->selectCollection($collname);
							$tabresultats=$this->mongo_collection->find();
							$tabresultats->timeout(500000);
							
							foreach($tabresultats as $clef => $tabvaleurs){
								foreach($tabvaleurs as $champ => $var){
									$taballchamps[$dbname][$collname][$champ]['nb_occur']++;
									
									if($taballchamps[$dbname][$collname][$champ]['lenMAX']<strlen($var))
										$taballchamps[$dbname][$collname][$champ]['lenMAX']=strlen($var);
									else if($taballchamps[$dbname][$collname][$champ]['lenMAX']=='')
										$taballchamps[$dbname][$collname][$champ]['lenMAX']=strlen($var);
									
									if($taballchamps[$dbname][$collname][$champ]['lenMIN']>strlen($var))
										$taballchamps[$dbname][$collname][$champ]['lenMIN']=strlen($var);
									else if($taballchamps[$dbname][$collname][$champ]['lenMIN']=='')
										$taballchamps[$dbname][$collname][$champ]['lenMIN']=strlen($var);
								}
							}
						}
					}
				}
			}
		}
		
		$this->selectDB('phpMongAdmin');
		$this->selectCollection('fields');
		$this->mongo_collection->remove();
		
		$mongodate=new MongoDate();
		
		//sauvegarde
		foreach($taballchamps as $dbname => $tabcoll){
			foreach($tabcoll as $collname => $tabchamps){
				foreach($tabchamps as $champ => $tabinfos){
					$save=array('field_db'=>"$dbname", 'field_collection'=>"$collname", 'field_name'=>"$champ", 'field_lastupdate'=>$mongodate, 'field_nbOccur'=> $tabinfos['nb_occur'], 'field_lenMAX'=> $tabinfos['lenMAX'], 'field_lenMIN'=> $tabinfos['lenMIN']);
					$this->mongo_collection->save($save);
				}
			}
		}
		
		$fd=fopen('/var/www/admin/phpmongadmin/'.$this->host.'_liste_collections.csv', 'w+');
		fwrite($fd, $listecsv);
		fclose($fd);
	}
	
	//selection d'une base de donnees
	public function selectDB($db=''){
		if($db=='') throw new Exception ("phpMongAdmin::selectDB => db=''\n");
		$this->mongo_db=$this->mongo_connection->selectDB($db);
	}
	
	//selection d'une collection
	public function selectCollection($collection=''){
		if($collection=='') throw new Exception ("phpMongAdmin::selectCollection => collection=''\n");
		$this->mongo_collection=$this->mongo_db->selectCollection($collection);
		$this->mongo_collection_cut=$collection;
	}
	
	//renvoie la liste des bases de donnÃ©es
	public function listDBs(){
		$db_list=$this->mongo_connection->listDBs();
		
		foreach( $db_list as $val ) {
			if(!in_array($val['name'], $this->tabExceptDB)){
				$tab[]=$val['name'];
			}
		}
		
		if(count($tab>0))
			asort($tab);
		
		return $tab;
	}
	
	//cree une base de donnees
	public function createDB($DBname=''){
		if($DBname=='') throw new Exception ("phpMongAdmin::createDB => DBname=''\n");
		self::selectDB($DBname);
		self::createCollection('0000000000');
		self::dropCollection('0000000000');
	}
	
	//supprime une base de données
	public function dropDB(){
		$this->mongo_db->drop();
	}
	
	//renvoie la liste des collections
	public function listCollections(){
		$collections_list=$this->mongo_db->listCollections();
		
		foreach($collections_list as $collection) {
			$colname=$collection->getName();
			if(!in_array($colname, $this->tabExceptCollection)){
				$tab[]=$colname;
			}
		}
		
		if(count($tab))
			asort($tab);
		
		return $tab;
	}
	
	//creation d'une collection
	public function createCollection($collection=''){
		if($collection=='') throw new Exception ("phpMongAdmin::createCollection => collection=''\n");
		$this->mongo_db->createCollection($collection);
	}
	
	//suppression d'une collection
	public function dropCollection($collection=''){
		if($collection=='') throw new Exception ("phpMongAdmin::dropCollection => collection=''\n");
		$this->mongo_db->dropCollection($collection);
	}
	
	//vidage d'une collection
	public function clearCollection($collection=''){
		if($collection=='') throw new Exception ("phpMongAdmin::clearCollection => collection=''\n");
		self::dropCollection($collection);
		self::createCollection($collection);
	}
	
	//renvoie la liste des collections
	public function displayCollection($page='', $tabtri='', $options=array()){
		if($page=='') throw new Exception ("phpMongAdmin::displayCollection => page=''\n");
		if($tabtri=='') throw new Exception ("phpMongAdmin::displayCollection => tabtri=''\n");
		if(count($options)==0) throw new Exception ("phpMongAdmin::displayCollection => options=''\n");
		
		$nbaffich=50;
		$sauts=$page*$nbaffich;
		
		$title="<center><b><font>".$this->host."</font> . <font>".$this->mongo_db."</font> . <font>".$this->mongo_collection_cut."</font></b> : ";
		
		$resultat0=$this->mongo_collection->find();
		$total=$resultat0->count();
		
		
		if($total>0){
			$title.=number_format($total,0,',', ' ')." résultats<br><br>";
			
			$resultat=$this->mongo_collection->find()->limit($nbaffich)->skip($sauts)->sort($tabtri);
			$tabtri=(array)$tabtri;
			
			$actions=self::getActionResult();
			$navigation=self::getNavResult($page, $nbaffich, $total);
			$table=self::getTabResult($resultat, '', $tabtri, $options);
		}
		else{
			$actions=self::getActionResult();
			$title.="La collection est vide<br><br>";
		}
		
		return $title.$actions.$navigation.$table;
	}
	
	//construction des actions
	public function getActionResult(){
		
		$chaine="";
		$chaine="<a href='javascript:search(\"".$this->host."\", \"".$this->mongo_db."\", \"".$this->mongo_collection_cut."\");'><img src='img/search_LR.png' class='img_menu' title='Rechercher'></a> ";
		$chaine.="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$chaine.="<a href='javascript:insertField(\"".$this->host."\", \"".$this->mongo_db."\", \"".$this->mongo_collection_cut."\");'><img src='img/insert_LR.png' class='img_menu' title='Insérer'></a> ";
		$chaine.="<a href='javascript:upload_file(\"".$this->host."\", \"".$this->mongo_db."\");'><img src='img/upload_LR.png' class='img_menu' title='Uploader un fichier'></a> ";
		$chaine.="<a href='javascript:create_unique_key(\"".$this->host."\", \"".$this->mongo_db."\", \"".$this->mongo_collection_cut."\");'><img src='img/key_LR.png' class='img_menu' title='Créer une clé unique'></a> ";
		$chaine.="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$chaine.="<a href='export.php?type=php&host=".$this->host."&db=".$this->mongo_db."&collection=".$this->mongo_collection_cut."' target='_blank'><img src='img/php_LR.png' class='img_menu' title='Exporter en tableau php'></a> ";
		$chaine.="<a href='export.php?type=csv&host=".$this->host."&db=".$this->mongo_db."&collection=".$this->mongo_collection_cut."' target='_blank'><img src='img/csv_LR.png' class='img_menu' title='Exporter en csv'></a> ";
		$chaine.="<a href='javascript:clearColl(\"".$this->host."\", \"".$this->mongo_db."\", \"".$this->mongo_collection_cut."\");'><img src='img/clean_LR.png' class='img_menu' title='Vider la collection'></a> ";
		$chaine.="<a href='javascript:dropColl(\"".$this->host."\", \"".$this->mongo_db."\", \"".$this->mongo_collection_cut."\");'><img src='img/trash_LR.png' class='img_menu' title='Supprimer la collection'></a> ";
		
		
		return $chaine;
	}
	
	//construction de la navigation dans la collection
	public function getNavResult($page='', $nbaffich='', $total=''){
		if($page=='') throw new Exception ("phpMongAdmin::getNavResult => page=''\n");
		if($nbaffich=='') throw new Exception ("phpMongAdmin::getNavResult => nbaffich=''\n");
		if($total=='') throw new Exception ("phpMongAdmin::getNavResult => total=''\n");
		$chaine='<br><br>';
		
		if($page>0){
			$pageprecedente=$page-1;
			$chaine.="<a href='javascript:displayCollection(\"".$this->mongo_collection_cut."\", 0, \"\");'><img src='img/first_LR.png' style='border:none;' title='Première page'></a>&nbsp;";
			$chaine.="<a href='javascript:displayCollection(\"".$this->mongo_collection_cut."\", $pageprecedente, \"\");'><img src='img/prev_LR.png' style='border:none;' title='Page précédente'></a>&nbsp;";
			
		}
		
		$chaine.=" &nbsp; Page $page &nbsp; ";
		
		if($total>$nbaffich+$page*$nbaffich){
			$pagesuivante=$page+1;
			$last=floor($total/$nbaffich);
			
			$chaine.="<a href='javascript:displayCollection(\"".$this->mongo_collection_cut."\", $pagesuivante, \"\");'><img src='img/next_LR.png' style='border:none;' title='Page suivante'></a>&nbsp;";
			$chaine.="<a href='javascript:displayCollection(\"".$this->mongo_collection_cut."\", $last, \"\");'><img src='img/last_LR.png' style='border:none;' title='Dernière page'></a>&nbsp;";
		}
		
		return $chaine;
	}
	
	//creation du tableau HTML a partir d'un tableau d'entete et un tableau de donnÃ©es
	public function getTabResult($resultat='', $fonction='', $tabtri='', $options=''){
		if($resultat=='') throw new Exception ("phpMongAdmin::getTabResult => resultat=''\n");
		
		$indexes=$this->mongo_collection->getIndexInfo();
		
// 		print_r($indexes);
		
		if($options['affichetype']=='1')
			$affichetype=true;
		
		$tabindexes=array();
		foreach($indexes as $clef => $index){
			foreach($index['key'] as $nom_index => $valeur){
				$unique=$index['unique'];
				if($unique=='')
					$unique="0";
				
				if($valeur==1)
					$tabindexes["$nom_index"]=$unique;
			}
		}
		
		
		foreach($resultat as $clef => $tabresultat){
			foreach($tabresultat as $clef2 => $valeur2){
				$tabData[$clef][$clef2]=$valeur2;
				$tabHead[$clef2]=$clef2;
			}
		}
		
		ksort($tabHead);
		
		$chaine="<br><br><table align='center' cellspacing='0' cellpadding='5' class='tabresult'>";
		$chaine.="<tr><th>&nbsp;&nbsp;&nbsp;&nbsp;ADMIN&nbsp;&nbsp;&nbsp;&nbsp;</th>";
		
		$trindex="<tr align='center'><td>";
		$trindex.="<input type='hidden' id='strkeys_multiaction' value=''>";
		$trindex.="<input type='checkbox' id='chkmulti_all' onclick='javascript:multiaction_chkall();' title='Selectionner toute cette page'>";
		$trindex.="<a href='javascript:multiaction_del()'><img src='img/delete_LR.png' class='imgbutton' title='Supprimer tous ces enregistrements'></a>";
		$trindex.="</td>";
		
		if(count($tabHead)>0){
			foreach($tabHead as $clef){
				
				if(count($options['champ_recherche'])>0){
					if(in_array($clef, $options['champ_recherche'])){
						$highlight="style='background-color:#f3a6a6;'";
					}
					else {
						$highlight="";
					}
				}
				else {
					$highlight="";
				}
				
				$trindex.="<td $highlight>";
				if($tabindexes[$clef]!=''){
					if($tabindexes[$clef]==1)
						$trindex.="<a href='javascript:deleteIndex(\"$clef\");'><img src='img/delindexUnique_LR.png' class='imgbutton' title=\"Supprimer l'index Unique\"></a> ";
					else
						$trindex.="<a href='javascript:deleteIndex(\"$clef\");'><img src='img/delindex_LR.png' class='imgbutton' title=\"Supprimer l'index\"></a> ";
				}
				else
					$trindex.="<a href='javascript:ensureIndex(\"$clef\");'><img src='img/index_LR.png' class='imgbutton' title='Ajouter un index'></a> ";
				
				
// 				$trindex.="<a href='javascript:ensureIndex(\"$clef\");'><img src='img/index_LR.png' class='imgbutton' title='Ajouter un index'></a> ";
				
				//TRI
				$imagetriup='';
				if($tabtri[$clef]==1)
					$imagetriup='ok';
				
				$imagetridown='';
				if($tabtri[$clef]==-1)
					$imagetridown='ok';
				
				if($fonction=='search'){
					$trindex.="<a href='javascript:search_validate(0, \"$clef|1\");'><img src='img/triup$imagetriup"."_LR.png' class='imgbutton' title='Tri ascendant'></a> ";
					
					$trindex.="<a href='javascript:search_validate(0, \"$clef|-1\");'><img src='img/tridown$imagetridown"."_LR.png' class='imgbutton' title='Tri descendant'></a> ";
				}
				else{
					$trindex.="<a href='javascript:displayCollection(\"".$this->mongo_collection_cut."\", 0, \"$clef|1\");'><img src='img/triup$imagetriup"."_LR.png' class='imgbutton' title='Tri ascendant'></a> ";
					
					$trindex.="<a href='javascript:displayCollection(\"".$this->mongo_collection_cut."\", 0, \"$clef|-1\");'><img src='img/tridown$imagetridown"."_LR.png' class='imgbutton' title='Tri descendant'></a> ";
				}
				////////////////////
				
				
				$trindex.="</td>";
				
				
				
				
				$chaine.="<th $highlight>$clef</th>";
			}
		}
		
		$chaine.="</tr>";
		$trindex.="</tr>";
		$chaine.=$trindex;
		$strallkeys="";
		if(count($tabData)>0){
			foreach($tabData as $clef => $tabresultat){
				
				$strallkeys.="|$clef";
				$chaine.="<tr align='center' onclick='javascript:multiaction_select(\"$clef\");' id='tr_$clef'><td>";
				$chaine.="<input type='checkbox' id='chkmulti_$clef' onclick='javascript:multiaction_chk(\"$clef\");'>";
				$chaine.="<a href='javascript:addOne(\"$clef\");'><img src='img/add_LR.png' class='imgbutton' title='Ajouter un champ'></a>";
				$chaine.="<a href='javascript:getRow(\"$clef\");'><img src='img/edit_LR.png' class='imgbutton' title='Modifier cet enregistrement'></a>";
				$chaine.="<a href='javascript:delRow(\"$clef\");'><img src='img/delete_LR.png' class='imgbutton' title='Supprimer cet enregistrement'></a> ";
				$chaine.="</td>";
				
				
				foreach($tabHead as $clef2){
					
					if(count($options['champ_recherche'])>0){
						if(in_array($clef2, $options['champ_recherche'])){
							$highlight="style='background-color:#f3a6a6;'";
						}
						else {
							$highlight="";
						}
					}
					else {
						$highlight="";
					}
					
					$type="";
					
					//telechargement de documents
					if($clef2=='_id')
						$id=$tabresultat[$clef2];
					
					if(($clef2=='filename')&&($this->mongo_collection_cut=='fs.files')){
						$title="title='FILE   $clef2   $id'";
						
						if($affichetype)
							$type="<b><i>FILE</i></b>";
						
						$chaine.="<td $title $highlight>$type <a href='download_document.php?id=$id&filename=".$tabresultat[$clef2]."&db=".$this->mongo_db."&host=".$this->host."' target='_blank'>".$tabresultat[$clef2]."</a></td>";
					}
					else{
						if(is_array($tabresultat[$clef2])){
							//gestion des tableaux dans un champ
							$title="title='ARRAY   $clef2   $id'";
							
							if($affichetype)
								$type="<b><i>AR</i></b>";
							
							$chaine.= "<td $title $highlight>$type ".htmlentities(utf8_decode(stripslashes(array2json($tabresultat[$clef2]))))."</td>";
						}
						else if(self::isMongoDate($tabresultat[$clef2])){
							$date=self::MongoDateFormat($tabresultat[$clef2], 'readable', 'fr');
							$title="title='MONGODATE   $clef2   $id'";
							
							if($affichetype)
								$type="<b><i>MD</i></b>";
							
							$chaine.="<td $title $highlight>$type $date</td>";
						}
						else if(is_int($tabresultat[$clef2])){
							$title="title='INTEGER   $clef2   $id'";
							
							if($affichetype)
								$type="<b><i>INT</i></b>";
							
							$chaine.="<td $title $highlight>$type ".htmlentities(utf8_decode(stripslashes($tabresultat[$clef2])))."</td>";
						}
						else if(is_string($tabresultat[$clef2])){
							$title="title='STRING   $clef2   $id'";
							
							if($affichetype)
								$type="<b><i>STR</i></b>";
							
							$chaine.="<td $title $highlight>$type ".htmlentities(utf8_decode(stripslashes($tabresultat[$clef2])))."</td>";
						}
						else if($clef2=='_id'){
							$title="title='MONGOID   $clef2   $id'";
							
							if($affichetype)
								$type="<b><i>MONGID</i></b>";
							
							$chaine.="<td $title $highlight>$type ".htmlentities(utf8_decode(stripslashes($tabresultat[$clef2])))."</td>";
						}
						else{
							$title="title='$clef2   $id'";
							
							if($affichetype && ($tabresultat[$clef2]!=""))
								$type="<b><i>??</i></b>";
							
							$chaine.="<td $title $highlight>$type ".htmlentities(utf8_decode(stripslashes($tabresultat[$clef2])))."</td>";
						}
					}
				}
				$chaine.="</tr>";
			}
		}
		
		$chaine.="</table>";
		
		$chaine.="<input type='hidden' id='strallkeys' value='$strallkeys'>";
		
		return $chaine;
	}
	
	//ajout d'un enregistrement
	public function addRow($tabdata=''){
		/*if($this->mongo_collection_cut=='test'){
			$tabdata['tab']=utf8_array_encode(array('testtab'=>'21225', 'testtab2'=>'zer'));
			print_r($tabdata);
			
		}*/
		
		if($tabdata=='') throw new Exception ("phpMongAdmin::addRow => tabdata=''\n");
		
		$this->mongo_collection->insert($tabdata);
		
		
	}
	
	//recuperation d'un enregistrement
	public function getRow($mongoId=''){
		if($mongoId=='') throw new Exception ("phpMongAdmin::getRow => mongoId=''\n");
		$mongoId=new MongoId($mongoId);
		$resultat=$this->mongo_collection->find(array("_id" => $mongoId));
		
		foreach($resultat as $clef => $tabresultat){
			foreach($tabresultat as $clef2 => $valeur2){
				if(is_array($valeur2)){
					$tab[$clef2]['value']=htmlentities(utf8_decode(stripslashes(array2json($valeur2))));
					$tab[$clef2]['type']='array';
				}
				else if(self::isMongoDate($valeur2)){
					$tab[$clef2]['value']=self::MongoDateFormat($valeur2, 'readable', 'fr');
					$tab[$clef2]['type']='mongoDate';
				}
				else if(is_int($valeur2)){
					$tab[$clef2]['value']=$valeur2;
					$tab[$clef2]['type']='int';
				}
				else{
					$tab[$clef2]['value']=htmlentities(utf8_decode(stripslashes($valeur2)));
					$tab[$clef2]['type']='txt';
				}
			}
		}
		
		return $tab;
	}
	
	//modification d'un enregistrement
	public function modifRow($mongoId='', $post=''){
		if($mongoId=='') throw new Exception ("phpMongAdmin::modifRow => mongoId=''\n");
		if($post=='') throw new Exception ("phpMongAdmin::modifRow => post=''\n");
		$mongoId=new MongoId($mongoId);
		$resultat=$this->mongo_collection->find(array("_id" => $mongoId));
// 		print_r($post);
		foreach($resultat as $clef => $tabresultat){
			foreach($tabresultat as $clef2 => $valeur2){
				if($post["type_$clef2"]=='txt')
					$tabdata[$clef2]=$post[$clef2];
				else if($post["type_$clef2"]=='array'){
					$tabdata[$clef2]=json_decode(stripslashes($post[$clef2]), true);
				}
				else if($post["type_$clef2"]=='mongoDate'){
					$tabdata[$clef2]=self::MongoDateFormat($post[$clef2], 'mongodate', 'fr');
				}
				else if($post["type_$clef2"]=='int'){
					$tabdata[$clef2]=(int)$post[$clef2];
				}
			}
		}
		
		$tabdata['_id']=$mongoId;
// 		print_r($tabdata);
		$this->mongo_collection->save($tabdata);
	}
	
	//suppression d'un enregistrement
	public function delRow($mongoId=''){
		if($mongoId=='') throw new Exception ("phpMongAdmin::delRow => mongoId=''\n");
		$mongoId=new MongoId($mongoId);
		$resultat=$this->mongo_collection->remove(array("_id" => $mongoId));
	}
	
	//ajout d'un champ
	public function addField($mongoId, $post){
		if($mongoId=='') throw new Exception ("phpMongAdmin::delRow => mongoId=''\n");
		if($post=='') throw new Exception ("phpMongAdmin::addField => post=''\n");
		$mongoId=new MongoId($mongoId);
		$resultat=$this->mongo_collection->find(array("_id" => $mongoId));
		
		foreach($resultat as $clef => $tabresultat){
			foreach($tabresultat as $clef2 => $valeur2){
				$tabdata[$clef2]=$valeur2;
			}
		}
		
		$tabdata[$post['champ']]=$post['valeur'];
		
		$tabdata['_id']=$mongoId;
		$this->mongo_collection->save($tabdata);
	}
	
	//ajouter un nouvel enregistrement par duplication
	public function duplicateRow($mongoId='', $post=''){
		if($mongoId=='') throw new Exception ("phpMongAdmin::duplicateRow => mongoId=''\n");
		if($post=='') throw new Exception ("phpMongAdmin::duplicateRow => post=''\n");
		$mongoId=new MongoId($mongoId);
		$resultat=$this->mongo_collection->find(array("_id" => $mongoId));
		
		foreach($resultat as $clef => $tabresultat){
			foreach($tabresultat as $clef2 => $valeur2){
				$tabdata[$clef2]=$post[$clef2];
			}
		}
		unset($tabdata['_id']);
		$this->mongo_collection->insert($tabdata);
	}
	
	//indexer
	public function ensureIndex($field=''){
		if($field=='') throw new Exception ("phpMongAdmin::ensureIndex => field=''\n");
		$this->mongo_collection->ensureIndex(array($field=>1));
	}
	
	//supprimer l'index
	public function deleteIndex($field=''){
		if($field=='') throw new Exception ("phpMongAdmin::deleteIndex => field=''\n");
		$result=$this->mongo_collection->deleteIndex(array($field=>1));
		print_r($result);
	}
	
	//recherche rapide
	public function quickSearch($requete=''){
		if($requete=='') throw new Exception ("phpMongAdmin::quickSearch => requete=''\n");
		foreach($requete as $clef => $valeur){
			if($clef=='_id'){
				$mongoId=new MongoId($valeur);
				$requete[$clef]=$mongoId;
			}
		}
		$resultat=$this->mongo_collection->find($requete)->limit(1000);
		$table=self::getTabResult($resultat, '');
		return $table;
	}
	
	//recherche
	public function search($post=''){
		if($post=='') throw new Exception ("phpMongAdmin::search => post=''\n");
		
		if($post['affichetype']=='1')
			$options['affichetype']=1;
		else
			$options['affichetype']=0;
		
		$formulaire="<form id='form_refresh_search'>";
		$formulaire.="<input type='hidden' name='action' value='".$post['action']."'>";
		$formulaire.="<input type='hidden' name='host' value='".$post['host']."'>";
		$formulaire.="<input type='hidden' name='db' value='".$post['db']."'>";
		$formulaire.="<input type='hidden' name='collection' value='".$post['collection']."'>";
		$formulaire.="<input type='hidden' name='tri' value='".$post['tri']."'>";
		$formulaire.="<input type='hidden' name='affichetype' value='".$options['affichetype']."'>";
		
		
		if($post['tri']!=""){
			list($champ, $sens)=explode('|', $post['tri']);
			$sens=(int)$sens;
			$tabtri=array("$champ" => $sens);
		}
		else
			$tabtri=array('_id' => 1);
		
		$requete=array();
		$requetehtml="";
		foreach($post as $clef => $valeur){
			if(substr($clef,0,4)=="txt_"){
				
				$idchamp=substr($clef,4);
				if($post["txt_$idchamp"]!=""){
					
					$formulaire.="<input type='hidden' name='txt_$idchamp' value='".$post["txt_$idchamp"]."'>";
					$formulaire.="<input type='hidden' name='op_$idchamp' value='".$post["op_$idchamp"]."'>";
					$filter.="$idchamp|";
					$options['champ_recherche'][]=$idchamp;
					
					if($idchamp=='_id'){
						$mongoId=new MongoId($valeur);
						$post["txt_$idchamp"]=$mongoId;
					}
					
					switch ($post["op_$idchamp"]){
						case 'sup':
							$requete["$idchamp"]=array('$gt' => $post["txt_$idchamp"]);
							$requetehtml.="$idchamp > ".$post["txt_$idchamp"]."<br>";
							break;
						case 'supequal':
							$requete["$idchamp"]=array('$gte' => $post["txt_$idchamp"]);
							$requetehtml.="$idchamp >= ".$post["txt_$idchamp"]."<br>";
							break;
						case 'inf':
							$requete["$idchamp"]=array('$lt' => $post["txt_$idchamp"]);
							$requetehtml.="$idchamp < ".$post["txt_$idchamp"]."<br>";
							break;
						case 'infequal':
							$requete["$idchamp"]=array('$lte' => $post["txt_$idchamp"]);
							$requetehtml.="$idchamp <= ".$post["txt_$idchamp"]."<br>";
							break;
						case 'notequal':
							$requete["$idchamp"]=array('$ne' => $post["txt_$idchamp"]);
							$requetehtml.="$idchamp != ".$post["txt_$idchamp"]."<br>";
							break;
						case 'like':
							if(substr($post["txt_$idchamp"],0,1)=='%'){
								$post["txt_$idchamp"]=str_replace('%', '(.*?)', $post["txt_$idchamp"]);
								$regex=new MongoRegex("/".$post["txt_$idchamp"]."$/msi");
								$requetehtml.="$idchamp LIKE ".$post["txt_$idchamp"]."<br>";
								$requete["$idchamp"]=$regex;
							}
							else if(substr($post["txt_$idchamp"],-1)=='%'){
								$post["txt_$idchamp"]=str_replace('%', '(.*?)', $post["txt_$idchamp"]);
								$regex=new MongoRegex("/^".$post["txt_$idchamp"]."/msi");
								$requetehtml.="$idchamp LIKE ".$post["txt_$idchamp"]."<br>";
								$requete["$idchamp"]=$regex;
							}
							else if(strpos('%', $post["txt_$idchamp"])===false){
								$requete["$idchamp"]=$post["txt_$idchamp"];
								$requetehtml.="$idchamp = ".$post["txt_$idchamp"]."<br>";
							}
							else{
								$post["txt_$idchamp"]=str_replace('%', '(.*?)', $post["txt_$idchamp"]);
								$regex=new MongoRegex("/".$post["txt_$idchamp"]."/msi");
								$requetehtml.="$idchamp LIKE ".$post["txt_$idchamp"]."<br>";
								$requete["$idchamp"]=$regex;
							}
							break;
							
						case 'equal':
							$requete["$idchamp"]=(float)$post["txt_$idchamp"];
							$requetehtml.="$idchamp = ".(float)$post["txt_$idchamp"]."<br>";
							break;
							
						case 'dblequal':
							$requete["$idchamp"]=array('$in' => array((float)$post["txt_$idchamp"], $post["txt_$idchamp"]));
							$requetehtml.="$idchamp == ".$post["txt_$idchamp"]."<br>";
// 							print_r($requete);
							break;
							
						default:
							echo "!!! OPERATEUR ICONNU !!!";
					}
				}
			}
		}
		
		$formulaire.="<input type='hidden' id='filter' value='$filter'>";
		
		$requetehtml="<b>Requete :</b><br>$requetehtml <br><input type='hidden' name='search_request_tmp' id='search_request_tmp' value='".str_replace('<br>', '', $requetehtml)."'>";
		$formulaire.="</form>";
		
// 		print_r($requete);
		
		$resultat=$this->mongo_collection->find($requete)->skip(0)->sort($tabtri);
		$tabtri=(array)$tabtri;
		$total=$resultat->count();
		
		$title="<center><b>".$this->mongo_collection."</b> : ";
		
		if($total>0){
			$requete_json=json_encode($requete);
			
			$title.="<a href='export.php?type=csv&host=".$this->host."&db=".$this->mongo_db."&collection=".$this->mongo_collection_cut."&requete=$requete_json' target='_blank'><img src='img/csv_LR.png' class='img_menu' title='Exporter en csv'></a> ";
			
			$title.="<br><br>$total Enregistrements ont été trouvés<br>";
			$table=self::getTabResult($resultat, 'search', $tabtri, $options);
		}
		else{
			$title.="<br><br>Aucun enregistrement n'a été trouvé<br>";
		}
		
		$requetehtml.="<br><a href='javascript:refresh_search();'>Actualiser</a>";
		$requetehtml.="<br><a href='javascript:edit_search(\"".$this->host."\", \"".$this->mongo_db."\", \"".$this->mongo_collection_cut."\");'>Editer recherche</a>";
		$requetehtml.="<br><a href='javascript:search(\"".$this->host."\", \"".$this->mongo_db."\", \"".$this->mongo_collection_cut."\");'>Nouvelle recherche</a>";
		
		
		return $title.$requetehtml.$formulaire.$table;
		
		
		/*
		$table=self::getTabResult($resultat);
		return $table;*/
	}
	
	//liste des champs de la collection
	public function getFields(){
		
		$db=$this->mongo_db;
		$coll=$this->mongo_collection_cut;
		
		$this->selectDB('phpMongAdmin');
		$this->selectCollection('fields');
		
		$resultat=$this->mongo_collection->find(array('field_db' => "$db", 'field_collection' => "$coll"));
		$total=$resultat->count();
		
		$tabHead=array();
		if($total>0){
			foreach($resultat as $clef => $tabresultat){
				$tabHead[$tabresultat['field_name']]=$tabresultat['field_name'];
			}
		}
		else{
			$this->selectDB($db);
			$this->selectCollection($coll);
			
			$resultat=$this->mongo_collection->findOne();
			
			foreach($resultat as $clef => $tabresultat){
				$tabHead[$clef]=$clef;
			}
		}
		
		
		ksort($tabHead);
		return $tabHead;
	}
	
	//unset un champ d'uyn enregistrement
	function unsetField($mongoId, $field){
		if($mongoId=='') throw new Exception ("phpMongAdmin::unsetField => mongoId=''\n");
		if($field=='') throw new Exception ("phpMongAdmin::unsetField => post=''\n");
		$mongoId=new MongoId($mongoId);
		$tabdata=$this->mongo_collection->findOne(array("_id" => $mongoId));
		
		unset($tabdata[$field]);
		
		$this->mongo_collection->save($tabdata);
	}
	
	//upload un fichier dans la base
	public function uploadFile($filepost){
		print_r($filepost);
		$this->mongo_db->getGridFS()->storeFile($filepost['flashUploadFiles']['tmp_name'], array("filename"=>$_FILES['flashUploadFiles']['name']));
	}
	
	//retourne true si le champ est du type MongoDate
	public function isMongoDate($champ=''){
		list($usec, $sec)=explode(' ', $champ);
		if(($usec<1)&&($sec>1000000000))
			return true;
		else
			return false;
	}
	
	//retourne la date d'entrée au format de sortie choisi
	public function MongoDateFormat($date_entree, $format_sortie, $type_sortie){
		if($date_entree=='') throw new Exception ("phpMongAdmin::MongoDateFormat => date_entree=''\n");
		if($format_sortie=='') throw new Exception ("phpMongAdmin::MongoDateFormat => format_sortie=''\n");
		if($type_sortie=='') throw new Exception ("phpMongAdmin::MongoDateFormat => type_sortie=''\n");
		
		if($format_sortie=='readable'){
			list($usec, $sec)=explode(' ', $date_entree);
			
			if($type_sortie=='fr'){
				return date('d-m-Y H:i:s', $sec);
			}
			else if($type_sortie=='us'){
				return date('Y-m-d H:i:s', $sec);
			}
		}
		else if($format_sortie=='mongodate'){
			if($type_sortie=='fr'){
				list($date, $heure)=explode(' ', $date_entree);
				list($jours, $mois, $annee)=explode('-', $date);
				if($heure==''){
					$heures=0;
					$minutes=0;
					$secondes=0;
				}
				else
					list($heures, $minutes, $secondes)=explode(':', $heure);
				
				return new MongoDate(strtotime("$annee-$mois-$jours $heures:$minutes:$secondes"));
			}
			else if($type_sortie=='us'){
				
				list($date, $heure)=explode(' ', $date_entree);
				list($annee, $mois, $jours)=explode('-', $date);
				if($heure==''){
					$heures=0;
					$minutes=0;
					$secondes=0;
				}
				else
					list($heures, $minutes, $secondes)=explode(':', $heure);
				
				return new MongoDate(strtotime("$annee-$mois-$jours $heures:$minutes:$secondes"));
			}
			else if($type_sortie=='us-short'){
				
				list($date, $heure)=explode(' ', $date_entree);
				list($annee, $mois, $jours)=explode('-', $date);
				if($heure==''){
					$heures=0;
					$minutes=0;
					$secondes=0;
				}
				else
					list($heures, $minutes, $secondes)=explode(':', $heure);
				
				return new MongoDate(strtotime("$annee-$mois-$jours"));
			}
		}
	}
	
	//unique_key
	public function create_unique_key($field=''){
		if($field=='') throw new Exception ("phpMongAdmin::create_unique_key => field=''\n");
		
		$tabfields=explode('|', $field);
		
		$tabindexes=array();
		foreach($tabfields as $clef => $valeur){
			if($valeur!=''){
				$tabindexes[$valeur]=1;
			}
		}
		
		$result=$this->mongo_collection->ensureIndex($tabindexes, true);
		
		print_r($result);
	}
	
	
	//suppression de plusieurs enregistrements
	public function multiaction_del($strkeys_multiaction){
		$tabkeys=explode('|', $strkeys_multiaction);
		
		foreach($tabkeys as $clef => $keyid){
			if($keyid!=''){
// 				echo "$keyid ";
				$this->delRow($keyid);
			}
		}
	}
	
	
}


?>