<?php
session_start();
include "include/session_check.php";
include_once "classes/mysql_connection.php";
include_once "classes/mysqlCommand.php";
include_once "classes/mssql_data.php";
include_once("db_params.php");

##### CONFIG PARAMS ######
$username = $_SESSION["account"];

$mySql = new mysqlCnx($server_address, $dbuser, $dbpasswd, $db);
$con = $mySql->Open();

require('xajax/xajax.inc.php');
$xajax = new xajax();
$xajax->SetCharEncoding('UTF-8');
$xajax->decodeUTF8InputOn();

function tiempo() {
    $respuesta = new xajaxResponse('UTF-8');
    /*global $username;
    global $con;
    $command_txt = "SELECT count(*) num 
                    FROM aplicacion  
                    WHERE id_aplicante = '$username'";
    $cmd = new mysqlCommand($command_txt, $con);
    $hay = $cmd->ExecuteReader();
    if($hay[0]['num'] < 98){
        $respuesta->script("javascript:alert('FALTAN PREGUNTAS POR RESPONDER.');");
    }else{
        $respuesta->script("javascript:alert('GRACIAS POR RESPONDER ESTE TEST.');");*/
        $respuesta->script("javascript:window.open('resultados.php','_self');");
    //}
    return $respuesta;
}

function caso1($form, $preg) {
    global $username;
    global $con;
    //Se instancia un objeto para generar respuesta con ajax
    $respuesta = new xajaxResponse('UTF-8');
    //Se captura el contenido de los input
    $resp = end($form);
    
    //$hoy = date('Y-m-d');
    $command_txt = "SELECT count(*) num 
                    FROM aplicacion  
                    WHERE id_aplicante = '$username' 
                    AND pregunta_id = ".$preg."";
    $cmd = new mysqlCommand($command_txt, $con);
    $hay = $cmd->ExecuteReader();

    if ($hay[0]['num'] == 1) 
    {
        $txt_upd = "UPDATE aplicacion 
                        SET respuesta = '$resp' 
                        WHERE id_aplicante = '$username' 
                        AND pregunta_id=".$preg."";
        $cmdupd = new mysqlCommand($txt_upd, $con);
        $update = $cmdupd->ExecuteReader();
    } 
    else if ($hay[0]['num'] == 0) 
    {
        $txt_ins = "INSERT INTO aplicacion 
                        VALUES ('$username',NULL,".$preg.",NULL,'$resp')";
        $cmdins = new mysqlCommand($txt_ins, $con);
        $insert = $cmdins->ExecuteReader();
    }
    return $respuesta;
}

//Se registran las funciones
$xajax->registerFunction("caso1");
$xajax->registerFunction("tiempo");
//Se procesa cualquier petición
$xajax->processRequests();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
        <?php $xajax->printJavascript("xajax/"); ?>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF8"/>
        <title>UVP</title>
        <link href="include/stylesheet.css" rel="stylesheet" type="text/css"/>
        <script type="text/javascript" src="tools/jquery-1.4.4.js"></script>
        <script type="text/javascript" src="tools/ieversion/ieversion.js"></script>
        <script type="text/javascript" src="tools/phpsimilar/phpjs.js"></script>
        <script type="text/javascript" src="tools/filterinput/filterinput.js"></script>
