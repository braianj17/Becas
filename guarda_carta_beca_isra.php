<?php
session_start();
include "include/session_check.php";
require_once("../conexiones/conecta_alu.php");
include_once "../classes/mssql_data.php";
include_once("../classes/db_params.php");

### MSSQL CONNECTION SIIE
$siieSql= new  mssqlCnx($siie_server_address,$siie_dbuser,$siie_dbpasswd,$siie_db);
$siieCon=$siieSql->Open();

### MSSQL CONNECTION POWER CAMPUS
$msSql = new  mssqlCnx($pc_server_address,$pc_dbuser,$pc_dbpasswd,$pc_db);
$con = $msSql->Open();

$idpc   = $_SESSION["alumno"];##Pople Id del Alumno
$folio  = $_SESSION["recibo"];##Numero de recibo del pago de Estudio Socioeconomico
$matricula  = $idpc; ##Se tomara el id de power campus como la matricula adjunta al documento 

#carta
$nombrexmlcarta = 'carta.uvp.123.word';//$_FILES['carta']['name'];
$nombre_tmpcarta = $_FILES['carta']['tmp_name'];
$tipoxml = $_FILES['carta']['type'];
$tamanoxml = $_FILES['carta']['size'];
$partdoc = explode(".", $nombrexmlcarta);
//print_r($partdoc);
if (in_array("PDF", $partdoc)){
    $extension = 'pdf';
}else if (in_array("pdf", $partdoc)){
    $extension = 'pdf';
}else if (in_array("JPG", $partdoc)){
    $extension = 'jpg';
}else if (in_array("jpg", $partdoc)){
    $extension = 'jpg';
}else{
    $extension = 'fail';
}
//echo $extension;
$nombrecarta = $matricula . "CARTA." . $extension;
//echo '<br>';
if($extension == 'fail'){
    $status = 'failext';
    //echo 'no se permite la extension';
}
else{
    //echo 'se procede con el ingreso de la carta';
    if (1 == 1) {
        if ($_FILES['carta']['error'] > 0 ) {
            $MSG = 'failed';
        } else {
            #borra los archivos que existan con el mismo nombre
            array_map('unlink', glob("cartas_aceptacion/" . $nombrecarta));

            #carga archivos en la carpeta
            move_uploaded_file($nombre_tmpcarta, "cartas_aceptacion/" . $nombrecarta);

            $MSG = 'success';
        }
    }

    function verifica($recibo){
        global $siieCon;
        $query = "SELECT folio FROM beca_aceptar WHERE folio = '$recibo';";
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
        //carta de aceptacion
        if($nombrexmlcarta != ''){
            $existe = verifica($folio);
            if($existe == 'si'){
                $insert="UPDATE beca_aceptar SET estatus = 0, url = 'https://aspaa.uvp.mx/becas/cartas_aceptacion/$nombrecarta' WHERE folio = '$folio';";
            }else{
                $insert="INSERT INTO beca_aceptar VALUES('$folio','$matricula','Carta','https://aspaa.uvp.mx/becas/cartas_aceptacion/$nombrecarta','0','');";
            }
            $dato = insert_update($insert);
            if($dato != "bool(FALSE)"){$status = 'success';}else{$status = 'failed';}
        }
    }
    else
    {
        $status = 'failed';
    }
}

echo utf8_encode($status);
?>