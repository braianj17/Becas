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

##Indicaciones para la Renovacion de Beca ##################################################################################################################################
##Se realizara la renovación siempre y cuando se cumplan las siguientes condiciones:                                                                                     ###
##1.- El alumno tenga un promedio mayor a 8.5 en el Periodo Anterior                                                                                                     ###
##2.- Si el alumno renovara Beca de Convenio Empresarial, debera de subir el documento que acredite dicho convenio, Credencial de la Empresa                             ###
                                                                                                                                                                         ###
###En este sitio el alumno realizara la Renovación de su Beca, se tomara en cuenta el folio del alumno para obtener lo siguiente:                                        ###
##Beca anteriormente otorgada                                                                                                                                            ###
##Porcentaje otorgado                                                                                                                                                    ###
##Esto es la Beca que se renovara, mediante la busqueda similar en el periodo a la cual se solicita Beca                                                                 ###
                                                                                                                                                                         ###
##La renovacion y asignación de Decuentos se vera hasta que en Control Económico se realice la verificacion del documento en caso de ser Convenio                        ###
##Cuando los documentos son aceptados, se procedera a realizar el Envio de la Carta Compromiso                                                                           ###
##La carta será revisada y autorizada, una vez hecho esto se procede a realizar la Aplicacion de la Beca a cargo de Control Economico                                    ###
############################################################################################################################################################################

##Obtener la informacion del Recibo del alumno de la solicitud de beca actual
$query = "SELECT ACADEMIC_YEAR, ACADEMIC_TERM, ACADEMIC_SESSION FROM CASHRECEIPT WHERE RECEIPT_NUMBER = '$folio';";
$cmd = new mssqlCommand($query,$con);
$info = $cmd->ExecuteReader(true);
$beca_year      = $info[0]['ACADEMIC_YEAR']; ##Datos academicos de la solicitud de Beca
$beca_periodo   = $info[0]['ACADEMIC_TERM']; ##Datos academicos de la solicitud de Beca
$beca_plantel   = $info[0]['ACADEMIC_SESSION']; ##Datos academicos de la solicitud de Beca

##Obtener el PersonId del alumno para poder seleccionar y encontrar la Beca otrogada en el periodo anterior, la cual sera renovada
$query_person = "SELECT PersonId FROM PEOPLE WHERE PEOPLE_ID = '$idpc'";
$cmd = new mssqlCommand($query_person,$con);
$infoP = $cmd->ExecuteReader(true);
$personid = $infoP[0]['PersonId']; ##PersonId del alumno, ligado a la beca otorgada

##Obtener las becas del Periodo Anterior, para identificar cual beca tiene asignada, esto mediante el año anterior al de la beca actual
$anio_beca      = $beca_year; ##año en el cual solicita Beca

##Se busca el año anterior solo si el periodo de beca es SemestreA o CuatrimesA
if($beca_periodo == 'SEMESTREA' || $beca_periodo == 'CUATRIMESA'){
    $anio_anterior  = $anio_beca - 1; ##año anterior para buscar la beca en otro periodo
}else{
    $anio_anterior  = $anio_beca; ##si el periodo es diferente a SemestreA o CuatrimesA, el año sera igual al año de solicitud
}

