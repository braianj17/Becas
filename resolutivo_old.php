<?php
session_start();
include "include/session_check.php";
require_once("../conexiones/conecta_alu.php");
include_once "../classes/mssql_data.php";
include_once("../classes/db_params.php");

$idpc           = $_SESSION["account"];
$clave_recibo   = $_SESSION["recibo"];

### MSSQL CONNECTION
$msSql = new  mssqlCnx($pc_server_address,$pc_dbuser,$pc_dbpasswd,$pc_db);
$con = $msSql->Open();

### MSSQL CONNECTION SIIE
$siieSql= new  mssqlCnx($siie_server_address,$siie_dbuser,$siie_dbpasswd,$siie_db);
$siieCon=$siieSql->Open();

#####################################################################
##Generales de la Beca#
$query_beca     = "SELECT folio,estatus_doc,estatus,becatipo,becaotorgado FROM beca_beca WHERE folio = '$clave_recibo'";
$cmd            = new mssqlCommand($query_beca,$siieCon);
$info_beca      = $cmd->ExecuteReader();

$estado_beca    = trim($info_beca[0]['estatus']); ##Estado de Beca 0 en espera, 1 aceptado, 2 rechazado
$beca           = trim($info_beca[0]['becatipo']); ##Beca solicitada
$beca_otorgado  = trim($info_beca[0]['becaotorgado']); ##Porcentaje otorgado

##Porcentaje otorgado
$query = "SELECT ScholarshipLevel otorgado FROM ScholarshipOfferingLevel WHERE ScholarshipOfferingLevelId = '$beca_otorgado'";
$cmd = new mssqlCommand($query,$con);
$infopor = $cmd->ExecuteReader();
$porcentaje_otorgado = $infopor[0]['otorgado'];

#####################################################################
##Generales del Alumno
#####################################################################
##Obtener el Id de Power Campus, para poder obtener la información del alumno
$query = "SELECT PEOPLE_ID, TAX_ID, PersonId FROM PEOPLE WHERE PEOPLE_ID = '$idpc'";
$cmd = new mssqlCommand($query,$con);
$info = $cmd->ExecuteReader();
$personid = $info[0]['PersonId'];

###############################################################################################
##Generales de la Beca Otorgada
$query = "SELECT 
            s.ScholarshipApplicationId,
            s.AwardedPercentage porcentaje,
            s2.Name nombrebeca,
            s2.Description descripcion,
            c.ChargeCreditScholarshipId,
            c2.ChargeNumberSource,
            c3.CHARGE_CREDIT_CODE, 
            c3.CRG_CRD_DESC concepto

            FROM ScholarshipApplication s
            INNER JOIN ScholarshipOffering s2
            ON s2.ScholarshipOfferingId = s.ScholarshipOfferingId

            INNER JOIN ChargeCreditScholarship c
            ON s.ScholarshipApplicationId = c.ScholarshipApplicationId

            INNER JOIN ChargeCreditScholarshipDetail c2
            ON c.ChargeCreditScholarshipId = c2.ChargeCreditScholarshipId

            INNER JOIN CHARGECREDIT c3
            ON c2.ChargeNumberSource = c3.CHARGECREDITNUMBER

            WHERE s.ScholarshipOfferingId = '$beca' 
            AND s.ScholarshipOfferingLevelId = '$beca_otorgado' 
            AND s.PersonId = '$personid'
            ORDER BY c2.ChargeNumberSource DESC";
$cmd = new mssqlCommand($query,$con);
$infores = $cmd->ExecuteReader();
$nombrebeca = $infores[0]['nombrebeca'];
$descripcion = $infores[0]['descripcion'];
$porcentaje = $infores[0]['porcentaje'];

?>
<html>
    <title>
        Becas
    </title>
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <style type="text/css">
	<!--
	body,td,th {
		font-family: Arial, Helvetica, sans-serif;
		font-size: 12px;
                border-collapse: collapse; 
                border: 1px solid black;
	}
        -->
        </style>
</head>

<body topmargin="0"> 
    <div style="text-align:right; padding:5px;">
        <button type="button" style="color: #174ae0; font-size: 12px; padding: 5px;" onclick="recarga()">
                Salir
        </button>
    </div>
    
    <div id="sep2" style="text-align:center; padding:15px;">
        <div style="background-color:#F7F7F7; text-align:center; padding:15px">
            <?php
                if($estado_beca == 2){
            ?>
                <div style="font-weight: bold; font-size: 20px;">LO SENTIMOS, TU BECA HA SIDO DENEGADA, PARA MAYOR INFORMACIÓN ACUDE A CONTROL ECONOMICO.</div>
            <?php
                }else{
            ?>
                <p><div style="font-weight: bold; font-size: 30px;">FELICIDADES!!, TU BECA HA SIDO ACEPTADA</div></p>
                
                <table border="0"  width="100%"> 
                    <tr>
                        <th>PORCENTAJE</th>
                        <th>BECA</th>
                        <th>DESCRIPCION</th>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-size: 18px;"><?=$porcentaje?>%</td>
                        <td style="text-align: center; font-size: 18px;"><?=$nombrebeca?></td>
                        <td style="font-size: 18px;"><?=$descripcion?></td>
                    </tr>
                </table>
                <p></p>
                <table border="0" width="100%"> 
                    <tr>
                        <th style="font-size: 20px;">APLICADO EN LOS SIGUIENTES CONCEPTOS</th>
                    </tr>
            <?php
                    $mes = array('ENE' => 'ENERO', 'FEB' => 'FEBRERO', 'MAR' => 'MARZO', 'ABR' => 'ABRIL', 'MAY' => 'MAYO', 'JUN' => 'JUNIO', 'JUL' => 'JULIO', 'AGO' => 'AGOSTO', 'SEP' => 'SEPTIEMBRE', 'OCT' => 'OCTUBRE', 'NOV' => 'NOVIEMBRE', 'DIC' => 'DICIEMBRE');
                    foreach ($infores as $b){
                        $c = explode(" ", $b['concepto']);
                        if($c[0] == 'CPUE' || $c[0] == 'CTEH')
                        {
                                if($c[1] == 'COLEGIATURA'){
                                        $mesc = $c[4];
                                        $col_mes = $mes[$mesc];
                                        $descripcion_cargo = $c[1].' '.$col_mes;
                                }
                                else if($c[1] == 'INSC-REINSC'){
                                        $descripcion_cargo = 'INSCRIPCION - REINSCRIPCION';
                                }
                        }else{
                                $descripcion_cargo = $b['concepto'];
                        }
            ?>
                        <tr><td style="text-align: center; font-size: 18px;"><?=$descripcion_cargo?></td></tr>
            <?php            
                    }
            ?>
            </table>
            <?php
                }
            ?>
            
        </div>
    </div>
</body>
<script type="text/javascript" src="../jquery/jquery-3.1.0.min.js"></script>
<script>
    function recarga(){
    //location.reload();
    //url = "index.php?cierra=1";
    url = "logout.php";
    $(location).attr('href',url);
}
</script>
</html>