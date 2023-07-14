<?php 
class mysqlCnx
    {
	private $h;
	private $user_db;
	private $passwd_db;
	private $initial_catalog;
	private $cnx=false;
        
	public function __construct($host,$user,$passwd,$initial_catalog)
                {
		$this->h=$host;
		$this->user_db=$user;
		$this->passwd_db=$passwd;
		$this->initial_catalog=$initial_catalog;
                }
	public function Open()
                {
		$this->cnx=mysql_connect($this->h,$this->user_db,$this->passwd_db) or die("Imposible conectar al servidor MySQL ".mysql_error());
		mysql_select_db($this->initial_catalog,$this->cnx);
		return $this->cnx;
                }
	public function Close()
                {
		mysql_close($this->cnx);
                }
    }
?>