</head>
<body>
<?php
include "header.php";
//$username = $_SESSION["account"];
?>
<br/>
    <center>
        <div id="maincontainer" style="width:900px">
            <div id="instructions" style="padding:15px; background:#EFF1F8; font-family:Arial, Helvetica, sans-serif; font-size:17px">
                TEST DE ORIENTACIÓN VOCACIONAL : <b>CHASIDE</b>
            </div>
            <div id="workarea" style="padding:15px; background:#FCFDFE">
                <table border="1" align="center" cellpadding="8" cellspacing="0" bordercolor="#F5F8FC">
                    <td colspan="s" bgcolor="#F7FAFD">
                        <form id="contenido" name="contenido">
                        <?php
                            $command_txt = "SELECT * from preguntas ";
                            $cmd = new mysqlCommand($command_txt, $con);
                            $preguntas = $cmd->ExecuteReader();
                            
                            foreach ($preguntas as $preg)
                            {
                                if ($preg["tipo_id"] == 1)//RADIO BUTTONS
                                {
                                    if ($preg["numero"] != NULL) 
                                    {
                                        echo"<p>";
                                        echo(utf8_encode($preg["numero"] . ".- " . $preg["descripcion"]));
                                        echo"</p>";
                                    }
                                    else 
                                    {
                                        echo"<br>";
                                        echo(utf8_encode($preg["descripcion"]));
                                        echo"<br>";
                                    }
                                        
                                    $txt_resp = "SELECT * from respuestas";
                                    $cmdresp = new mysqlCommand($txt_resp, $con);
                                    $respuestas = $cmdresp->ExecuteReader();
                                    $pregunta = $preg['numero'];

                                    $txt2 = "SELECT respuesta 
                                            FROM aplicacion  
                                            WHERE id_aplicante = '$username'
                                            AND pregunta_id = '$pregunta'";
                                    $cmd2 = new mysqlCommand($txt2, $con);
                                    $previo = $cmd2->ExecuteReader();

                                    if ($previo[0]['respuesta'] != NULL && $preg["numero"] != NULL) 
                                    {
                                        foreach ($respuestas as $resp) 
                                        {
                                            if ($resp['identidicador'] == $previo[0]['respuesta']) 
                                            {
                                                $checked = "checked";
                        ?>
                                                <input type="radio" 
                                                        name="<?= $preg["numero"]?>" 
                                                        onclick="xajax_caso1(xajax.getFormValues('contenido',1,'<?= $preg["numero"] ?>'),<?= $preg["numero"] ?>)" 
                                                        value="<?= $resp["identidicador"] ?>"
                                                        style = "width: 15px; height: 15px; margin-left: 30px;"
                                                        <?= $checked ?>>
                                                        <label style="font-size: 18px;"><?= utf8_encode($resp["nombre"]) ?></label>
                                                </input>
                                                
                        <?php
                                            } 
                                            else 
                                            {
                        ?>
                                                <input type="radio" 
                                                        name="<?= $preg["numero"]?>" 
                                                        onclick="xajax_caso1(xajax.getFormValues('contenido', 1, '<?= $preg["numero"] ?>'),<?= $preg["numero"] ?>)" 
                                                        value="<?= $resp["identidicador"] ?>"
                                                        style = "width: 15px; height: 15px; margin-left: 30px;">
                                                    <label style="font-size: 18px;"><?= utf8_encode($resp["nombre"]) ?></label>
                                                </input>
                                                
                        <?php
                                            }
                                        }
                                    } 
                                    else 
                                    {
                                        foreach ($respuestas as $resp) 
                                        {
                                            if ($preg["numero"] != NULL) 
                                            {
                        ?>
                                            <input type="radio" 
                                                    name="<?= $preg["numero"] ?>" 
                                                    onclick="xajax_caso1(xajax.getFormValues('contenido',1,'<?= $preg["numero"] ?>'),<?= $preg["numero"] ?>)" 
                                                    value="<?=$resp["identidicador"] ?>"
                                                    style = "width: 15px; height: 15px; margin-left: 30px;">
                                                    <label style="font-size: 18px;"><?= utf8_encode($resp["nombre"]) ?></label>
                                            </input>
                                            
                        <?php
                                            }
                                        }
                                    }
                                }
                            }
                        ?>
                        </form>
                        <div align="center">
                            <input style="font-size: 20px; font-weight: bold; padding-top: 10px; padding-bottom: 10px; padding-left: 20px;     padding-right: 20px;" type="button" value="Calificar" onclick="xajax_tiempo()"/>
                        </div>
                        </td>
                    </table>	
                </div>
            </div>
        </center>
    </body>
</html>