$query_becas = "SELECT DISTINCT beca_tipo_otorgado FROM beca_generales WHERE academic_year = $anio_anterior ORDER BY beca_tipo_otorgado";
$cmd = new mssqlCommand($query_becas,$siieCon);
$infoB = $cmd->ExecuteReader();
##Se verifican las becas otorgadas en el periodo anterior, se identifica que beca tenia asignada en el periodo anterior
foreach ($infoB as $b)
{
    ##Se recorren las becas que fueron otorgadas en el periodo, estas se revisan con el alumno para indicar si el alumno renovara beca
    $beca_torgada   = $b['beca_tipo_otorgado'];
    $query_scholar  = "SELECT PersonId, s.ScholarshipOfferingId beca, s.ScholarshipOfferingLevelId otorgado, s.AwardedPercentage porcentaje, b.ScholarshipType tipo, 
                        b.Name nombre, b.Description descripcion, b.SessionPeriodId idperiodo
                        FROM ScholarshipApplication s
                        INNER JOIN ScholarshipOffering b ON b.ScholarshipOfferingId = s.ScholarshipOfferingId
                        INNER JOIN ScholarshipOfferingLevel n ON n.ScholarshipOfferingLevelId = s.ScholarshipOfferingLevelId
                        WHERE PersonId = '$personid' AND s.ScholarshipOfferingId = '$beca_torgada' AND s.ScholarshipStatus = 'OTOR';";
    $cmd = new mssqlCommand($query_scholar,$con);
    $infoS = $cmd->ExecuteReader(true);
    $banterior = $infoS[0]['PersonId']; ##Indica si el alumno tiene esa beca otorgada
    if($banterior != '')
    {
        $idperido_anterior      = $infoS[0]['idperiodo'];   ##Id del periodo de la beca solicitada anteriormente, con esto se obtendran las calificaciones del periodo
        $beca_anterior          = $infoS[0]['beca'];        ##Id de la Beca anterior
        $otorgado_anterior      = $infoS[0]['otorgado'];    ##Id del porcentaje otorgado
        $porcentaje_anterior    = $infoS[0]['porcentaje'];  ##Porcentaje otorgado
        $tipo_anterior          = $infoS[0]['tipo'];        ##Tipo de Beca, este se utilizara para saber que beca asignar en la renovacion
        $nombre_anterior        = $infoS[0]['nombre'];      ##Nombre de la Beca anterior
        $descripcion_anterior   = $infoS[0]['descripcion']; ##Descripcion de la Beca
        break;
    }
}
$idperido_anterior.'-->'.$beca_anterior.'-->'.$otorgado_anterior.'-->'.$porcentaje_anterior.'-->'.$tipo_anterior.'-->'.$nombre_anterior.'-->'.$descripcion_anterior;
##Obtener el id del periodo en la cual el alumno esta solicitando la Beca actualmente
$query_person = "SELECT SessionPeriodId FROM ACADEMICCALENDAR WHERE ACADEMIC_YEAR = '$beca_year' AND ACADEMIC_TERM = '$beca_periodo' AND ACADEMIC_SESSION = '$beca_plantel';";
$cmd = new mssqlCommand($query_person,$con);
$info = $cmd->ExecuteReader(true);
$idperiodo = $info[0]['SessionPeriodId']; ##Id del periodo

##Obtener la beca en el periodo en el cual el alumno esta realizando la solicitud, y que esta coincida con la beca que tenia aplicada y va a renovar
$query_bkactual = "SELECT s.ScholarshipOfferingId beca, s.ScholarshipType tipo, s.Name nombre, s.Description descripcion, n.ScholarshipOfferingLevelId idnivel, n.ScholarshipLevel nivel, n.Percentage porcentaje
FROM ScholarshipOffering s
LEFT JOIN ScholarshipOfferingLevel n
ON s.ScholarshipOfferingId = n.ScholarshipOfferingId
AND n.Percentage = '$porcentaje_anterior'
WHERE s.SessionPeriodId = '$idperiodo' 
AND s.ScholarshipType = '$tipo_anterior';";
$cmd = new mssqlCommand($query_bkactual,$con);
$info = $cmd->ExecuteReader(true);
//var_dump($info);
##Esta informacion sobre la beca a agregar es la Beca a Renovar y es la misma que se tiene en el periodo anterior
##Si esta beca o el porcentaje no se escuentra se debera mostrar un mensaje para poder agregarlo
##Se debe de mostrar la beca a renovar
$beca_renovar           = $info[0]['beca'];         ##Nueva Beca,id de la beca, esta beca es la similar a la que se tenia y la cual se renueva
$otorgado_renovar       = $info[0]['idnivel'];      ##Nuevo nivel, id del prorcentaje de beca a otorgar
$nivel_renovar          = $info[0]['nivel'];        ##Nuevo nivel a otorgar, nivel que se renovara
$porcentaje_renovar     = $info[0]['porcentaje'];   ##Nuevo porcentaje a renovar
$tipo_renovar           = $info[0]['tipo'];         ##Nuevo tipo de beca a renovar EMPRESARIA,ACADEMICA,DEPARTAMEN,PROMOCIONA,FAMILIAR
$nombre_renovar         = $info[0]['nombre'];       ##Nombre de la beca a renovar
$descripcion_renovar    = $info[0]['descripcion'];  ##Descripcion de la beca

