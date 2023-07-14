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
$nombrexmlfoto = $_FILES['foto']['name'];
$nombre_tmpfoto = $_FILES['foto']['tmp_name'];
$tipoxml = $_FILES['foto']['type'];
$tamanoxml = $_FILES['foto']['size'];
list($n, $e) = explode(".", $nombrexmlfoto);
$nombrefoto = $matricula . "FOTO." . $e;

#mapa
$nombrexmlmapa = $_FILES['mapa']['name'];
$nombre_tmpmapa = $_FILES['mapa']['tmp_name'];
$tipoxml = $_FILES['mapa']['type'];
$tamanoxml = $_FILES['mapa']['size'];
list($n, $e) = explode(".", $nombrexmlmapa);
$nombremapa = $matricula . "MAPA." . $e;

#boleta
$nombrexmlboleta = $_FILES['boleta']['name'];
$nombre_tmpboleta = $_FILES['boleta']['tmp_name'];
$tipoxml = $_FILES['boleta']['type'];
$tamanoxml = $_FILES['boleta']['size'];
list($n, $e) = explode(".", $nombrexmlboleta);
$nombreboleta = $matricula . "BOLETA." . $e;

#ingresos
$nombrexmlingresos = $_FILES['ingresos']['name'];
$nombre_tmpingresos = $_FILES['ingresos']['tmp_name'];
$tipoxml = $_FILES['ingresos']['type'];
$tamanoxml = $_FILES['ingresos']['size'];
list($n, $e) = explode(".", $nombrexmlingresos);
$nombreingresos = $matricula . "INGRESOS." . $e;

#inscripcion
$nombrexmlinscripcion = $_FILES['inscripcion']['name'];
$nombre_tmpinscripcion = $_FILES['inscripcion']['tmp_name'];
$tipoxml = $_FILES['inscripcion']['type'];
$tamanoxml = $_FILES['inscripcion']['size'];
list($n, $e) = explode(".", $nombrexmlinscripcion);
$nombreinscripcion = $matricula . "INSCRIPCION." . $e;

#domicilio
$nombrexmldomicilio = $_FILES['domicilio']['name'];
$nombre_tmpdomicilio = $_FILES['domicilio']['tmp_name'];
$tipoxml = $_FILES['domicilio']['type'];
$tamanoxml = $_FILES['domicilio']['size'];
list($n, $e) = explode(".", $nombrexmldomicilio);
$nombredomicilio = $matricula . "DOMICILIO." . $e;

#curp
$nombrexmlcurp = $_FILES['curp']['name'];
$nombre_tmpcurp = $_FILES['curp']['tmp_name'];
$tipoxml = $_FILES['curp']['type'];
$tamanoxml = $_FILES['curp']['size'];
list($n, $e) = explode(".", $nombrexmlcurp);
$nombrecurp = $matricula . "CURP." . $e;

#resolutivo
$nombrexmlresolutivo = $_FILES['resolutivo']['name'];
$nombre_tmpresolutivo = $_FILES['resolutivo']['tmp_name'];
$tipoxml = $_FILES['resolutivo']['type'];
$tamanoxml = $_FILES['resolutivo']['size'];
list($n, $e) = explode(".", $nombrexmlresolutivo);
$nombreresolutivo = $matricula . "RESOLUTIVO." . $e;

