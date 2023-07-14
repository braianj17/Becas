<?
session_start();
if ($_SESSION['acceso']=='no'){
	header('Location: ../index.php');  
	exit; 
}
include "../conexiones/conecta_prog.php";
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><style type="text/css">
<!--
body,td,th {
	font-size: 12px;
	font-family: Geneva, Arial, Helvetica, sans-serif;
}
a:link {
	color: #002138;
	text-decoration: none;
}
a:visited {
	text-decoration: none;
	color: #002138;
}
a:hover {
	text-decoration: underline;
	color: #002138;
}
a:active {
	text-decoration: none;
	color: #002138;
}
.style1 {
	color: #EDE1B9;
	font-weight: bold;
}
.style6 {	color: #002B5F;
	font-size: 22px;
	font-family: Geneva, Arial, Helvetica, sans-serif;
}
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
	background-image: url(../imagen/bg.png);
	background-repeat: repeat-x;
	background-color: #F3F7FA;
}
.style7 {color: #666666}
.style8 {color: #003366}
-->
</style></head>
<body>
<table width="1000" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF">
<tr>
<td colspan="2">
	<table width="74%" border="0" align="center" cellpadding="0" cellspacing="0">
	<tr>
	<td><div align="center" style="color:#004677; font-size:12px"><img src="../imagen/banner_aspaa.png" width="1000" height="120" /></div></td>
	</tr>
	</table>
</td>
</tr>
<tr>
<td valign="top" bgcolor="#EBEBE9">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td><img src="../imagen/edificio.jpg" width="196" height="264"></td>
	</tr>
	<tr>
	<td bgcolor="#012E4B">
		<div align="center" style="color:#FFFFFF; font-weight:bold">
			<img src="../imagen/white_arrow.png" width="8" height="4">&nbsp;&nbsp;Enlaces r&aacute;pidos 
		</div>
	</td>
	</tr>
	<tr>
	<td>
		<table width="100%" cellpadding="8" cellspacing="3">
		<tr>
		<td bgcolor="#E1E0DD" style="border-bottom:#EBEBEB solid 1px;" onMouseOver="style.backgroundColor='#DAE1E4';" onMouseOut="style.backgroundColor='';">
			<a title="Regresa al menu de inicio" href="../index.php">
			<img src="../imagen/arrow.gif" width="15" height="17" border="0" />Inicio
			</a>
		</td>
		</tr>
		<tr>
		<td bgcolor="#E1E0DD" style="border-bottom:#EBEBEB solid 1px;" onMouseOver="style.backgroundColor='#DAE1E4';" onMouseOut="style.backgroundColor='';">
			<a href='../logout.php'><img src="../imagen/arrow.gif" width="15" height="17" border="0" />Cerrar Sesion</a>
		</td>
		</tr>
		</table>
	</td>
	</tr>
	</table>
</td>
<td width="804"  valign="top" style="background-image:url(../imagen/escudo_uvp_bw.jpg); background-repeat:no-repeat; background-position:center; padding:20px">
	<table width="700" border="0" align="center" cellpadding="5" cellspacing="0">
	<tr>
	<td>
		<span class="style6">Directorio de extensiones </span>
		<hr style="border:#000066 solid 3px" /></td>
	</tr>
	<tr>
	<td><div align="left"><img src="../imagen/graduacion.jpg" width="718" height="223"></div></td>
	</tr>
	<tr>
	<td><span >Seleccione una ubicaci&oacute;n </span></td>
	</tr>
	</table>
	<br>
	<table width="700" border="1" align="center" cellpadding="3" cellspacing="0" bordercolor="#CAC9C7">
	<tr>
	<td  bgcolor="#EBEBE9">
	<div align="center" class="style1 style7">
	<div align="left">Edificios</div>
	</div>
	</td>
	</tr>
	<tr>
	<td>
		<table width="100%" border="0" cellspacing="5" cellpadding="3">
		<tr>
		<td>
		<div align="left">
		<img src="../imagen/arrow.gif" width="15" height="17" border="0" />
		<a href="<?=$_SERVER['PHP_SELF']?>?key=kukulkan"><strong> Complejo Kukulcan</strong></a>
		</div>
		</td>
		</tr>
		<tr>
		<td>
			<span style="font-size:11px; font-weight:bold; font-family:Geneva, Arial, Helvetica, sans-serif">
			<blockquote class="style8">3 Sur 5759 Col. El Cerrito</blockquote> 
			</span>
		</td>
		</tr>
		<tr>
		<td>
			<div align="left">
			<a title="Regresa al menu de inicio" href="../index.php">
			<img src="../imagen/arrow.gif" width="15" height="17" border="0" />
			</a>
			<a href='<?=$_SERVER['PHP_SELF']?>?key=cuetlaxcoapan' target='_self'><strong>Complejo Cuetlaxcoapan</strong></a>
			</div>
		</td>
		</tr>
		<tr>
		<td>
			<blockquote class="style8">
		</td>
		</tr>
		<tr>
		<td>
			<div align="left">
			<a title="Regresa al menu de inicio" href="../index.php"><img src="../imagen/arrow.gif" width="15" height="17" border="0" /></a>
			<a href='<?=$_SERVER['PHP_SELF']?>?key=quetzalcoatl' target='_self'><strong>Edificio Quetzalcoatl</strong></a></div>
		</td>
		</tr>
		<tr>
		<td>
		<blockquote class="style8">
		<span style="font-size:11px; font-weight:bold; font-family:Geneva, Arial, Helvetica, sans-serif">2 Sur 5945 Col.Bugambilias</span>
		</blockquote>
		</td>
		</tr>
		<tr>
		<td>
			<div align="left">
			<a title="Regresa al menu de inicio" href="../index.php"><img src="../imagen/arrow.gif" width="15" height="17" border="0" /></a>
			<a href='<?=$_SERVER['PHP_SELF']?>?key=univatur' target='_self'><strong>Univatur</strong></a></div>
		</td>
		</tr>
		<tr>
		<td>
			<blockquote class="style8">
			<span style="font-size:11px; font-weight:bold; font-family:Geneva, Arial, Helvetica, sans-serif"> 2 Sur 5945 Col. Bugambilias&nbsp; </span></blockquote>
		</td>
		</tr>
		<tr>
		<td>
			<div align="left">
			<a title="Regresa al menu de inicio" href="../index.php"><img src="../imagen/arrow.gif" width="15" height="17" border="0" /></a>
			<a href='<?=$_SERVER['PHP_SELF']?>?key=calmecac' target='_self'><strong>Edificio Calmecac</strong></a></div>
		</td>
		</tr>
		<tr>
		<td>
			<blockquote class="style8">
			<span style="font-size:11px; font-weight:bold; font-family:Geneva, Arial, Helvetica, sans-serif"> 3 Sur 5758 Col. El Cerrito </span></blockquote>
		</td>
		</tr>
		</table>
	</td>
	</tr>
	</table>
	<? if(isset($_GET["key"])){ ?>
		<br>
		<table width="700" border="1" align="center" cellpadding="3" cellspacing="0" bordercolor="#CAC9C7">
		<tr>
		<td  bgcolor="#EBEBE9"><div align="center" class="style1 style7">
			<div align="left">Extensiones del edificio <?=strtoupper($_GET["key"])?></div></div>
		</td>
		</tr>
		<tr>
		<td>
			<table width="100%" border="0" cellspacing="5" cellpadding="3">
			<tr>
			<td bgcolor="#F9F9F9"><div align="left">Nombre</div></td>
			<td bgcolor="#F9F9F9">Puesto</td>
			<td bgcolor="#F9F9F9">N&ugrave;mero/Ext</td>
			</tr>
			<?
			$r0=mysql_query("SELECT * FROM extensiones WHERE edificio='".$_GET["key"]."'",$link);
			while($fd0=mysql_fetch_array($r0)){ ?>			  
              <tr>
                <td><?=$fd0["nombre"]?></td>
                <td><?=$fd0["puesto"]?></td>
                <td><?=$fd0["extension"]?></td>
              </tr>
			<? } ?>
          </table>
		</td>
		</tr>
		</table>
	<? } ?>
</td>
</tr>
<? include "../tools/footer.php"; ?>
</table>
</body>
</html>