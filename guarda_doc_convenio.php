<?php
session_start();
include "include/session_check.php";
require_once("../conexiones/conecta_alu.php");
include_once "../classes/mssql_data.php";
include_once("../classes/db_params.php");

### MSSQL CONNECTION
$msSql = new  mssqlCnx($pc_server_address,$pc_dbuser,$pc_dbpasswd,$pc_db);
$con = $msSql->Open();   

### MSSQL CONNECTION SIIE
$siieSql= new  mssqlCnx($siie_server_address,$siie_dbuser,$siie_dbpasswd,$siie_db);
$siieCon=$siieSql->Open();

$idpc   = $_SESSION["alumno"];##Pople Id del Alumno
$folio  = $_SESSION["recibo"];##Numero de recibo del pago de Estudio Socioeconomico
$matricula  = $idpc; ##Se tomara el id de power campus como la matricula adjunta al documento

##LLegada de los documentos requeridos para la renovacion

#foto o credencial
$nombrexmlfoto = $_FILES['renuevafoto']['name'];
$nombre_tmpfoto = $_FILES['renuevafoto']['tmp_name'];
$tipoxml = $_FILES['renuevafoto']['type'];
$tamanoxml = $_FILES['renuevafoto']['size'];
list($n, $e) = explode(".", $nombrexmlfoto);
$nombrefoto = $matricula . "FOTO." . $e;

#boleta
$nombrexmlboleta = $_FILES['renuebaboleta']['name'];
$nombre_tmpboleta = $_FILES['renuebaboleta']['tmp_name'];
$tipoxml = $_FILES['renuebaboleta']['type'];
$tamanoxml = $_FILES['renuebaboleta']['size'];
list($n, $e) = explode(".", $nombrexmlboleta);
$nombreboleta = $matricula . "BOLETA." . $e;

#ingresos
$nombrexmlingresos = $_FILES['renuevaingresos']['name'];
$nombre_tmpingresos = $_FILES['renuevaingresos']['tmp_name'];
$tipoxml = $_FILES['renuevaingresos']['type'];
$tamanoxml = $_FILES['renuevaingresos']['size'];
list($n, $e) = explode(".", $nombrexmlingresos);
$nombreingresos = $matricula . "INGRESOS." . $e;


##Se desgloza los documentos y se corre el proceso de guadado de los mismos
if (($_FILES['renuevafoto']['error'] > 0) && ($_FILES['renuebaboleta']['error'] > 0) && ($_FILES['renuevaingresos']['error'] > 0))
{
    $MSG = 'failed';
} 
else 
{
    #borra los archivos que existan con el mismo nombre
    array_map('unlink', glob("documentos_becas/" . $nombrefoto));
    array_map('unlink', glob("documentos_becas/" . $nombreboleta));
    array_map('unlink', glob("documentos_becas/" . $nombreingresos));
    
    #carga archivos en la carpeta
    move_uploaded_file($nombre_tmpfoto, "documentos_becas/" . $nombrefoto);
    move_uploaded_file($nombre_tmpboleta, "documentos_becas/" . $nombreboleta);
    move_uploaded_file($nombre_tmpingresos, "documentos_becas/" . $nombreingresos);
    $MSG = 'success';
}

function verifica($nd,$recibo){
    global $siieCon;
    $query = "SELECT folio FROM beca_documentos WHERE folio = '$recibo' AND doc = '$nd';";
    $cmd = new mssqlCommand($query,$siieCon);
    $info = $cmd->ExecuteReader(true);
    $registro = $info[0]['folio'];
    if($registro != ''){
        $esta = 'si';
    }else{
        $esta = 'no';
    }
    return $esta;
}

function insert_update($insert){
    global $siieCon;
    $cmd= new mssqlCommand($insert,$siieCon);
    $dato=$cmd->ExecuteNonQuery(true);
    return $dato;
}

if($MSG == 'success')
{
    //Fotografia
    if($nombrexmlfoto != '')
    {
        $existe = verifica('d1',$folio);
        if($existe == 'si'){
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombrefoto' WHERE folio = '$folio' AND doc = 'd1'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','Fotografia','https://aspaa.uvp.mx/becas/documentos_becas/$nombrefoto','0','','d1');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status1 = 1;}else{$status1 = 0;}
    }
    
    //Boleta
    if($nombrexmlboleta != '')
    {
        $existe = verifica('d3',$folio);
        if($existe == 'si'){
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombreboleta' WHERE folio = '$folio' AND doc = 'd3'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','Boleta','https://aspaa.uvp.mx/becas/documentos_becas/$nombreboleta','0','','d3');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status3 = 1;}else{$status3 = 0;}
    }
    
    //Ingresos
    if($nombrexmlingresos != '')
    {
        $existe = verifica('d4',$folio);
        if($existe == 'si'){
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombreingresos' WHERE folio = '$folio' AND doc = 'd4'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','Ingresos', 'https://aspaa.uvp.mx/becas/documentos_becas/$nombreingresos','0','','d4');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status4 = 1;}else{$status4 = 0;}
    }
    
    if(($status1 == 1) || ($status3 == 1) || ($status4 == 1))
    {
        $insert ="UPDATE beca_generales SET estatus_documentos = 1 WHERE folio = '$folio'";
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){
            $status = 'success';
        }else{
            $status = 'failed';
        }
    }
    else
    {
            $status = 'failed1';
    }
}
else
{
    $status = 'failed';
}
echo utf8_encode($status);
?>