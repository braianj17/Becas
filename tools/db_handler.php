<?
$dbhost="192.168.1.183";
	$dbname="campus";
	$dbuser="sa";
	$dbpasswd="SqL_515t_2011";

if(isset($dbname)){

$dbh= @mssql_connect($dbhost,$dbuser,$dbpasswd) or die("Imposible conectar al servidor MYSQL");
if($dbh){
	mssql_select_db($dbname ,$dbh) or die("No es posible abrir la base de datos");
}else{
	die("No es posible continuar...");
}
}else{
	die("Debe seleccionar una base de datos ".$cfg_campus);
}
?>