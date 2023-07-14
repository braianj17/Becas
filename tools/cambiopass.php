<?php
session_start();
require_once("../conexiones/conecta_prog.php");
if($_SESSION["acceso"]=="si"){
	$main="../index.php";
}else{
	$main="../index.php";
}
?>
<html>	 					   
<head> 
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<? include "css.php"; ?>
<script>
function submitf() {
	if (document.form1.currPasswd && (document.form1.currPasswd.value == "" || document.form1.newPasswd.value == "" || document.form1.newPasswd2.value == "")){
		alert("Por favor llene todos los campos");
		document.form1.currPasswd.focus();
	}else{
		if(document.form1.newPasswd.value== document.form1.newPasswd2.value){	
			if(document.form1.newPasswd.value.length < 6 ){
				alert("Minimo 6 caracteres ");
				document.form1.newPasswd.value="";
				document.form1.newPasswd2.value="";
				document.form1.newPasswd.focus();
			}else{
				form1.submit();
			}
		}else{
			alert("La nueva contraseña no coincide en ambos campos");
			document.form1.currPasswd.focus();
		}
	}
}
function capLock(e){
    kc = e.keyCode ? e.keyCode : e.which ;
    sk = e.shiftKey ? e.shiftKey: ( (kc == 16) ? true : false ) ;
    if(((kc >= 65 && kc <= 90) && !sk ) || ((kc >= 97 && kc <= 122 ) && sk))
		document.getElementById('caplock').style.visibility = 'visible';
	else document.getElementById('caplock').style.visibility = 'hidden';
}
</script>

</head>

<body>
<table width="1000" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF">
  <tr>
    <td colspan="2"><? include "header.php"; ?></td>
  </tr>

  <tr>
    <td valign="top" bgcolor="#F9F9F9"><? if($_SESSION["acceso"]=="si"){ include "../tools/sidebar.php"; }else{ include "../tools/sidebar_nosession.php"; } ?></td>
    <td valign="top" style="background-image:url(../imagen/escudo_uvp_bw.jpg); background-repeat:no-repeat; background-position:center;">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td bgcolor="#EDEFF4">&nbsp;</td>
      </tr>
      <tr>
        <td style="padding-top:30px; padding-left:30px;">
		
		  <table width="700" border="0" align="center" cellpadding="5" cellspacing="0" bordercolor="#F9F9F9">
            <tr>
              <td bgcolor="#D9DDE8"><strong>Nota:</strong></td>
            </tr>
            <tr>
              <td bgcolor="#F4F4F4"><div align="left">Estas a punto de cambiar tu contrase&ntilde;a, te recordamos que si lo haces, la pr&oacute;xima vez que inicies sesion en el sistema, deber&aacute;s utilizar la nueva contrase&ntilde;a que elijas. </div></td>
            </tr>
            <tr>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td><? if(!isset($_POST['tx'])){ ?>
                  <form name="form1" method="post">
				  <input type="hidden" name="tx" value="save">
                    <table border='1' align='center' cellpadding='3' cellspacing='0' bordercolor="#F4F4F4">
                      <tr>
                        <td colspan='2' align='center' bgcolor='#F2F5F9'><strong>Cambiar contrase&ntilde;a</strong> </td>
                      </tr>
					  <? if($_SESSION["acceso"]=="si"){ ?>
                      <tr>
                        <td align = 'right' bgcolor="#FBFBFB"><div align="right">Contrase&ntilde;a actual</div></td>
                        <td><input name = "currPasswd" type = "password" id="currPasswd" maxlength = "15" /></td>
                      </tr>
					  <? } ?>
                      <tr>
                        <td align = 'right' bgcolor="#FBFBFB"><div align="right">Nueva contrase&ntilde;a</div></td>
                        <td><input name = "newPasswd" type = "password" id="newPasswd" maxlength = "15" onKeyPress="capLock(event)"><div id="caplock" style="visibility: hidden; color:#000000; position:absolute; background:#FFCCCC; font-weight:normal; padding:5px; opacity:0.4;filter:alpha(opacity=40);">Escribes con Mayúsculas.</div></td>
                      </tr>
                      <tr>
                        <td align = 'right' bgcolor="#FBFBFB"><div align="right">Repetir contrase&ntilde;a</div></td>
                        <td><input name = "newPasswd2" type = "password" id="newPasswd2" maxlength = "15"></td>
                      </tr>
                      <tr>
                        <td colspan = "2" align = "center" bgcolor="#EBF7E1"><div align="left" style="color:#000000; font-size:12px; font-weight:bold">
                            <div align="center">M&iacute;nimo 6 y m&aacute;ximo 15 caracteres alfanum&eacute;ricos</div>
                        </div></td>
                      </tr>
                      <tr>
                        <td colspan = '2' align = 'center' bgcolor="#F7F7F7"><input name="button" type="button" onClick="submitf()" value="Cambiar"></td>
                      </tr>
                    </table>
                  </form>
                <? }else{
		$r=mysql_query("SELECT * FROM claves WHERE id_sistem='".$_SESSION['nombre']."'",$link);
		if($fd=mysql_fetch_array($r)){
			if($fd['clave']==$_POST['currPasswd'] || $_SESSION["acceso"]!="si"){
				$r0=mysql_query("UPDATE claves SET clave='".mysql_real_escape_string($_POST['newPasswd'])."', status='c' WHERE id_sistem='".$_SESSION['nombre']."'",$link);
				if(mysql_affected_rows($link)<=0){
					$MSG="Ocurri&oacute; un error al intentar actualizar la contrase&ntilde;a";
				}else{
					$MSG="Su contrase&ntilde;a fu&eacute; actualizada con &eacute;xito";
					$r1=mysql_query("INSERT INTO movimientos (tipo,fecha,hora,dato1,dato2,dato3) values('S','".date("Y-m-d")."','".date("H:i:s")."','','".$_SESSION['nombre']."','')",$link);
					if(isset($_SESSION["firsttime"])){
						unset($_SESSION["firsttime"]);
					}
				}
			}else{ 
				$MSG="La contrase&ntilde;a actual es incorrecta, por favor int&eacute;ntelo nuevamente";
			}
		}else{
			$MSG="Ocurrio un error desconocido y no es posible continuar.";
		}
		?>
                  <table width='310' border='1' align='center' cellpadding='8' cellspacing='0' bordercolor="#F4F4F4">
                    <tr>
                      <td align='center' bgcolor='#F2F5F9'><strong>Cambiar contrase&ntilde;a</strong> </td>
                    </tr>
                    <tr>
                      <td align = "center" bgcolor="#FBFCFD"><div align="left">
                        <?=$MSG?>
                      </div></td>
                    </tr>
                    <tr>
                      <td align = 'center' bgcolor="#F7F7F7"><input name="button2" type="button" onClick="javascript:window.open('<?=$main?>','_self')" value="Aceptar" /></td>
                    </tr>
                  </table>
                <? } ?>              </td>
            </tr>
          </table>		</td>
      </tr>
</table>
</td>
</tr>
<? include "footer.php"; ?>
</table>
</body>
</html>