<?php
$PREFIX=array("EV","CI","PR","PE","DA","PA","OE","RH","SE","AD");
if(file_exists("conexiones/conecta_prog.php")){
	require_once("conexiones/conecta_prog.php");
}else{
	if(file_exists("../conexiones/conecta_prog.php")){
		require_once("../conexiones/conecta_prog.php");
	}else{
		$offline=1;
	}
}
?>
<users>
<? $r0=mysql_query("SELECT * FROM gente_online ORDER BY date DESC",$link);
while($fd0=mysql_fetch_array($r0)){
	$prefix=substr($fd0["USER_ID"],0,2);
	if($prefix=="PR" || $prefix=="AD" || !in_array(strtoupper($prefix),$PREFIX)){
		$account=substr($fd0["USER_ID"],2);
	}else{
		$account=substr($fd0["USER_ID"],4);	
	}
	if(in_array(strtoupper($prefix),$PREFIX)){
		$q1="SELECT *,CONCAT(nombre,' ',ap_paterno,' ',ap_materno) AS nombre_usuario FROM empleado_dp WHERE clave_e LIKE '".$account."'";
	}else{
		$q1="SELECT *,CONCAT(nombre,' ',ap_paterno,' ',ap_materno) AS nombre_usuario FROM a_datosp WHERE cuenta LIKE '%".$account."%'";		

	}
	$r1=mysql_query($q1,$link);		
	if($fd1=mysql_fetch_array($r1)){
		$nombre_usuario=$fd1["nombre_usuario"];
	}else{
		$nombre_usuario="Desconocido";	
	}
		if(isset($prefix) && $prefix!=""){
			$prefix="(".$prefix.")";
		}
	
	?>
	<user id="<?=$fd0['USER_ID']?>" username="-<?=strtoupper($prefix)?> <?=$nombre_usuario?>"/>	   
<? } ?>
<? if($offline==1){ ?>
	<user id="x" username="Error connection"/>	   
<? } ?>
</users>