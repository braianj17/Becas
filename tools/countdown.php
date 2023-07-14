<?
session_start();
if(isset($_GET["SECTION_ID"])){
	$SECTIONID=$_GET["SECTION_ID"];
	$clientTime=$_GET["cTime"];
	$clientDate=$_GET["cDate"];		
}
?>
<style type="text/css">
<!--
body {
	background-color: #0C253E;
}
-->
</style>
<script>
<? if(isset($_SESSION["finish_time"][$SECTIONID])){ ?>
	createcount('countdown_div','<?=$_SESSION["finish_time"][$SECTIONID]?>','<?=$SECTIONID?>','saveData.php');
<? }else{
	include "tools/db_handler_beta.php";
	$r=mysql_query("SELECT *,now() start_time,ADDTIME('".$clientDate." ".$clientTime."', time_limit) finish_time FROM beta_sections WHERE section_id='".$SECTIONID."'",$dbh_e);
	if($fd=mysql_fetch_array($r)){
		if(!isset($_SESSION["start_time"][$SECTIONID])){
			$_SESSION["start_time"][$SECTIONID]=$fd["start_time"];
			$_SESSION["finish_time"][$SECTIONID]=$fd["finish_time"];
		}
	}
	?>
	createcount('countdown_div','<?=$_SESSION["finish_time"][$SECTIONID]?>','<?=$SECTIONID?>','saveData.php');	
	<?
} ?>
</script>
<div id="countdown_div" style="padding:5px; background-color:#0C253E; font-family:Arial, Helvetica, sans-serif; font-size:12px; font-weight:bold; color:#FFFFFF; text-align:right;"></div>