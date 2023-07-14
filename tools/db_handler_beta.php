<?
$db_host="localhost";
$db_nombre="exabeta";
$db_user="usexamena"; #joseluis
$db_passwd="mMfk=m]IY2EnP|M[iYDw"; #cual1964
$dbh_e= @mysql_connect($db_host,$db_user,$db_passwd) or die("Imposible conectar al servidor MYSQL");
if($dbh_e){
	mysql_select_db($db_nombre ,$dbh_e) or die("No es posible abrir la base de datos");
}else{
	die("No es posible continuar...");
}
?>
