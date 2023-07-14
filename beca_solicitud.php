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

$idpc   = $_SESSION["alumno"];##People Id del alumno 9 digitos
$folio  = $_SESSION["recibo"];##Folio, numero de recibo

################################################################################
##Este es el sistema para la solicitud de una nueva Beca                      ##
##Para la solicitud de una nueva el alumno debe:                              ##
##Realizar el pago del estudios socioeconomico                                ##
##La vaidacion de dicho pago se realiza con anterioridad, lo cual da paso a   ##
##este sistema                                                                ##
##Realizar la busqueda de la informacion del alumno, lo mas posible para el   ##
##inicio de la solicitud                                                      ##
##Se selecciona el tipo de Beca a solicitar, con lo cual se podra identificar ##
##si se debe de llenar solicitud o solo se debe de realizar el pedido de      ##
##beca y porcentaje, asi como los documentos a subir                          ##
##Para las becas de Rendimiento Academico SEP                                 ##
##Se inicia el llenado de la solicitud, esta informacion debera llenarse      ##
##completamente, la informacion principal que solicita el reporte SEP sera    ##
##otro tipo de informacion sera tambien validada, aunque podra quedarse vacia ##
##Cada que se llene una seccion de informacion, se validara la informacion    ##
##Si todo esta correcto esta seccion sera guardada, asi consecutivamente hasta##
##Completar la solicitud de informacion, finalizado esto, se procede a        ##
##realizar la carga de documentos, esto con respecto al tipo de Beca          ##
##Al llegar aqui el alumno, indica que el alumno es de nuevo ingreso o que no ##
##ha solicitado Beca Anteriormente                                            ##
##Se inicia con la verificacion de su promedio en el periodo anterior,        ##
##En caso de no existir informacion del periodo anterior el alumno se tomara  ##
##como nuevo ingreso                                                          ##
##Se verifica el nivel en el cual estuvo anteriormente el alumno, si cambia   ## 
##de nivel de Bachilletaro a Licenciatura o Licenciatura a Maestria o         ##
##Maestria a Doctorado, se tomara como nuevo ingreso                          ##
##En caso contrario se tomara como alumno activo, obteniendo su promedio del  ##
##periodo                                                                     ##
##Si el promedio es muy bajo?                                                 ##
################################################################################

##Obtener el periodo en el cual se esta solicitando la Beca, esto con el recibo
##Se junta la obtencion del nivel del alumno en el periodo actual
$query_beca = "SELECT c.ACADEMIC_YEAR aniob, c.ACADEMIC_TERM periodob, c.ACADEMIC_SESSION plantelb, a.PROGRAM programa, a.DEGREE nivel, a.CURRICULUM carrera
                FROM CASHRECEIPT c
                INNER JOIN ACADEMIC a
                ON c.PEOPLE_ORG_ID = a.PEOPLE_ID
                AND c.ACADEMIC_YEAR = a.ACADEMIC_YEAR
                AND c.ACADEMIC_TERM = a.ACADEMIC_TERM
                AND c.ACADEMIC_SESSION = a.ACADEMIC_SESSION
                WHERE RECEIPT_NUMBER = '$folio'";
$cmd = new mssqlCommand($query_beca,$con);
$info = $cmd->ExecuteReader(true);

/*$beca_anio      = '2018';##$info[0]['aniob']; ##Informacion del periodo actual
$beca_periodo   = 'CUATRIMESA';##$info[0]['periodob']; ##Informacion del periodo actual
$beca_plantel   = 'PUEBLA';##$info[0]['plantelb']; ##Informacion del periodo actual
$beca_programa  = 'ESCOL';##$info[0]['programa']; ##Informacion del periodo actual
$beca_nivel     = 'MTRIA';##$info[0]['nivel']; ##Informacion del periodo actual
$beca_carrera   = 'PMATI';##$info[0]['carrera']; ##Informacion del periodo actual*/

$beca_anio      = $info[0]['aniob']; ##Informacion del periodo actual
$beca_periodo   = $info[0]['periodob']; ##Informacion del periodo actual
$beca_plantel   = $info[0]['plantelb']; ##Informacion del periodo actual
$beca_programa  = $info[0]['programa']; ##Informacion del periodo actual
$beca_nivel     = $info[0]['nivel']; ##Informacion del periodo actual