##Se obtienen los datos del periodo academico en el cual solicito Beca anteriormente mediante el id de periodo $idperido_anterior
##Se verifica el promedio del alumno, con respecto al periodo de la beca solicitada anteriormente
$query_banterior = "SELECT ACADEMIC_YEAR, ACADEMIC_TERM, ACADEMIC_SESSION FROM ACADEMICCALENDAR WHERE SessionPeriodId = '$idperido_anterior';";
$cmd = new mssqlCommand($query_banterior,$con);
$info = $cmd->ExecuteReader(true);
$anio_promedio      = $info[0]['ACADEMIC_YEAR'];
$periodo_promedio   = $info[0]['ACADEMIC_TERM'];
$plantel_promedio   = $info[0]['ACADEMIC_SESSION'];

##Para verificar el promedio del alumno se debe de seleccionar su informacion academica del periodo en el que solito beca anteriormente
##Se debe de obtener el plan de estudio en ese periodo para poder obtener las materias del plan de estudios y con ello su calificacion
$query_plan = "SELECT MATRIC_YEAR, MATRIC_TERM, MATRIC_SESSION, CURRICULUM, PROGRAM
                FROM ACADEMIC 
                WHERE PEOPLE_ID = '$idpc' 
                AND ACADEMIC_YEAR = '$anio_promedio' 
                AND ACADEMIC_TERM = '$periodo_promedio' 
                AND ACADEMIC_SESSION = '$plantel_promedio'
                AND ACADEMIC_FLAG = 'Y';";
$cmd = new mssqlCommand($query_plan,$con);
$info = $cmd->ExecuteReader(true);
$anio_plan      = $info[0]['MATRIC_YEAR'];
$periodo_plan   = $info[0]['MATRIC_TERM'];
$plantel_plan   = $info[0]['MATRIC_SESSION'];
$carrera_plan   = $info[0]['CURRICULUM'];
##Se toma en cuenta el programa del alumno, si el programa es sistema abierto se tomara en cuenta la materia de lenguas
$programa_plan   = $info[0]['PROGRAM'];

##Se obtienen las materias del alumno en el periodo anterior de Beca
if(($carrera_plan == 'PLLEX') || ($carrera_plan == 'TLLEX') || ($programa_plan == 'ABIER') || ($carrera_plan == 'PMEI')){
    $noingles = "";
}
else{
    $noingles = "AND EVENT_ID NOT LIKE 'LE%'";
}

$query_materias = "SELECT EVENT_ID, FINAL_GRADE
                FROM TRANSCRIPTDETAIL
                WHERE PEOPLE_ID = '$idpc'
                AND ACADEMIC_YEAR = $anio_promedio
                AND ACADEMIC_TERM = '$periodo_promedio'
                AND ACADEMIC_SESSION = '$plantel_promedio'
                AND EVENT_ID <> '000000'
                $noingles";
$cmd = new mssqlCommand($query_materias,$con);
$infom = $cmd->ExecuteReader(true);
$totalmat = count($infom);
$cont = 0;
$suma_calif = 0;

if($totalmat != 0)
{
    foreach ($infom as $materia)
    {
        $evid = $materia['EVENT_ID'];
        $mat = mat_plan($carrera_plan, $evid);
        
        if($mat != '')
        {
            $suma_calif = $suma_calif + $materia['FINAL_GRADE'];
            $cont++;
        }
    }
    
    $promedio = $suma_calif / $cont;
    $promedio = truncateFloat($promedio,2);##Promedio del alumno obtenido por el sistema
}

##Excepciones al sistema, folios de alumno que tienen mal registro en sus materias haciendo que no puedan ingresar en el sistema
$recibos    = array(1408210,1409637,1409476,1408713,1405067,1409441,1405067,1409637,1409694,1409771,1417269,1409723,1418183,1417873,1408986,1409055, 1409627, 1424300,1421993,
    1425510,1418657,1410901,1425276,1426184,1417187,1426609,1426696,1414370,1409655,1425640,1412639,1418028,1414177,1414175, 1409641,1433726,1418684, 1439182, 1442494, 1441924,
    1418299,1455989,1477662,1476157,1469166,1479235,1477053,1485030,1477798);
