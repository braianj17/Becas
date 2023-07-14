<?php 
class mssqlCnx{
	private $h;
	private $user_db;
	private $passwd_db;
	private $initial_catalog;	
	private $connectionInfo;
	
	private $cnx=false;
	private $link_connect=false;
	public function __construct($host,$user,$passwd,$initial_catalog){
		$this->h=$host;
		$this->user_db=$user;
		$this->passwd_db=$passwd;				
		$this->initial_catalog=$initial_catalog;						
		$this->connectionInfo = array( "Database"=>$this->initial_catalog, "UID"=>$this->user_db, "PWD"=>$this->passwd_db);
	}
	public function Open(){
		$this->link_connect = mssql_connect( $this->h, $this->user_db, $this->passwd_db, true);
		$this->cnx = mssql_select_db( $this->initial_catalog, $this->link_connect );

		// $this->cnx = sqlsrv_connect( $this->h, $this->connectionInfo) or die("Imposible conectar al servidor SQL ".mysql_error());
		return $this->link_connect;	
	}
}
?>