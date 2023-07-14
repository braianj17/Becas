<?php
session_start();
require_once("../conexiones/conecta_alu.php");
include_once "../classes/mssql_data.php";
include_once("../classes/db_params.php");

$idpc   = $_SESSION["alumno"];##People Id del alumno 9 digitos
$folio  = $_SESSION["recibo"];##Folio, numero de recibo

### MSSQL CONNECTION Power Campus
$msSql= new  mssqlCnx($pc_server_address,$pc_dbuser,$pc_dbpasswd,$pc_db); ##Produccion
$conPc=$msSql->Open();

if(isset($_GET['accion']))
{
    ##Dato enviado indica la accion a realizar
    $accion = $_GET['accion'];

    switch ($accion)
    {
        case 'becas_seleccion':
            ##Id del periodo para la seleccion de Becas
            $idperiodo = $_GET['idperiodo'];

            ##Promedio del alumno lo cual determina que becas se mostraran
            $promedio = $_GET['promedio'];
            
            if($promedio < 8.49){
                $solobecas = "AND ScholarshipType IN ('A.P.I.T.','DEPARTAMEN','APOYOTRABA','TRABCAT','PROMOCIONA')";
            }else{
                $solobecas = '';
            }
                        ##Se obtienen las becas del periodo en el cual se esta solicitando beca
            $query_becas ="SELECT ScholarshipOfferingId idbeca, ScholarshipType tipobeca, Name nombrebeca, Description descripcionbeca
                            FROM ScholarshipOffering 
                            WHERE SessionPeriodId = '$idperiodo'
                            AND ScholarshipType NOT IN ('ESPECIAL','DESCUENTO','AJUSTE','DEPOCULT') $solobecas;";
            $cmd = new mssqlCommand($query_becas,$conPc);
            $databecas = $cmd->ExecuteReader(true);
            $cmbbeca= '<label for="seleccionabeca">Seleccione una Beca</label>';
            $cmbbeca.= '<select class="form-control" id="beca_solicitada" onchange="becasolicitada()">';
            $cmbbeca.='<option value="0" selected="selected">Seleccionar Beca...</option>';
            foreach($databecas as $bs)
            {
                $cmbbeca.='<option id="'.$bs["idbeca"].'" value="'.$bs["idbeca"].'">'.$bs["nombrebeca"].': '. $bs["descripcionbeca"].'</option>';
            }
            $cmbbeca.='</select>';
            if ($databecas != NULL)
            echo($cmbbeca);
            break;

        case 'porcentaje_seleccion':
            ##Datos enviados para obtener el procentaje de cada una de las becas
            $idbeca = $_GET['idbeca'];

            $query = "SELECT 
                    ScholarshipOfferingLevelId oferta,
                    ScholarshipOfferingId beca,
                    ScholarshipLevel niveles,
                    Percentage porcentaje,
                    NumScholarshipAvailable disponibles,
                    MaxAmount
                    FROM ScholarshipOfferingLevel 
                    WHERE ScholarshipOfferingId = $idbeca";
            $cmd = new mssqlCommand($query,$conPc);
            $dato = $cmd->ExecuteReader(true);

            $cmbbecanivel= '<label for="seleccionaporcentaje">Porcentaje Solicitado</label>';
            $cmbbecanivel.= '<select class="form-control" id="porcentaje_solicitado">';
            $cmbbecanivel.='<option value="0">Seleccione Porcentaje de Beca</option>';
            foreach($dato as $nibec)
            {
                if($nibec["disponibles"] > 0 && $nibec["porcentaje"] != '')
                {
                    $cmbbecanivel.='<option value="'.$nibec['oferta'].'">'.$nibec['niveles'].'%</option>';
                }
            }
            $cmbbecanivel.='</select>';

            if ($dato != NULL)
            echo($cmbbecanivel);
            break;
    }
}
else if(isset($_POST['accion']))
{
    ##Dato enviado indica la accion a realizar
    $accion = $_POST['accion'];
    switch ($accion)
    {
        case 'ver_tipo':
            ##Id de la beca a solicitar
            $becasolicitada = $_POST['beca'];
            
            ##Id del id del porcentaje a solicitar
            $solicitado = $_POST['solicitado'];
            
            ##Se obtiene el tipo de beca solicitado
            $query_tipob ="SELECT ScholarshipType tipo FROM ScholarshipOffering WHERE ScholarshipOfferingId = '$becasolicitada';";
            $cmd = new mssqlCommand($query_tipob,$conPc);
            $datatipo = $cmd->ExecuteReader(true);
            $tipobeca = $datatipo[0]['tipo'];
            
            ##Se obtiene el % de beca solicitado
            $query_porb ="SELECT Percentage porcentaje, ScholarshipLevel nivelbeca FROM ScholarshipOfferingLevel
                    WHERE ScholarshipOfferingLevelId = '$solicitado';";
            $cmd = new mssqlCommand($query_porb,$conPc);
            $datapor = $cmd->ExecuteReader(true);
            $porcenbeca = $datapor[0]['porcentaje'];
            $nivelbeca  = $datapor[0]['nivelbeca'];
            
            ##Se verifica el tipo de Beca
            ##Si la beca es diferente a Convenio EMPRESARIA y Rendimiento ACADEMICA se guarda el registro de Beca
            ##De lo contrario se envia el tipo de Beca
            if($tipobeca != 'EMPRESARIA' && $tipobeca != 'ACADEMICA')
            {
                ##Se guarda el registro directamente
                $status = 'directo';
            }
            else
            {
                ##Se envia la respues del tipo de beca
                $status = 'procesar';
            }
            $response = array( $tipobeca, $status, $porcenbeca, $nivelbeca);
            echo json_encode($response);
            break;
    }
}
?>