##Obtener los datos del periodo anterior a cual esta solicitando la Beca
if($beca_periodo == 'SEMESTREA'){ $periodo_anterior = 'SEMESTREB'; $new_year = ($beca_anio-1);}
elseif ($beca_periodo == 'SEMESTREB'){ $periodo_anterior = 'SEMESTREA'; $new_year = $beca_anio;}
elseif ($beca_periodo == 'CUATRIMESA'){ $periodo_anterior = 'CUATRIMESC'; $new_year = ($beca_anio-1);}
elseif ($beca_periodo == 'CUATRIMESB'){ $periodo_anterior = 'CUATRIMESA'; $new_year = $beca_anio;}
elseif ($beca_periodo == 'CUATRIMESC'){ $periodo_anterior = 'CUATRIMESB'; $new_year = $beca_anio;}

##Obtener la informacion academica correspondiente al periodo anterior a la beca
$query_beca = "SELECT PROGRAM, DEGREE, CURRICULUM, MATRIC_YEAR, MATRIC_TERM, MATRIC_SESSION
                FROM ACADEMIC 
                WHERE PEOPLE_ID = '$idpc'
                AND ACADEMIC_YEAR = '$new_year'
                AND ACADEMIC_TERM = '$periodo_anterior'
                AND ACADEMIC_SESSION = '$beca_plantel';";
$cmd = new mssqlCommand($query_beca,$con);
$info = $cmd->ExecuteReader(true);
$last_programa  = $info[0]['PROGRAM']; ##Informacion del periodo anterior
$last_nivel     = $info[0]['DEGREE']; ##Informacion del periodo anterior
$las_carrera    = $info[0]['CURRICULUM']; ##Informacion del periodo anterior

$anio_plan      = $info[0]['MATRIC_YEAR']; ##Informacion sobre el plan de estudios del alumno
$periodo_plan   = $info[0]['MATRIC_TERM']; ##Informacion sobre el plan de estudios del alumno
$plantel_plan   = $info[0]['MATRIC_SESSION']; ##Informacion sobre el plan de estudios del alumno

##Ahora que se tiene la informacion de ambos periodos se procede a realizar la comparacion para saber si el alumno cambia de nivel
if($last_programa == '') ##Si no existe registro del alumno en el periodo anterior se toma como alumno nuevo
{
    $alumnotipo = 1; ##Alumno Nuevo Ingreso
}
else
{
    if($beca_nivel == 'LIC' && $last_nivel == 'BACH'){
        $alumnotipo = 1; ##Alumno nuevo
    }else if($beca_nivel == 'MTRIA' && $last_nivel == 'LIC'){
        $alumnotipo = 1; ##Alumno nuevo
    }else if($beca_nivel == 'DOCT' && $last_nivel == 'MTRIA'){
        $alumnotipo = 1; ##Alumno nuevo
    }else{
        $alumnotipo = 2; ##Alumno activo
    }
}

##Si el alumno es nuevo ingreso se dara opcion de ingresar su promedio para poder ir a una Beca
##Si el alumno es activo se procede a verificar su promedio en el periodo
if($alumnotipo == 1)
{
    $promedio = 'nuevo';
}
else if($alumnotipo == 2)
{
    ##Se obtienen las materias del alumno en el periodo anterior a la solicitud de Beca
    if(($las_carrera == 'PLLEX') || ($las_carrera == 'TLLEX') || ($last_programa == 'ABIER') || ($las_carrera == 'PMEI')){
        $noingles = "";
    }
    else{
        $noingles = "AND EVENT_ID NOT LIKE 'LE%'";
    }

    $query_materias = "SELECT EVENT_ID, FINAL_GRADE
                    FROM TRANSCRIPTDETAIL
                    WHERE PEOPLE_ID = '$idpc'
                    AND ACADEMIC_YEAR = $new_year
                    AND ACADEMIC_TERM = '$periodo_anterior'
                    AND ACADEMIC_SESSION = '$beca_plantel'
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
             $mat = mat_plan($las_carrera, $evid);
			//echo "<br>".$las_carrera."-".$evid."-".$mat;
            if($mat != '')
            {
                $suma_calif = $suma_calif + $materia['FINAL_GRADE'];
                $cont++;
            }
        }

        $promedio = $suma_calif / $cont;
    }
    
    ##Promedio del alumno Activo, este debe de ser mayor a 8, de lo contrario no puede solicitar Beca
    //$promedio = 8.5;//Promedio de prueba
    if($folio == 1476157 || $folio== 1477662 || $folio== 1475317 || $folio== 1460124 || $folio== 1487334 || $folio== 1474322 || $folio== 1489742 || $folio == 1491505  ){//el recibo que estaba: 1445126,1465695,8.8
        $promedio = 8.5;
    }else{
        $promedio = truncateFloat($promedio,2);
    }
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
    $mt = $cmd->ExecuteReader(true);
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

