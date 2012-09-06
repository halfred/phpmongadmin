<?

Class ServerList{
	public static function getServerList(){
		$server_list['Dev']="mongodb://X.X.X.X";
		$server_list['Prod']="mongodb://Y.Y.Y.Y,Z.Z.Z.Z";
		return $server_list;
	}
	
	public static function getDefaultServer(){
		return $server_default='Dev';
	}
}

?>
