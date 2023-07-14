<?php
session_start();
if (empty($_SERVER['HTTPS'])) {
    header('Location: https://'.$_SERVER['SERVER_NAME'] );
}

if(isset($_SESSION["logged"]) && $_SESSION["logged"]==true){
    header("Location: index.php");
}

if(isset($_GET["clave"]))
{
    $recibo = $_GET["clave"];
}
else
{
    $recibo = '';
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html" />
    <meta charset="UTF-8">
    <title>Universidad del Valle de Puebla</title>
    <link rel='shortcut icon' href='favicon.ico'>
    <link href="include/stylesheet.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="tools/jquery-1.4.4.js"></script>
    <script type="text/javascript">
    $(document).ready(function()
    {
        $("#loading").hide();
        $("#login_btn").click(function()
        {
            //Funcion al dar clic en el boton INICIAR
            inicio();
        });

        function inicio(){
            if($("#clave").val()!="")
            {
                $('#login_frm').slideUp('slow', function() {});
                $('#loading').slideDown('slow', function() {});
                $('#loading').show();
                var var_recibo = document.getElementById("clave").value;
                $('#loginDetails').load('login_check.php?recibo='+var_recibo+'&ctrl=<?=date("YmdHis")?>');
            }
            else
            {
                $('#loginDetails').html('Escribe tu número de recibo y pulsa el bot&oacute;n.');
            }
        }

        $('#loginDetails').ajaxComplete(function(event, XHR, ajaxOptions)
        {
            var results = XHR.responseText ? XHR.responseText : (XHR.responseHTML ? XHR.responseHTML : XHR.responseXML);
            if(results=="SUCCESS")
            {
                //Creacion de la sesion del alumno para el inicio del proceso de Solicitud de Nueva Beca
                var MSG="Bienvenido, estamos creando su sesi&oacute;n";
                $('#loginDetails').html(MSG);
                $('#loading').fadeOut(3000, function() {
                $('#loginDetails').fadeOut('slow',function(){});
                    window.open('beca_solicitud.php','_self');
                });
            }
            else if(results=="SUCCESS2")
            {
                //Creacion de la sesion del alumno para el inicio del proceso de Renovacion de Beca
                var MSG="Bienvenido, nuevamente....";
                $('#loginDetails').html(MSG);
                $('#loading').fadeOut(3000, function() {
                $('#loginDetails').fadeOut('slow',function(){});
                    window.open('renovar_beca.php','_self');
                });
            }
            //Acceso a los sistemas siguientes
            //Carta de aceptacion y Documentos
            else if(results == "SUCCESS3")
            {
                //Creacion de la sesion del alumno, se envia al sistema de carta de aceptacion
                MSG="Bienvenido, nuevamente...";
                $('#loginDetails').html(MSG);
                $('#loading').fadeOut(3000, function() {
                    $('#loginDetails').fadeOut('slow',function(){});
                    window.open('resolutivo.php','_self');
                });

                //MSG="Los Resolutivos de Beca estan siendo Actualizados, la respuesta de aplicación de Beca será el día 31 de Enero.";
                //mensajes(MSG);
            }
            else if(results == "SUCCESS4")
            {
                //Creacion de la sesion del alumno, se envia al sistema de documentos de beca Academica
                MSG="Bienvenido, nuevamente...";
                $('#loginDetails').html(MSG);
                $('#loading').fadeOut(3000, function() {
                    $('#loginDetails').fadeOut('slow',function(){});
                    window.open('documentos_rendimiento.php','_self');
                });
                //MSG="El sistema de Documentos de Beca de Rendimiento Académico se esta actualizando, disculpa las molestias, te pedimos intentar mas tarde, Gracias.";
                //mensajes(MSG);

            }
            else if(results == "SUCCESS6")
            {
                //Creacion de la sesion del alumno, se envia al sistema de documentos de beca Academica
                MSG="Bienvenido, nuevamente...";
                $('#loginDetails').html(MSG);
                $('#loading').fadeOut(3000, function() {
                    $('#loginDetails').fadeOut('slow',function(){});
                    window.open('beca_formulario.php','_self');
                });
                //MSG="El formulario para solicitud de Beca de Rendimiento Académico se esta actualizando, disculpa las molestias, te pedimos intentar mas tarde, Gracias.";
                //mensajes(MSG);

            }
            else if(results == "SUCCESS5")
            {
                //Creacion de la sesion del alumno, se envia al sistema de documentos de beca Empresarial
                MSG="Bienvenido, nuevamente...";
                $('#loginDetails').html(MSG);
                $('#loading').fadeOut(3000, function() {
                $('#loginDetails').fadeOut('slow',function(){});
                    window.open('documentos_convenio.php','_self');
                });
            }
            else
            {
                var MSG = '';
                //mensajes que indican el estatus del recibo
                if(results == "FAIL1")
                {
                    MSG="El número de Recibo no esta correctamente registrado";
                    mensajes(MSG);
                }
                else if(results == "FAIL2")
                {
                    MSG="No ha pagado completamente el Estudio Socioeconomico.";
                    mensajes(MSG);
                }
                else if(results == "FAIL3")
                {
                    MSG="Este número de recibo no Pertenece al pago del Estudio Socioeconómico.";
                    mensajes(MSG);
                }
                else if(results == "FAIL4")
                {
                    MSG="Lo sentimos el tiempo para Solicitar Beca se ha Terminado.";
                    mensajes(MSG);
                }
                //mensajes que indican el estatus de la beca
                else if(results == "MSJ1")
                {
                    MSG="¡La Solicitud de Beca se realizó con éxito!, Favor de estar atento al sistema, en breve se enviará el resolutivo de beca para concluir con el proceso.";
                    mensajes(MSG);
                }
                else if(results == "MSJ2")
                {
                    MSG="La Solicitud de Beca ha sido Rechazada, acude a Control Económico donde te indicaran el motivo.";
                    mensajes(MSG);
                }
                else if(results == "MSJ3")
                {
                    MSG="La Carta de Aceptación se encuentra en revisión, mantente atento al sistema en espera de la resolución.";
                    mensajes(MSG);
                }
                else if(results == "MSJ4")
                {
                    MSG="Tu Beca ha sido aprobada y aplicada, acude a Caja a realizar el ajuste en tus pagos.";
                    mensajes(MSG);
                }
                else if(results == "MSJ5")
                {
                    MSG="Hubo un problema durante el proceso de Solicitud, favor de comunicarse al Departamento de Programación ext. 734.";
                    mensajes(MSG);
                }
            }
        });
    });

    //Mensaje de falla en el sistema
    function mensajes(mensaje){
        $('#done').html(mensaje);
        $('#loginDetails').hide();
        $('#iniciar').hide();
        $("#login_frm").slideUp(500);
        $('#loading').hide();
        window.setTimeout("recarga()",5000);
    }

    //Funcion que recarga el sistema, cierra sesion
    function recarga(){
        //window.open('index.php','_self');
        url = "logout.php";
        location.reload();
    }
    </script>
</head>

<body>
<?php include "header.php"; ?>
<br /><br /><br />
<form method="post" name="form1" id="form1">
<table border="0" align="center" cellpadding="15" cellspacing="0">
    <tr>
        <td>
            <div id="iniciar" align="center" style="font-size:30px; color:#FFFFFF">Iniciar Sesi&oacute;n </div>
        </td>
    </tr>
    <tr>
     	<td>
            <div align="center" id="login_frm" style="display:block; visibility:visible">
                <table width="99%" align='center' cellpadding='8' cellspacing='0' style="border:none">
                    <tr>
                        <td colspan="2" align='right' bgcolor="#EEEFF4" style="border-left:#D6D6D6 solid 1px; border-right:#D6D6D6 solid 1px; border-top:#D6D6D6 solid 1px; border-bottom:none; opacity:0.4;filter:alpha(opacity=40);">
                            <div align="left"><span class="style9">Login Solicitud de Beca</span></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align='right' style="border-top:none; border-left:#D6D6D6 solid 1px; border-right:#D6D6D6 solid 1px; border-bottom:#AEAEAE solid 1px">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <table border="0" cellspacing="0" cellpadding="3">
                                            <tr>
                                                <td colspan="2">
                                                    <span style="color:#CCCCCC">INGRESA EL NUMERO DE RECIBO DE TU ESTUDIO SOCIOECONOMICO Y DA CLIC EN EL BOTON INICIAR: </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input type="text" name="clave" id="clave" style="width:230px; border:#CAD4E7 solid 1px; padding:5px; background-color:#FBFCFD; color:#999999; font-size:24px; font-weight:bold; text-align: center;" />
                                                </td>
                                                <td>
                                                    <input type="button" name="login_btn" id="login_btn" value="Iniciar" style="padding:15px"/>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    <span style="color:#CCCCCC">Fecha límite de solicitud y renovación de beca: 8 de Septiembre de 2018 </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <div id="loading" style="background-color:#FFFFFF;">
                <table width="100%" border="0" cellspacing="0" cellpadding="5">
                    <tr>
                        <td width="19%"><img src="img/loading.gif" /></td>
                        <td width="81%">Procesando</td>
                    </tr>
                </table>
            </div>
            <div id="loginDetails" style="color:#00FFFF"></div>
            <div id="done" align="center" style="font-size:30px; color:#FFFFFF"></div>
        </td>
    </tr>
</table>
</form>

<script src="tools/jquery-1.4.4.js"></script>
<script type="text/javascript" src="tools/phpsimilar/phpjs.js"></script>
<script type="text/javascript" src="tools/ieversion/ieversion.js"></script>
<script src="tools/filterinput/filterinput.js"></script>
<img src="img/programacion_logo.png" style="position: absolute; bottom:0; right: 0;" align="right" width="100px" height="100px"></img>

</body>
</html>