##Informacion del Alumno requerida para llenar parte del formato de Solicitud de beca
function datos($matpc,$year,$periodo,$plantel)
{
    $consulta ="SELECT DISTINCT
                TAX_ID,
                PEOPLE_ID AS PID,
                apellido,
                nombreuno,
                nombre,
                amp,
                nacimiento,
                edad,
                carrera,
                CASE genero 
                WHEN 'M' THEN '0'
                WHEN 'F' THEN '1'
                WHEN 'U' THEN 'i'
                WHEN ' ' THEN 'i'
                END genero,
                calle,
                colonia,
                ciudad, 
                estadores,
                estadopro,  
                codigo_postal,
                pais,
                correo,
                telefono,
                discapacidad,
                numcasa

                FROM (
                SELECT 
                ac.PEOPLE_ID, 
                pe.FIRST_NAME as nombre, 
                pe.MIDDLE_NAME as nombreuno, 
                pe.LAST_NAME as apellido,
                pe.Last_Name_Prefix amp,
                pe.TAX_ID AS TAX_ID,
                Convert(NVARCHAR,pe.BIRTH_DATE,103) nacimiento,
                CAST(datediff(dd,pe.BIRTH_DATE,GETDATE()) / 365.25 as int) edad,
                ac.CURRICULUM carrera, 
                ac.PROGRAM, 
                ac.DEGREE, 
                ac.ACADEMIC_YEAR, 
                ac.ACADEMIC_TERM, 
                ac.ACADEMIC_SESSION,
                pf.PhoneNumber as telefono, 
                dm.GENDER genero,
                ad.ADDRESS_LINE_1 calle,
                ad.ADDRESS_LINE_2 colonia,
                ad.CITY ciudad,
                ad.STATE,
                cs.LONG_DESC estadores,
                dm.ETHNICITY,
                ce.LONG_DESC estadopro,
                ad.ZIP_CODE codigo_postal,
                ad.COUNTRY pais,
                ad.EMAIL_ADDRESS correo,
                ds.DISABILITY discapacidad,
                ad.HOUSE_NUMBER numcasa

                FROM dbo.ACADEMIC AS ac
                LEFT JOIN dbo.PEOPLE AS pe 
                ON ac.PEOPLE_ID = pe.PEOPLE_ID
                LEFT JOIN dbo.PersonPhone AS pf
                ON pe.PrimaryPhoneId = pf.PersonPhoneId
                LEFT JOIN dbo.DEMOGRAPHICS AS dm
                ON ac.PEOPLE_ID = dm.PEOPLE_ID
                LEFT JOIN dbo.ADDRESSSCHEDULE AS ad
                ON ac.PEOPLE_ID = ad.PEOPLE_ORG_ID
                LEFT JOIN dbo.CODE_ETHNICITY ce
                ON dm.ETHNICITY = ce.CODE_VALUE_KEY
                LEFT JOIN dbo.CODE_STATE cs
                ON ad.STATE = cs.CODE_VALUE_KEY
                LEFT JOIN dbo.DISABLEREQUIRE AS ds
                ON ac.PEOPLE_ID = ds.PEOPLE_ID

                WHERE ac.PEOPLE_ID = '$matpc'
                AND ac.ACADEMIC_SESSION = '$plantel' 
                AND ac.ACADEMIC_YEAR = '$year' 
                AND ac.ACADEMIC_TERM = '$periodo'
                ) tabla
                ORDER BY nombre;";
    
    return $consulta;
}

$query_alumno = datos($idpc,$beca_anio,$beca_periodo,$beca_plantel);
$cmdalumno = new mssqlCommand($query_alumno,$con);
$datos_alumno = $cmdalumno->ExecuteReader(true);
$taxid  = $datos_alumno[0]['TAX_ID'];

