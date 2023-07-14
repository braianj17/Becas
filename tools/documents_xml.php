<?php
session_start();
include "../conexiones/conecta_prog.php";
header("Content-Type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?><root>";
$q="SELECT * FROM categorias_documentos,privilegios_documentos WHERE tipo_sesion='".$_SESSION["usuario"]."' AND id_categoria=categoria ORDER BY id_categoria";
$r=mysql_query($q,$link);
while($fd=mysql_fetch_array($r)){
	echo "<categoria id='".utf8_encode($fd['nombre_categoria'])."'>";
		$r1=mysql_query("SELECT * FROM documentos_institucionales WHERE categoria='".$fd["id_categoria"]."'",$link);
		while($fd1=mysql_fetch_array($r1)){
		echo "<article>";
		echo "<title>titulo1</title>";
		echo "<url>".utf8_encode($fd1["nombre_documento"])."</url>";
		echo "<urltext>".utf8_encode($fd1["path"])."</urltext>";
		echo "</article>";
		}
	echo "</categoria>";
}
echo "</root>";
?>