$promedios  = array('9.2','9.0','9.75','9.5','8.5','9.75','8.5','9.0','9.5','9.3','8.5','10.0','8.5','8.5','9.0','10.0', '10.0', '8.5','9.4','8.5','9.1','8.5','9.0','8.8','8.5',
    '8.5','8.8','8.5','9.1','8.5','8.5','8.5','8.5','8.5','8.5','8.5','8.5', '8.5', '10.0', '10.0', '8.5','8.5','8.5','8.5','9','9','9','9','9','8.5','8.5','9.5');

##Se busca el numero de folio dentro de las excepciones
if (in_array($folio, $recibos)){
    $posrecibo = array_search($folio,$recibos); ##Se obtiene la posicion del folio dentro del arreglo de folios
    $promedio  = $promedios[$posrecibo]; ##Se obtiene el promedio dentro del arreglo de promedios en la posicion del folio
}

##Funcion para verificar la materia en el plan de estudiante donde se solicito beca anteriormente
function mat_plan($idcar, $idmat){
    global $con, $anio_plan, $periodo_plan;
    $query_mt ="SELECT EVENT_ID
                    FROM DEGREQEVENT 
                    WHERE CURRICULUM = '$idcar' 
                    AND EVENT_ID = '$idmat'
                    AND MATRIC_YEAR = $anio_plan
                    AND MATRIC_TERM = '$periodo_plan'
                    AND REQUIRED = 'Y'";
    $cmd = new mssqlCommand($query_mt,$con);
    $mt = $cmd->ExecuteReader();
    $mat = $mt[0]['EVENT_ID'];
    
    return $mat;
}

##Funcion para la visiualizacion del promedio
function truncateFloat($number, $digitos)
{
    $raiz = 10;
    $multiplicador = pow ($raiz,$digitos);
    $resultado = ((int)($number * $multiplicador)) / $multiplicador;
    return number_format($resultado, $digitos);
}

##Obtener el registro actual en Beca Generales, si el alumno no tiene registro, se envia al registro de generales
##Si ya tiene generales, se verifican los documentos
$query_registro = "SELECT people_id FROM beca_generales WHERE folio = $folio;";
$cmd = new mssqlCommand($query_registro,$siieCon);
$infoR = $cmd->ExecuteReader();
$registro = $infoR[0]['people_id']; ##Registro en el sistema de becas
if($registro == ''){
    $existe = 0; ##No existe
}else{
    $existe = 1; ##Si existe
}

##Con respecto al promedio obtenido del sistema, se verifica la condicion indicando que solo se renovara beca si el promedio es mayor o igual a 8.5
if($promedio >= 8.5)
{
    $paso1 = 1;##Promedio aceptado
    
    if($existe == 1)
    {
        ##Verificar el tipo de beca a renovar, si la Beca es de convenio empresarial EMPRESARIA de debe de egregar el documento correspondiente para su verificacion
        ##Verificar si el documento ha sido subido, revisado y aceptado, de lo contraio se enviara el mensaje segun el estado del documento y se enviara al sistema de documentos
        if($tipo_renovar == 'EMPRESARIA')
        {
            $tipobs = 2;
            ##Verificar el documento, se envia al sistema de ingreso de documentos para subir el documento, una vez que el documento ha sido aceptado se procede a lo siguiente
            $query_estadoc = "SELECT estatus FROM beca_documentos WHERE folio = '$folio' AND nombredoc = 'Fotografia';";
            $cmd = new mssqlCommand($query_estadoc,$siieCon);
            $infoD = $cmd->ExecuteReader();
            $credencial = $infoD[0]['estatus'];
            if($credencial == '')
            {
                ##Si el documento no existe se envia al sistema para guardar documentos
                //$renovacion_bk = 2;
                $paso2 = 0; ##No ha subido documentos, se envia al sistema de documentos
            }
            else
            {
                ##Si existe se verifica el estado del documento, 0 en espera, 1 aceptado, 2 rechazado
                if($credencial == 1)
                {
                    $paso2 = 1;##El documento ha sido aceptado 
                }
                else if($credencial == 2)
                {
                    ##Si el documento aun no ha sido aceptado no se puede proceder con el siguiente paso
                    ##Documento Rechazado, se envia al sistema de documentos para verificar el estado del documento y volver a subirlo
                    $paso2 = 0;
                }
                else if($credencial == 0)
                {
                    ##Si el documento aun no ha sido aceptado no se puede proceder con el siguiente paso
                    ##Documento en espera, solo mostrar el mensaje indicando el estado de revision del documento
                    $paso2 = 3;
                }
            }
        }
        else
        {
            ##Si la beca a renovar no es de Convenio se procede a siguiente paso que es la renovacion
            $paso2 = 1;
        }
    }
}
else
{
    ##El alumno no tiene el promedio, por lo tanto no se otorga la renovacion de Beca
    $paso1 = 0;
    $tipobs = 1;
}