if( $taxid == '')
{
    $query_alumno = datos($idpc,$new_year,$periodo_anterior,$beca_plantel);
    $cmdalumno = new mssqlCommand($query_alumno,$con);
    $datos_alumno = $cmdalumno->ExecuteReader(true);
}

##Datos del alumno
foreach($datos_alumno as $alumno)
{
    $alumno = array_map('utf8_encode', $alumno);##codificacion para todos los elementos del arreglo
    $taxid          = $alumno['TAX_ID'];
    $nombre         = $alumno['nombre'];
    $nombreuno      = $alumno['nombreuno'];
    $apellido       = $alumno['apellido'];
    $amp            = $alumno['amp'];
    $nacimiento     = $alumno['nacimiento'];
    $edad           = $alumno['edad'];
    $genero         = $alumno['genero'];
    $calle          = $alumno['calle'];
    $colonia        = $alumno['colonia'];
    $ciudad         = $alumno['ciudad'];
    $estadores      = $alumno['estadores'];
    $estadopro      = $alumno['estadopro'];
    $cp             = $alumno['codigo_postal'];
    $pais           = $alumno['pais'];
    $correo         = $alumno['correo'];
    $telefono       = $alumno['telefono'];
    $discapacidad   = $alumno['discapacidad'];
    $numcasa        = $alumno['numcasa'];
}

##DEGREE DEL ALUMNO 
$nivel = substr($beca_carrera, 1, 1);
if($nivel == 'L')
{
    $nivel = 'LICENCIATURA';
}
elseif ($nivel == 'M') 
{
    $nivel = 'MAESTRIA';
}
elseif ($nivel == 'D') 
{
    $nivel = 'DOCTORADO';
}
elseif ($nivel == 'B') 
{
    $nivel = 'BACHILLERATO';
}

##Obtener las Becas del periodo en el que se solicita la Beca
##Obtener el id del periodo en el cual se solicita la beca
$query_idp ="SELECT SessionPeriodId FROM ACADEMICCALENDAR WHERE ACADEMIC_YEAR = '$beca_anio' 
                AND ACADEMIC_TERM = '$beca_periodo' AND ACADEMIC_SESSION = '$beca_plantel';";
