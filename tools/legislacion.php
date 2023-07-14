<?
session_start();
if($_SESSION['acceso']=='no'){
	header('Location: ../index.php');
	exit;
}
$CHECKED=array();
if(isset($_POST['categorias'])){
	foreach($_POST['categorias'] as $key=>$val){
		if($val==1){ 
			array_push($CHECKED,$key);
		}
	}
}
include "../clases/read_xml.php";
$xmlurl = "descargas.xml";
$xmlreader = new xmlreader1 ($xmlurl);
$xml = $xmlreader->parse();
?>
<html>
<head>
<style type="text/css">
<!--
.Estilo1 {
        font-size: 26px;
        font-family: Arial, Helvetica, sans-serif;
        color: #0066FF;
}

.Estilo2 {
        font-size: 18px;
        font-family: Arial, Helvetica, sans-serif;
        color: #0066FF;
}
body,td,th {
	font-family: Arial, Helvetica, sans-serif;
	font-size: small;
}
a:link {
	color: #333333;
	text-decoration: none;
}
a:visited {
	text-decoration: none;
	color: #333333;
}
a:hover {
	text-decoration: underline;
	color: #333333;
}
a:active {
	text-decoration: none;
	color: #333333;
}
.style1 {color: #FFFFFF}
.style2 {
	font-size: medium;
	font-weight: bold;
}
body {
	background-image: url(../imagen/escudo_uvp_bw.jpg);
	background-repeat: no-repeat;
	background-position:center;
	background-position:bottom;	
}
-->
</style>
<script>
function updatef(){
	document.form1.submit();
}
</script>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"></head>
<body>
<form name="form1" method="post">
<center>
<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0">
<tr>
<td>
  <div align="left"><a href='doc_aca.php'><img src="../imagen/arrow.gif" width="15" height="17" border="0">Documentos Acad&eacute;micos</a>  </div></td>
</tr>
</table>
</br>


<table width="100%" border="1" align="center" cellpadding="5" cellspacing="0" bordercolor="#D7D7D7">
<tr>
<td bgcolor="#26476C"><div align='center' class='Estilo1 style1 style2'>LEGISLACIÓN UNIVERSITARIA UVP 2010</div></td>
</tr>
<tr>
<td bgcolor="#E8E8E8">
	<table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
	
	<? for ($i=0; $i<sizeof ($xml["categorias"]["#"]["categoria"]); $i++){ ?>	
		<tr>
		<td bgcolor="#F3F3F3">
			<input type="checkbox" name="categorias[<?=$xml["categorias"]["#"]["categoria"][$i]["#"]["nombre"][0]["#"]?>]" value="1" onClick="updatef()" <? if(in_array($xml["categorias"]["#"]["categoria"][$i]["#"]["nombre"][0]["#"],$CHECKED)){ $display="block"; ?> checked <? }else{ $display="none"; }?>/><b><?=$xml["categorias"]["#"]["categoria"][$i]["#"]["nombre"][0]["#"]?></b>	</td>
		</tr>
		<tr>
		<td bgcolor="#FBFBFB">
			<div id="<?=$xml["categorias"]["#"]["categoria"][$i]["#"]["nombre"][0]["#"]?>" style="display:<?=$display?>; padding-left:50px">
			<table  width="100%" border="0" align="center" cellpadding="5" cellspacing="0">
			<? for($j=0; $j<sizeof($xml["categorias"]["#"]["categoria"][$i]["#"]["links"][0]["#"]["link"]); $j++){ ?>
				<tr>
				<td>
				<a href="<?=$xml["categorias"]["#"]["categoria"][$i]["#"]["links"][0]["#"]["link"][$j]["@"]["url"]?>" target="_blank"><?=$xml["categorias"]["#"]["categoria"][$i]["#"]["links"][0]["#"]["link"][$j]["@"]["id"]?>. <?=utf8_decode($xml["categorias"]["#"]["categoria"][$i]["#"]["links"][0]["#"]["link"][$j]["#"])?></a> 
				</td>
				</tr>
			<? } ?>
			</table>
			</div>
		</td>
		</tr>
	<? } ?>
	









		</table>
	  </div>	</td>
	</tr>
  </table>
</td>
</tr>
</table>
</form>
</body>
</html>