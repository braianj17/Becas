<?php
if(file_exists("conexiones/conecta_prog.php")){
	require_once("conexiones/conecta_prog.php");
}else{
	if(file_exists("../conexiones/conecta_prog.php")){
		require_once("../conexiones/conecta_prog.php");
	}else{
		die("No es posible conectar a la base de datos");
	}
}
// Tiempo máximo de espera
$time = 5 ;
// Momento que entra en línea
$date = time() ;
// Recuperamos su IP
$USER_ID = $_SESSION["account"];
// Tiempo Limite de espera 
$limite = $date-$time*60 ;
// si se supera el tiempo limite (5 minutos) lo borramos
$r_u=mysql_query("delete from gente_online where date < $limite",$link);
// tomamos todos los usuarios en linea
$query0="select * from gente_online where USER_ID='$USER_ID'";
$resp = mysql_query($query0,$link) ;
// Si son los mismo actualizamos la tabla gente_online
if(mysql_num_rows($resp)>0) {
	$r_u1=mysql_query("update gente_online set date='$date' where USER_ID='$USER_ID'",$link) ;
}else {
	$r_u1=mysql_query("insert into gente_online (date,USER_ID) values ('$date','$USER_ID')",$link) ;
}
// Seleccionamos toda la tabla
$query = "SELECT * FROM gente_online";
// Ocultamos algún mensaje de error con @
$resp = @mysql_query($query,$link) or die(mysql_error());
// almacenamos la consulta en la variable $usuarios
$usuarios = mysql_num_rows($resp);
// Si hay 1 usuarios se muestra en singular; si hay más de uno, en plural
echo $usuarios;
?>