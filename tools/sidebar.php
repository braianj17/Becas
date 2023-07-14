<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">

  <tr>
    <td height="38" bgcolor="#EDEFF4">&nbsp;</td>
  </tr>
  <tr>
    <td bgcolor="#EDEFF4"><table width="196" border="1" align="center" cellpadding="8" cellspacing="0" bordercolor="#D9DDE8">
	<tr>
	  <td bgcolor="#D9DDE8" style="border-bottom:#EBEBEB solid 1px;" onmouseover="style.backgroundColor='#DAE1E4';" onmouseout="style.backgroundColor='';"><strong>Enlaces r&aacute;pidos</strong></td>
	  </tr>
	  <? if(!isset($_SESSION["firsttime"])){ ?>
	<tr>
        <td bgcolor="#EDEFF4" style="border-bottom:#EBEBEB solid 1px;" onmouseover="style.backgroundColor='#DAE1E4';" onmouseout="style.backgroundColor='';">
			<a title="Regresa al menu de inicio" <? if(substr_count($_SERVER['PHP_SELF'],"tools")>0){ ?>href="../index.php"<? }else{ ?>href="index.php"<? } ?>><img src="../imagen/arrow.gif" width="15" height="17" border="0" />Inicio</a>		</td>
	</tr>
	<? } ?>
	<tr>
        <td bgcolor="#EDEFF4" style="border-bottom:#EBEBEB solid 1px;" onmouseover="style.backgroundColor='#DAE1E4';" onmouseout="style.backgroundColor='';">
		<a href='../logout.php'><img src="../imagen/arrow.gif" width="15" height="17" border="0" />Cerrar Sesion</a>	</td>
	</tr>
	<? if(substr_count($_SERVER['PHP_SELF'],"tools")<=0){ ?>
	<tr>
	<td bgcolor="#EDEFF4" style="border-bottom:#EBEBEB solid 1px;" onmouseover="style.backgroundColor='#DAE1E4';" onmouseout="style.backgroundColor='';">
		<a href='extensiones.php'><img src="../imagen/arrow.gif" width="15" height="17" border="0" />Extensiones</a>	</tr>
		<? } ?>
	<tr>
	
	<td bgcolor="#EDEFF4" style="border-bottom:#EBEBEB solid 1px;" onmouseover="style.backgroundColor='#DAE1E4';" onmouseout="style.backgroundColor='';">
		<a href='../tools/cambiopass.php'><img src="../imagen/arrow.gif" width="15" height="17" border="0" />Cambiar contrase&ntilde;a</a>	</td>
	</tr>
    </table></td>
  </tr>
    <tr>
    <td  bgcolor="#EDEFF4"><div align="center">&nbsp;</div></td>
  </tr>
  <tr>
    <td bgcolor="#EDEFF4"><div align="center">
	
		<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="186" height="200">
          <param name="movie" value="../tools/users_online.swf?users_online=<? if(file_exists("tools/users_online.php")){
			include("tools/users_online.php");
		}else{
			if(file_exists("../tools/users_online.php")){
				include("../tools/users_online.php");
			}		
		}		
		?>&amp;date=<?=date("YmdHis")?>" />
          <param name="quality" value="high" />
          <param name="wmode" value="transparent" />
          <embed src="../tools/users_online.swf?users_online=<? if(file_exists("tools/users_online.php")){
			include("tools/users_online.php");
		}else{
			if(file_exists("../tools/users_online.php")){
				include("../tools/users_online.php");
			}		
		}		
		?>&amp;date=<?=date("YmdHis")?>" width="186" height="200" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" wmode="transparent"></embed>
	    </object>
		
    </div></td>
  </tr>
  <tr>
    <td height="225" bgcolor="#EDEFF4"><div align="center">
      <? include("calendar.php"); ?>
    </div></td>
  </tr>
  
</table>