if($paso1 == 0)
{
    //echo 'El promedio '.$promedio.' no es Apto para la Renovacion de Beca';
    $renovacion_bk = 0;
}
else
{
    if($paso2 == 0)
    {
        //echo 'Documento rechazado';
        $renovacion_bk = 2;
    }
    else if($paso2 == 1)
    {
        //echo 'Documento aceptado';
        $renovacion_bk = 3;
    }
    else if($paso2 == 3)
    {
        //echo 'documento en revision';
        $renovacion_bk = 3;
    }
}
?>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        
        <title>Solicitud de Beca</title>
    </head>
    <body>
        <div class="container obs" style="border: 1px solid violet; padding: 5px;">
            <div align="right" style="margin-top: 5px;">
                <button class="btn btn-sm btn-info" id="salir" onclick="recarga()">Salir</button>
            </div>
            
            <div class="form-signin">
                <fieldset>
                    <legend>Renovación de Beca</legend>
                    <div id="renovar" style="display: none;">
                        <p><legend>Tu promedio es de <?=$promedio?></legend></p>
                        <p><legend>La Beca a Renovar es <?=$nombre_renovar.': '.$descripcion_renovar?></legend></p>
                        <p><legend>El Porcentaje de Beca a Renovar es <?=$porcentaje_renovar?>%</legend></p>
                        <br>
                            <div style="display: none;" class="form-group" id="empresaconvenio">
                                <label for="seleccionaporcentaje">Nombre de la Empresa</label>
                                <input type="text" id="empresa" class="form-control" placeholder="Nombre de la Empresa">
                            </div>
                        <br>
                        <div align="right">
                            <button class="btn btn-lg btn-success" id="validar" onclick="guardageneralesbeca()">Continuar</button>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
        
        <!--Modal, ventana emergente para mostrar mensaje-->
        <div class="modal fade" id="mensajes" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div id="msj" class="modal-content">
                </div>
            </div>
        </div>

    </body>
    
    <script type="text/javascript">
    //Variables del sistema
    var promedio = '';
    var renovacion_bk = '';
    var existe = '';
    var tipobeca = ''; //$tipo_renovar == 'EMPRESARIA'
    
    $(document).ready(function () 
    {
        //Evitar que el modal se cierre al dar clic fuera de el
        $('#mensajes').modal({backdrop: 'static', keyboard: false});

        //Obtener el promedio del alumno y el estado de la renovacion
        promedio        = '<?=$promedio;?>';
        renovacion_bk   = '<?=$renovacion_bk;?>';
        existe          = '<?=$existe?>';
        tipobeca        = '<?=$tipo_renovar?>';
        
        if(renovacion_bk == 0)
        {
            //Si el alumno no cuenta con el promedio, no se renueva beca
            $("#msj").empty();
            $('#mensajes').modal('show');
            $("#msj").append('<div class="modal-header">'
                                        +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarsalir()">'
                                            +'<span aria-hidden="true">&times;</span>'
                                        +'</button>'
                                    +'</div>'
                                    +'<div class="modal-body">'
                                        +'<div class="alert alert-danger" role="alert">'
                                            +'<p>Lo sentimos, Tu promedio No cumple con los Requisitos de Renovación de Beca.</p>'
                                            +'<p>Promedio minimo de 8.5</p>'
                                        +'</div>'
                                    +'</div>');
        }    
        else if(existe == 0)
        {
            //Si el alumno no se ha registrado, se envia el mensaje y se muestra el formulario
            $("#msj").empty();
            $('#mensajes').modal('show');
            $("#msj").append('<div class="modal-header">'
                                    +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                        +'<span aria-hidden="true">&times;</span>'
                                    +'</button>'
                                +'</div>'
                                +'<div class="modal-body">'
                                    +'<div class="alert alert-success" role="alert">'
                                        +'<p>Tu promedio cumple con los Requisitos de Solicitud de Beca.</p>'
                                    +'</div>'
                                +'</div>');

            //Cuando el promedio es el correcto para proceder con la solicitud se pasa a la siguiente parte de la solicitud
            $("#renovar").css("display", "block");
            /*Mostrar el campo para agregar la empresa de convenio*/
            if(tipobeca == 'EMPRESARIA'){
                $("#empresaconvenio").css("display", "block");
            }
        }
        else if(renovacion_bk == 2)
        {
            //Si la documentacion del alumno tiene fallas, se muestra un mensaje
            $("#msj").empty();
            $('#mensajes').modal('show');
            $("#msj").append('<div class="modal-header">'
                                    +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="solicitud_p2()">'
                                        +'<span aria-hidden="true">&times;</span>'
                                    +'</button>'
                                +'</div>'
                                +'<div class="modal-body">'
                                    +'<div class="alert alert-warning" role="alert">'
                                        +'<p>Revisa tu documentación.</p>'
                                    +'</div>'
                                +'</div>');

        }
        else if(renovacion_bk == 3)
        {
            //Si el alumno esta en revision, se mostrara el mensaje
            $("#msj").empty();
            $('#mensajes').modal('show');
            $("#msj").append('<div class="modal-header">'
                                    +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                        +'<span aria-hidden="true">&times;</span>'
                                    +'</button>'
                                +'</div>'
                                +'<div class="modal-body">'
                                    +'<div class="alert alert-info" role="alert">'
                                        +'<p>Tu solicitud esta siendo revisada, mantente atento al sistema para otener el resolutivo.</p>'
                                    +'</div>'
                                +'</div>');
        }      
    });
        
        //Guardar el registro de solicitud de Beca diferente a Convenio y Rendimiento
        function guardageneralesbeca(){
            //Valores a guardar
            //Folio, people_id, beca_tipo, beca_solicitado, porcentaje_solicitado, beca_estatus (0 es espera, 1 aceptado, 2 rechazado), 
            //documentos (0 no solicita, 1 solicita), estatus_documentos (0 sin documentos, 1 en espera, 2 aceptados)
            //año, periodo, plantel, seccion_completa (c completo, 0 solicitud, 1 seccion 1, 2 seccion 2, etc), tipo_beca (1 academica, 2 empresarial, 0 otra)
            //Si la seccion es completo, se verifica si solicita documentos, si solicita documentos, estos se verifican para saber su estatus
        
            /*
            $beca_renovar           = $info[0]['beca'];         ##Nueva Beca,id de la beca, esta beca es la similar a la que se tenia y la cual se renueva
            $otorgado_renovar       = $info[0]['idnivel'];      ##Nuevo nivel, id del prorcentaje de beca a otorgar
            $nivel_renovar          = $info[0]['nivel'];        ##Nuevo nivel a otorgar, nivel que se renovara
            $porcentaje_renovar     = $info[0]['porcentaje'];   ##Nuevo porcentaje a renovar
            $tipo_renovar           = $info[0]['tipo'];         ##Nuevo tipo de beca a renovar EMPRESARIA,ACADEMICA,DEPARTAMEN,PROMOCIONA,FAMILIAR
            $nombre_renovar         = $info[0]['nombre'];       ##Nombre de la beca a renovar
            $descripcion_renovar    = $info[0]['descripcion'];  ##Descripcion de la beca
            */
        
            var folio                   = '<?=$folio?>';
            var peopleid                = '<?=$idpc?>';
            var beca_tipo               = '<?=$beca_renovar?>';
            var beca_solicitado         = '<?=$otorgado_renovar?>';
            var porcentaje_solicitado   = '<?=$porcentaje_renovar?>';
            var academic_year           = '<?=$beca_year?>';
            var academic_term           = '<?=$beca_periodo?>';
            var academic_session        = '<?=$beca_plantel?>';
            var tipobs                  = '<?=$tipo_renovar?>';
            if(tipobs == 'EMPRESARIA' || tipobs == 'ACADEMICA' ){
                var proceder                = 'procesar';
            }else{
                var proceder                = 'directo';
            }
            var nivelb                  = '<?=$nivel_renovar?>';
            var empresaconv             = $("#empresa").val();
            
            $.ajax({
                type: 'POST',
                data: 
                {
                    accion                  : 'generales',
                    folio                   : folio,
                    peopleid                : peopleid,
                    beca_tipo               : beca_tipo,
                    beca_solicitado         : beca_solicitado,
                    porcentaje_solicitado   : porcentaje_solicitado,
                    academic_year           : academic_year,
                    academic_term           : academic_term,
                    academic_session        : academic_session,
                    tipobs                  : tipobs,
                    proceder                : proceder,
                    nivelb                  : nivelb,
                    promedio                : promedio,
                    tiposol                 : 'renovacion',
                    empresaconv             : empresaconv
                },
                dataType: 'json',
                url:  'actions.php',
                success:  function(response)
                {   
                    var status  = response[0];
                    var tipob   = response[1];

                    if(status == 'ok')
                    {
                        if(tipob == 0){
                            //alert('Beca solicitada correctamente');
                            $("#msj").empty();
                            $('#mensajes').modal('show');
                            $("#msj").append('<div class="modal-header">'
                                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarsalir()">'
                                                +'<span aria-hidden="true">&times;</span>'
                                            +'</button>'
                                        +'</div>'
                                        +'<div class="modal-body">'
                                            +'<div class="alert alert-success" role="alert">'
                                                +'<p>TU SOLICITUD DE BECA HA SIDO REALIZADA CORRECTAMENTE.</p>'
                                                +'<p>Te solicitamos estar al pendiente de la fecha de revision y autorizacion de Beca.</p>'
                                                +'<p>Entra regularmente al sistema, ya que aqui se indicara el Estado de tu Solicitud de Beca.</p>'
                                                +'<p>Para aclaración de dudas comunicate al Depto. de Control Economico, Ext. 188 .</p>'
                                            +'</div>'
                                        +'</div>');
                        }
                        else
                        {
                            //alert('Puede continual al siguiente paso');
                            $("#msj").empty();
                            $('#mensajes').modal('show');
                            $("#msj").append('<div class="modal-header">'
                                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="solicitud_p2('+tipob+')">'
                                                +'<span aria-hidden="true">&times;</span>'
                                            +'</button>'
                                        +'</div>'
                                        +'<div class="modal-body">'
                                            +'<div class="alert alert-success" role="alert">'
                                                +'<p>Informacón guardada correctamente, puedes continuar.</p>'
                                            +'</div>'
                                        +'</div>');
                        }
                    }
                    else
                    {
                        //alert('ocurrio un problema, por favor vuelve a intentarlo, salir');
                        $("#msj").empty();
                        $('#mensajes').modal('show');
                        $("#msj").append('<div class="modal-header">'
                                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarsalir()">'
                                                +'<span aria-hidden="true">&times;</span>'
                                            +'</button>'
                                        +'</div>'
                                        +'<div class="modal-body">'
                                            +'<div class="alert alert-danger" role="alert">'
                                                +'Lo sentimos, ocurrio un problema con la solicitud, vuelve a intentarlo.'
                                            +'</div>'
                                        +'</div>');
                    }
                }
            }); 
        }
    
        //Funcion indica el segundo paso a seguir para la solicitud de Beca
        //1 ACADEMICA: solo se muestra el mensaje de renovacion de beca
        //2 EMPRESARIA: se envia al sistema de documentos para subir solo los que requiere el convenio empresarial, se enviara el tipo de beca para mostrar los documentos necesarios 
        function solicitud_p2(tipobk){
            //alert(becatipo+'-->'+promedio); se enviara al sistema de documentos 2 renovacion de beca empresarial
            if(tipobk == 1){
                window.open('documentos_rendimiento.php','_self');
            }else{
                window.open('documentos_convenio.php','_self');
            }
        }
    
    
    //Funcion despues de Cerrar el mensaje, del promedio no aceptado y cerrar Sesion
    function cerrarsalir(){
        recarga();
    }

    //Cerrar Sesion
    function recarga(){
        url = "logout.php";
        $(location).attr('href',url);
    }
    </script>
</html>