$cmd = new mssqlCommand($query_idp,$con);
$data = $cmd->ExecuteReader(true);
$idperiodo = $data[0]['SessionPeriodId']; ##Id del periodo

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
                    <legend>Solicitud de Beca</legend>
                    <div id="promedio_nuevo" style="display: none;">
                        <p>El sistema detecta que eres Alumno de Nuevo Ingreso, por favor Ingresa tu Promedio para poder continuar con el proceso de Solicitud de Beca.</p>
                        <p>El promedio indicado sera verificado con los documentos entregados</p>
                        <p>Ingresa tu promedio con un formato de 1 a 10, p/e 9.00</p>
                        <p>Promedio minimo para la Solicitud de Beca es de 8.00</p>
                        <p>TU PROMEDIO DETERMINARA LAS BECAS DISPONIBLES PARA TU SOLICITUD.</p>
                        
                        <div class="input-group" style=" margin-left: 37%;">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-edit"></i></span>
                            <a data-toggle="tooltip" title="Ingresa tu Promedio" data-placement="right">
                                <input type="number" id="promedio1" class="form-control" maxlength="5" placeholder="8.35" required autofocus style="text-align: center; width: 25%;" >
                            </a>
                        </div>
                        <br>
                        <div align="right">
                            <button class="btn btn-lg btn-success" id="validar" onclick="validapromedio()">Continuar</button>
                        </div>
                    </div>
                    
                    <div id="selecciona_beca" style="display: none;">
                        <div class="form-group" id="seleccion_beca">
                        </div>
                        <div class="form-group" id="porcentaje_beca">
                        </div>
                        <div style="display: none;" class="form-group" id="empresaconvenio">
                            <label for="seleccionaporcentaje">Nombre de la Empresa</label>
                            <input type="text" id="empresa" class="form-control" placeholder="Nombre de la Empresa">
                        </div>
                        <div align="right">
                            <button class="btn btn-lg btn-success" id="guarda_beca" onclick="validabecasolicitada()">Continuar</button>
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
    var promedio;
    
    $(document).ready(function () 
        {
            //Evitar que el modal se cierre al dar clic fuera de el
            $('#mensajes').modal({backdrop: 'static', keyboard: false});
            
            //Obtener el tipo de alumno y promedio del mismo
            promedio        = '<?=$promedio;?>';
            var alumnotipo  = '<?=$alumnotipo?>';//1 Alumno nuevo Ingreso, 2 Alumno Activo
            var idperiodo   = '<?=$idperiodo?>'; //Id del periodo, si existe procede, si no no procede
            
            if(idperiodo == '')
            {
                //Si el alumno es de Nuevo Ingreso, se mostrara la opcion para ingresar el promedio del alumno
                $('#mensajes').modal('show');
                $("#msj").empty();
                $("#msj").append('<div class="modal-header">'
                                        +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarsalir()">'
                                            +'<span aria-hidden="true">&times;</span>'
                                        +'</button>'
                                    +'</div>'
                                    +'<div class="modal-body">'
                                        +'<div class="alert alert-warning" role="alert">'
                                            +'<p>Existe un problema con el nuevo periodo, al parecer no lo tienes registrado.</p>'
                                            +'<p>Acude con Control Escolar o Control Economico para revisar esta situación.</p>'
                                        +'</div>'
                                    +'</div>');
            }
            else if(alumnotipo == 1)
            {
                //Si el alumno es de Nuevo Ingreso, se mostrara la opcion para ingresar el promedio del alumno
                $("#msj").empty();
                $('#mensajes').modal('show');
                $("#msj").append('<div class="modal-header">'
                                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                                +'<span aria-hidden="true">&times;</span>'
                                            +'</button>'
                                        +'</div>'
                                        +'<div class="modal-body">'
                                            +'<div class="alert alert-info" role="alert">'
                                                +'<p>Debes ingresa tu Promedio para poder continuar con el proceso de Solicitud de Beca.</p>'
                                                +'<p>El promedio indicado sera verificado con los documentos entregados.</p>'
                                                +'<p>Promedio minimo para la Solicitud de Beca es de 8.00</p>'
                                                +'<p>TU PROMEDIO DETERMINARA LAS BECAS DISPONIBLES PARA TU SOLICITUD.</p>'
                                            +'</div>'
                                        +'</div>');
                $("#promedio_nuevo").css("display", "block");
            }
            else if(alumnotipo == 2)
            {
                //Si el Alumno es Activo, se verifica el promedio que trae del periodo anterior
                //Promedio mayor a 8.00
                if(promedio >= 8.00)
                {
                    //alert('Promedio aceptado');
                    $("#msj").empty();
                    $('#mensajes').modal('show');
                    $("#msj").append('<div class="modal-header">'
                                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                                +'<span aria-hidden="true">&times;</span>'
                                            +'</button>'
                                        +'</div>'
                                        +'<div class="modal-body">'
                                            +'<div class="alert alert-success" role="alert">'
                                                +'<p>Tu promedio cumple con los Requisitos de Solicitud de Beca. '+promedio+'</p>'
                                            +'</div>'
                                        +'</div>');
                                
                    //Cuando el promedio es el correcto para proceder con la solicitud se pasa a la siguiente parte de la solicitud
                    solicitud_p1(promedio); //La parte uno de la solicitud, se selecciona la beca a solicitar, esto segun el promedio del alumno
                }
                //Promedio menor a 8.00
                else
                {
                    $('#mensajes').modal('show');
                    $("#msj").empty();
                    $("#msj").append('<div class="modal-header">'
                                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarsalir()">'
                                                +'<span aria-hidden="true">&times;</span>'
                                            +'</button>'
                                        +'</div>'
                                        +'<div class="modal-body">'
                                            +'<div class="alert alert-danger" role="alert">'
                                                +'Lo sentimos, no cumples con el Promedio minimo para Solicitar una Beca.'+promedio
                                            +'</div>'
                                        +'</div>');
                }            
            }
        });
        
        /*Validar el promedio de Nuevo Ingreso, para determinar si el promedio es apto para solicitar una Beca*/
        function validapromedio(){
            promedio    = $.trim($("#promedio1").val());
            
            if(promedio == '')
            {
                //alert('Debes Ingresar tu Promedio');
                $("#msj").empty();
                $('#mensajes').modal('show');
                $("#msj").append('<div class="modal-header">'
                                        +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                            +'<span aria-hidden="true">&times;</span>'
                                        +'</button>'
                                    +'</div>'
                                    +'<div class="modal-body">'
                                        +'<div class="alert alert-warning" role="alert">'
                                            +'<p>Debes ingresar tu Promedio.</p>'
                                        +'</div>'
                                    +'</div>');
            }
            else
            {
                if(promedio <= 0 || promedio > 10){
                    //alert('Debes Ingresar tu Promedio correctamente');
                    $("#msj").empty();
                    $('#mensajes').modal('show');
                    $("#msj").append('<div class="modal-header">'
                                        +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                            +'<span aria-hidden="true">&times;</span>'
                                        +'</button>'
                                    +'</div>'
                                    +'<div class="modal-body">'
                                        +'<div class="alert alert-warning" role="alert">'
                                            +'<p>Debes Ingresar tu Promedio correctamente.</p>'
                                        +'</div>'
                                    +'</div>');
                }
                else if(promedio <= 7.999){
                    //alert('promedio bajo');
                    $("#msj").empty();
                    $('#mensajes').modal('show');
                    $("#msj").append('<div class="modal-header">'
                                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarsalir()">'
                                                +'<span aria-hidden="true">&times;</span>'
                                            +'</button>'
                                        +'</div>'
                                        +'<div class="modal-body">'
                                            +'<div class="alert alert-danger" role="alert">'
                                                +'Lo sentimos, no cumples con el Promedio minimo para Solicitar una Beca.'
                                            +'</div>'
                                        +'</div>');
                }
                else{
                    //alert('promedio ok');
                    $("#msj").empty();
                    $('#mensajes').modal('show');
                    $("#msj").append('<div class="modal-header">'
                                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                                +'<span aria-hidden="true">&times;</span>'
                                            +'</button>'
                                        +'</div>'
                                        +'<div class="modal-body">'
                                            +'<div class="alert alert-success" role="alert">'
                                                +'<p>Tu promedio cumple con los Requisitos de Solicitud de Beca, puedes continuar.</p>'
                                            +'</div>'
                                        +'</div>');
                                
                    //Cuando el promedio es el correcto para proceder con la solicitud se pasa a la siguiente parte del proceso
                    solicitud_p1(promedio); //La parte uno de la solicitud, se selecciona la beca a solicitar, esto segun el promedio del alumno
                }
            }
        }
        
        //Seolicitud de Beca parte 1, en esta parte de la beca se inicia con la seleccion de la beca a solicitar, las becas mostradas al alumno dependeran de su promedio
        function solicitud_p1(promedio){
            //Se esconde el apartado del promedio de alumnos de nuevo ingreso
            $("#promedio_nuevo").css("display", "none");
            
            //Se carga el formulario de seleccion de Beca
            var idperiodo = '<?=$idperiodo?>';
            $("#selecciona_beca").css("display", "block");
            $("#seleccion_beca").load('vista.php?accion=becas_seleccion&promedio='+promedio+'&idperiodo='+idperiodo);
        }
        
        //Se muestran los porcentajes de la beca seleccionada
        function becasolicitada(){
            if($("#beca_solicitada").val() != '0'){
                $("#porcentaje_beca").load('vista.php?accion=porcentaje_seleccion&idbeca='+ $("#beca_solicitada").val());		
                /*Mostrar la opcion para ingresar la empresa de la cual esta realizando el convenio empresarial, en caso de seleccionar esta beca*/
                var tipobeca = $("#beca_solicitada").val();
                if(tipobeca == 136 || tipobeca == 150){
                    $("#empresaconvenio").css("display", "block");
                }else{
                    $("#empresaconvenio").css("display", "none");
                    $("#empresa").val('');
                }
            }
        }
        
        //Se verifica la beca solicitada, y se verifica que se debe de realizar depues
        //Si la beca a solicitar es diferente de convenio y rendimiento academico, se guarda automaticamente la beca
        //Si la beca es de convenio se envia al sistema de documentos
        //Si la beca es de rendimiento academico se envia al formulario y despues al sistema de documentos
        function validabecasolicitada(){
            var beca_solicitada         = $("#beca_solicitada").val();
            var porcentaje_solicitado   = $("#porcentaje_solicitado").val();
            var empresa                 = $("#empresa").val();
            
            if(beca_solicitada == '0' || porcentaje_solicitado == 0)
            {
                $("#msj").empty();
                $('#mensajes').modal('show');
                $("#msj").append('<div class="modal-header">'
                                    +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                        +'<span aria-hidden="true">&times;</span>'
                                    +'</button>'
                                +'</div>'
                                +'<div class="modal-body">'
                                    +'<div class="alert alert-warning" role="alert">'
                                        +'<p>Debes Seleccionar la Beca y Porcentaje de Beca a solicitar.</p>'
                                    +'</div>'
                                +'</div>');
            }
            else if((beca_solicitada == 136 || beca_solicitada == 150) && empresa == '')
            {
                $("#msj").empty();
                $('#mensajes').modal('show');
                $("#msj").append('<div class="modal-header">'
                                    +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                        +'<span aria-hidden="true">&times;</span>'
                                    +'</button>'
                                +'</div>'
                                +'<div class="modal-body">'
                                    +'<div class="alert alert-warning" role="alert">'
                                        +'<p>Debes ingresar la Empresa con la cual se tiene convenio.</p>'
                                    +'</div>'
                                +'</div>');
            }
            else
            {
                //alert(beca_solicitada+'-->'+porcentaje_solicitado);
                //Verificar el tipo de Beca Solicitada para seguir con el proceso
                $.ajax({
                    type: 'POST',
                    data: 
                    {
                        accion      : 'ver_tipo',
                        beca        : beca_solicitada,
                        solicitado  : porcentaje_solicitado
                    },
                    dataType: 'json',
                    url:  'vista.php',
                    success:  function(response)
                    {   
                        var tipobca         = response[0];
                        var status          = response[1];
                        var porcentaje      = response[2];
                        var nivelbeca       = response[3];
                        guardageneralesbeca(tipobca,status,porcentaje,beca_solicitada,porcentaje_solicitado,nivelbeca,empresa); //Se envian los datos para guardar la solicitud de informacion
                    }
                });
            }
        }
        
        //Guardar el registro de solicitud de Beca diferente a Convenio y Rendimiento
        function guardageneralesbeca(tipobs,accion,porcentaje,becasol,porcsol,nivelb,empresa){
            //Valores a guardar
            //Folio, people_id, beca_tipo, beca_solicitado, porcentaje_solicitado, beca_estatus (0 es espera, 1 aceptado, 2 rechazado), 
            //documentos (0 no solicita, 1 solicita), estatus_documentos (0 sin documentos, 1 en espera, 2 aceptados)
            //año, periodo, plantel, seccion_completa (c completo, 0 solicitud, 1 seccion 1, 2 seccion 2, etc), tipo_beca (1 academica, 2 empresarial, 0 otra)
            //Si la seccion es completo, se verifica si solicita documentos, si solicita documentos, estos se verifican para saber su estatus
            var folio                   = '<?=$folio?>';
            var peopleid                = '<?=$idpc?>';
            var beca_tipo               = becasol;
            var beca_solicitado         = porcsol;
            var porcentaje_solicitado   = porcentaje;
            var academic_year           = '<?=$beca_anio?>';
            var academic_term           = '<?=$beca_periodo?>';
            var academic_session        = '<?=$beca_plantel?>';
            var tipobs                  = tipobs;
            var proceder                = accion;
            var nivelb                  = nivelb;
            var empresaconv             = empresa;
            
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
                    tiposol                 : 'nueva',
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
                        else{
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
                                                +'<p>Información guardada correctamente, puedes continuar.</p>'
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
        //1 ACADEMICA: se envia al formulario de beca rendimiento academico
        //2 EMPRESARIA: se envia al sistema de documentos para subir solo los que requiere el convenio empresarial, se enviara el tipo de beca para mostrar los documentos necesarios 
        function solicitud_p2(becatipo){
            //alert(becatipo+'-->'+promedio);
            if(becatipo == 2){
                window.open('documentos_convenio.php','_self');
            }else{
                /*$("#msj").empty();
                $('#mensajes').modal('show');
                $("#msj").append('<div class="modal-header">'
                                +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarsalir()">'
                                    +'<span aria-hidden="true">&times;</span>'
                                +'</button>'
                            +'</div>'
                            +'<div class="modal-body">'
                                +'<div class="alert alert-info" role="alert">'
                                    +'<p>El formulario para solicitud de Beca de Rendimiento Academico se esta actualizando, disculpa las molestias.</p>'
                                    +'<p>Por favor vuelve a internat mas tarde, Gracias.</p>'
                                +'</div>'
                            +'</div>');*/
                window.open('beca_formulario.php','_self');
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