##Se desgloza los documentos y se corre el proceso de guadado de los mismos
if (($_FILES['foto']['error'] > 0) && ($_FILES['mapa']['error'] > 0)
    && ($_FILES['boleta']['error'] > 0) && ($_FILES['ingresos']['error'] > 0)
    && ($_FILES['inscripcion']['error'] > 0) && ($_FILES['domicilio']['error'] > 0)
    && ($_FILES['curp']['error'] > 0) && ($_FILES['resolutivo']['error'] > 0))
{
    $MSG = 'failed';
} 
else 
{
    #borra los archivos que existan con el mismo nombre
    array_map('unlink', glob("documentos_becas/" . $nombrefoto));
    array_map('unlink', glob("documentos_becas/" . $nombremapa));
    array_map('unlink', glob("documentos_becas/" . $nombreboleta));
    array_map('unlink', glob("documentos_becas/" . $nombreingresos));
    array_map('unlink', glob("documentos_becas/" . $nombreinscripcion));
    array_map('unlink', glob("documentos_becas/" . $nombredomicilio));
    array_map('unlink', glob("documentos_becas/" . $nombrecurp));
    array_map('unlink', glob("documentos_becas/" . $nombreresolutivo));
    
    #carga archivos en la carpeta
    move_uploaded_file($nombre_tmpfoto, "documentos_becas/" . $nombrefoto);
    move_uploaded_file($nombre_tmpmapa, "documentos_becas/" . $nombremapa);
    move_uploaded_file($nombre_tmpboleta, "documentos_becas/" . $nombreboleta);
    move_uploaded_file($nombre_tmpingresos, "documentos_becas/" . $nombreingresos);
    move_uploaded_file($nombre_tmpinscripcion, "documentos_becas/" . $nombreinscripcion);
    move_uploaded_file($nombre_tmpdomicilio, "documentos_becas/" . $nombredomicilio);
    move_uploaded_file($nombre_tmpcurp, "documentos_becas/" . $nombrecurp);
    move_uploaded_file($nombre_tmpresolutivo, "documentos_becas/" . $nombreresolutivo);
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
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, 
                    localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombrefoto' WHERE folio = '$folio' AND doc = 'd1'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','Fotografia', 
                    'https://aspaa.uvp.mx/becas/documentos_becas/$nombrefoto','0','','d1');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status1 = 1;}else{$status1 = 0;}
    }
    
    //Mapa
    if($nombrexmlmapa != '')
    {
        $existe = verifica('d2',$folio);
        if($existe == 'si'){
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, 
                    localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombremapa' WHERE folio = '$folio' AND doc = 'd2'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','Croquis',
                    'https://aspaa.uvp.mx/becas/documentos_becas/$nombremapa','0','','d2');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status2 = 1;}else{$status2 = 0;}
    }
    
    //Boleta
    if($nombrexmlboleta != '')
    {
        $existe = verifica('d3',$folio);
        if($existe == 'si'){
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, 
                    localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombreboleta' WHERE folio = '$folio' AND doc = 'd3'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','Boleta', 
                    'https://aspaa.uvp.mx/becas/documentos_becas/$nombreboleta','0','','d3');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status3 = 1;}else{$status3 = 0;}
    }
    
    //Ingresos
    if($nombrexmlingresos != '')
    {
        $existe = verifica('d4',$folio);
        if($existe == 'si'){
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, 
                    localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombreingresos' WHERE folio = '$folio' AND doc = 'd4'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','Ingresos', 
                    'https://aspaa.uvp.mx/becas/documentos_becas/$nombreingresos','0','','d4');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status4 = 1;}else{$status4 = 0;}
    }
    
    //Inscripcion
    if($nombrexmlinscripcion != '')
    {
        $existe = verifica('d5',$folio);
        if($existe == 'si'){
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, 
                    localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombreinscripcion' WHERE folio = '$folio' AND doc = 'd5'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','Inscripcion', 
                    'https://aspaa.uvp.mx/becas/documentos_becas/$nombreinscripcion','0','','d5');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status5 = 1;}else{$status5 = 0;}
    }
    
    //Domicilio
    if($nombrexmldomicilio != '')
    {
        $existe = verifica('d6',$folio);
        if($existe == 'si'){
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, 
                    localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombredomicilio' WHERE folio = '$folio' AND doc = 'd6'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','Domicilio', 
                    'https://aspaa.uvp.mx/becas/documentos_becas/$nombredomicilio','0','','d6');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status6 = 1;}else{$status6 = 0;}
    }
    
    //CURP
    if($nombrexmlcurp != '')
    {
        $existe = verifica('d7',$folio);
        if($existe == 'si'){
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, 
                    localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombrecurp' WHERE folio = '$folio' AND doc = 'd7'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','CURP', 
                    'https://aspaa.uvp.mx/becas/documentos_becas/$nombrecurp','0','','d7');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status7 = 1;}else{$status7 = 0;}
    }
    
    //Resolutivo
    if($nombrexmlresolutivo != '')
    {
        $existe = verifica('d8',$folio);
        if($existe == 'si'){
            $insert="UPDATE beca_documentos SET peopleid = '$matricula', estatus = 0, 
                    localizacion = 'https://aspaa.uvp.mx/becas/documentos_becas/$nombreresolutivo' WHERE folio = '$folio' AND doc = 'd8'";
        }else{
            $insert="INSERT INTO beca_documentos VALUES('$folio','$matricula','Resolutivo', 
                    'https://aspaa.uvp.mx/becas/documentos_becas/$nombreresolutivo','0','','d8');";
        }
        $dato = insert_update($insert);
        if($dato != "bool(FALSE)"){$status8 = 1;}else{$status8 = 0;}
    }

    if(($status1 == 1) || ($status2 == 1) || ($status3 == 1) || ($status4 == 1) || ($status5 == 1) || ($status6 == 1) || ($status7 == 1) || ($status8 == 1))
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