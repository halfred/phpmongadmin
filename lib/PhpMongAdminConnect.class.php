<?php

/*
Connexion a la base mongo avec login
(necessaire pour lister les bdd)

require dojo

*/

include_once "Mongo/Admin.php";

class PhpMongAdminConnect{
	
	private $connection;
	private $host;
	
	private $hostoptions;
	private $login="admin";
	private $pass="admin";
	
	public function __construct($host=''){
		
		if($host=='') Throw new Exception('MongoConnect::__construct $host=null');
		
		$server_list=ServerList::getServerList();
		if($server_list[$host]=='') Throw new Exception('MongoConnect::__construct $server_list[$host]=null');
		
		$this->host=$server_list[$host];
		
		if(count(explode(',', $this->host))>1)
			$this->hostoptions=array('persist' => 'php','replicaSet' => true);
		else
			$this->hostoptions=array('persist' => 'php');
		
		$mongo_connect = new MongoAdmin($this->host, $this->hostoptions);
		$mongo_connect->login($this->login, $this->pass);
		$this->connection=$mongo_connect;
		$this->host=$host;
	}
	
	public function getConnection(){
		return $this->connection;
	}
	
	public function closeConnection(){
		$this->connection->close();
	}
}



?>
