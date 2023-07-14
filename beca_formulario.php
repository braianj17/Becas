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
##Realizar la busqueda de la informacion del alumno, lo mas posible para el   ##
##inicio de la solicitud                                                      ##
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
################################################################################

##Obtener el periodo en el cual se esta solicitando la Beca, esto con el recibo
##Se junta la obtencion del nivel del alumno en el periodo actual
$query_beca = "SELECT c.ACADEMIC_YEAR aniob, c.ACADEMIC_TERM periodob, c.ACADEMIC_SESSION plantelb, a.PROGRAM programa, a.DEGREE nivel, a.CURRICULUM carrera
                FROM CASHRECEIPT c
                LEFT JOIN ACADEMIC a
                ON c.PEOPLE_ORG_ID = a.PEOPLE_ID
                AND c.ACADEMIC_YEAR = a.ACADEMIC_YEAR
                AND c.ACADEMIC_TERM = a.ACADEMIC_TERM
                AND c.ACADEMIC_SESSION = a.ACADEMIC_SESSION
                WHERE RECEIPT_NUMBER = '$folio'";
$cmd = new mssqlCommand($query_beca,$con);
$info = $cmd->ExecuteReader(true);

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

##Se verifica el tipo de Periodo para llenar el Select del grado educativo
$tipop = $beca_periodo[0];
if($tipop == 'S'){
    ##Si es semestral
    $grados     = array('Semestre 1 y 2', 'Semestre 3 y 4','Semestre 5 y 6','Semestre 7 y 8','Semestre 9 y 10');
    $valores    = array('PRIMERO', 'SEGUNDO','TERCERO','CUARTO','QUINTO');
}else{
    ##Si es cuatrimestral
    ##Si es semestral
    $grados     = array('Cuatrimestre 1, 2 y 3', 'Cuatrimestre 4, 5 y 6','Cuatrimestre 7, 8 y 9','Cuatrimestre 10, 11 y 12');
    $valores    = array('PRIMERO', 'SEGUNDO','TERCERO','CUARTO');
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
                    <legend>Solicitud de Beca: Formulario Beca Rendimiento Académico</legend>
                    
                    <!--Inicio Seccion 1-->
                    <legend id="t1" style="display: block;">Seccion 1: Datos Generales</legend>
                    <legend id="t12" style="display: none;">Seccion 1: Datos Generales - Completa</legend>
                    <div id="sec1" style="display: block;">
                        <div class="form-inline form-group col-sm-12">
                          <label for="solicitante">Nombre del Solicitante</label>
                        </div>
                        <div class="form-group col-sm-12">
                            <div class="form-group col-sm-4">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="nombre" placeholder="Nombre(s)">
                            </div>
                            <div class="form-group col-sm-4">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1" placeholder="Apellido Paterno">
                            </div>
                            <div class="form-group col-sm-4">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2" placeholder="Apellido Materno">
                            </div>
                        </div>

                        <div class="form-group col-sm-12">
                          <label for="gradonivel">Grado y Nivel Educativo</label>
                        </div>
                        <div class="form-group col-sm-12">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="grado">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <?php
                                    for($i = 0; $i < count($valores); $i++){
                                    ?>
                                    <option value="<?=$valores[$i]?>"><?=$grados[$i]?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group col-sm-6">
                                <select class="form-control" id="nivel">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="BACHILLERATO">Bachillerato</option>
                                    <option value="LICENCIATURA">Licenciatura</option>
                                    <option value="POSGRADO">Posgrado</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group col-sm-12">
                          <label for="promedio">Promedio</label>
                        </div>
                        <div class="form-group col-sm-12">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" id="promedio" placeholder="Promedio">
                            </div>
                        </div>

                        <div class="form-group col-sm-12">
                          <label for="nueva">Beca Nueva?</label>
                        </div>
                        <div class="form-group col-sm-12">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="nueva" onchange="mostrar()">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">Si</option>
                                    <option value="NO">No</option>
                                </select>
                            </div>
                        </div>

                        <div id="ren1" style="display: none;" class="form-group col-sm-12">
                          <label for="ren1">Porcentaje de Beca Anterior</label>
                        </div>
                        <div id="ren2" style="display: none;" class="form-group col-sm-12">
                            <div class="form-group col-sm-6">
                                <input type="text" class="form-control" id="anterior" placeholder="% Beca Anterior">
                            </div>
                        </div>

                        <div class="form-group col-sm-12">
                          <label for="motivo">Exponga los motivos principales por los que solicita la beca</label>
                        </div>
                        <div class="form-group col-sm-12">
                            <textarea class="form-control" id="motivo" rows="3" style="width: 100%; resize: none;"></textarea>
                        </div>

                        <div class="form-group col-sm-12" style="text-align: right;">
                            <button class="btn btn-lg btn-success" id="validar" onclick="guardasec1()">Continuar</button>
                        </div>
                    </div>
                    <!--Fin Seccion 1-->
                    
                    <!--Inicio Seccion 2-->
                    <legend id="t2" style="display: none;">Seccion 2: Informacion del Alumno</legend>
                    <legend id="t22" style="display: none;">Seccion 2: Informacion del Alumno - Completa</legend>
                    <div id="sec2" style="display: none;">
                        <div class="form-group col-sm-6">
                          <label for="promedio">Edad</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" maxlength="2" class="form-control" id="edad" placeholder="Edad">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">Genero</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="genero">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="HOMBRE">MASCULINO</option>
                                    <option value="MUJER">FEMENINO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">CURP/NIA (solo en caso de Bachillerato)</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-8">
                                <input type="text" style="text-transform:uppercase;" class="form-control" id="curp" placeholder="CURP/NIA">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">Teléfono Particular</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-8">
                                <input type="number" class="form-control" id="telefono" placeholder="Teléfono">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-12">
                          <label for="solicitante">Dirección</label>
                        </div>
                        <div class="form-group col-sm-12">
                            <div class="form-group col-sm-8">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="calle" placeholder="Calle">
                            </div>
                            <div class="form-group col-sm-2">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="num1" placeholder="Número Exterior">
                            </div>
                            <div class="form-group col-sm-2">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="num2" placeholder="Número Interior">
                            </div>
                            
                            <div class="form-group col-sm-8">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="colonia" placeholder="Colonia">
                            </div>
                            <div class="form-group col-sm-4">
                                <input type="number" class="form-control" id="cp" placeholder="Codigo Postal">
                            </div>
                            <div class="form-group col-sm-6">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="localidad" placeholder="Localidad">
                            </div>
                            
                            <div class="form-group col-sm-6">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="municipio" placeholder="Municipio">
                            </div>
                            <div class="form-group col-sm-6">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="residencia" placeholder="Estado de Residencia">
                            </div>
                            <div class="form-group col-sm-6">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="procedencia" placeholder="Estado de Procedencia">
                            </div>
                        </div>

                        <div class="form-group col-sm-6">
                          <label for="nueva">Ha solicitado Beca Anteriormente</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="bantes" onchange="mostrar2()">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="ban1" style="display: none;" class="form-group col-sm-6">
                          <label for="promedio">Cuando?</label>
                        </div>
                        <div id="ban2" style="display: none;" class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="text" class="form-control" id="cuando" placeholder="Cuando?">
                            </div>
                        </div>

                        <div class="form-group col-sm-6">
                          <label for="promedio">Año de Ingreso a la Institución</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" maxlength="4" class="form-control" id="ingreso" placeholder="Año de Ingreso a la Institución">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">¿Sufre alguna discapacidad?</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="discapacitado" onchange="mostrar3()">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="dis1" style="display: none;" class="form-group col-sm-6">
                          <label for="nueva">¿Cual?</label>
                        </div>
                        <div id="dis2" style="display: none;" class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="discapacidad">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="MOTRIZ">MOTRIZ</option>
                                    <option value="VISUAL">VISUAL</option>
                                    <option value="MENTAL">MENTAL</option>
                                    <option value="AUDITIVA">AUDITIVA</option>
                                    <option value="LENGUAJE">LENGUAJE</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">¿Depende económicamente de sus padres?</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="dependiente">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">¿Vive su Padre?</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="padre">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">¿Vive su Madre?</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="madre">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">Situación de sus Padres</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="situacion">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="UNIDOS">UNIDOS</option>
                                    <option value="DIVORCIADOS">DIVORCIADOS</option>
                                    <option value="SEPARADOS">SEPARADOS</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">Cuantas personas Dependen económicamente del Tutor</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" maxlength="2" class="form-control" id="depende" placeholder="Dependientes del tutor">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">¿Cuantos Hijos tiene el Tutor?</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" maxlength="2" class="form-control" id="hijos" placeholder="Num hijos del tutor">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">¿Cuantos Estudian?</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" maxlength="2" class="form-control" id="estudian" placeholder="Estudiantes">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-12" style="text-align: right;">
                            <button class="btn btn-lg btn-success" id="validar" onclick="guardasec2()">Continuar</button>
                        </div>
                    </div>
                    <!--Fin Seccion 2-->
                    
                    <!--Inicio Seccion 2 continuacion tutores-->
                    <legend id="t3" style="display: none;">Seccion 2: Informacion Padre, Madre, Tutor</legend>
                    <legend id="t32" style="display: none;">Seccion 2: Informacion Padre, Madre, Tutor - Completa</legend>
                    <div id="sec21" style="display: none;">
                        <div class="form-group col-sm-12">
                          <label for="solicitante">Información del Padre</label>
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="promedio">Nombre Completo</label>
                        </div>
                        <div class="form-group col-sm-12">
                            <div class="form-group col-sm-4">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="nombrepadre" placeholder="Nombre">
                            </div>
                            <div class="form-group col-sm-4">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1padre" placeholder="Apellido Paterno">
                            </div>
                            <div class="form-group col-sm-4">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2padre" placeholder="Apellido Materno">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">Edad</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="edadpadre" placeholder="Edad">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">Nivel de Estudio</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-12">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="estudiospadre" placeholder="Nivel de Estudio">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">Tiene alguna Beca?</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="becapadre">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">Ocupación</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-12">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionpadre" placeholder="Ocupacion">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">Ingreso Neto Mensual</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresopadre" placeholder="Ingreso">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-12">
                          <label for="solicitante">Información de la Madre</label>
                        </div>
                        <div class="form-group col-sm-6">
                          <label for="promedio">Nombre Completo</label>
                        </div>
                        <div class="form-group col-sm-12">
                            <div class="form-group col-sm-4">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="nombremadre" placeholder="Nombre">
                            </div>
                            <div class="form-group col-sm-4">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1madre" placeholder="Apellido Paterno">
                            </div>
                            <div class="form-group col-sm-4">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2madre" placeholder="Apellido Materno">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">Edad</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="edadmadre" placeholder="Edad">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">Nivel de Estudio</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-12">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="estudiosmadre" placeholder="Nivel de Estudio">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">Tiene alguna Beca?</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="becamadre">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">Ocupación</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-12">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionmadre" placeholder="Ocupacion">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="promedio">Ingreso Neto Mensual</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresomadre" placeholder="Ingreso">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">Papa o Mama es el Tutor del alumno?</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="tutor" onchange="mostrar4()">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="infotutor" style="display: none;" class="form-group col-sm-12">
                            <div class="form-group col-sm-12">
                              <label for="solicitante">Información del Tutor</label>
                            </div>
                            
                            <div class="form-group col-sm-6">
                                <label for="nueva">Genero</label>
                            </div>
                            <div class="form-group col-sm-6">
                                <div class="form-group col-sm-6">
                                    <select class="form-control" id="generotutor">
                                        <option value="0" selected="selected">Seleccionar...</option>
                                        <option value="H">MASCULINO</option>
                                        <option value="M">FEMENINO</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group col-sm-6">
                              <label for="promedio">Nombre Completo</label>
                            </div>
                            <div class="form-group col-sm-12">
                                <div class="form-group col-sm-4">
                                    <input type="text" class="form-control" style="text-transform:uppercase;" id="nombretutor" placeholder="Nombre">
                                </div>
                                <div class="form-group col-sm-4">
                                    <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1tutor" placeholder="Apellido Paterno">
                                </div>
                                <div class="form-group col-sm-4">
                                    <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2tutor" placeholder="Apellido Materno">
                                </div>
                            </div>

                            <div class="form-group col-sm-6">
                              <label for="promedio">Edad</label>
                            </div>
                            <div class="form-group col-sm-6">
                                <div class="form-group col-sm-6">
                                    <input type="number" class="form-control" style="text-transform:uppercase;" id="edadtutor" placeholder="Edad">
                                </div>
                            </div>
                            
                            <div class="form-group col-sm-6">
                              <label for="promedio">Nivel de Estudio</label>
                            </div>
                            <div class="form-group col-sm-6">
                                <div class="form-group col-sm-12">
                                    <input type="text" class="form-control" style="text-transform:uppercase;" id="estudiostutor" placeholder="Nivel de Estudio">
                                </div>
                            </div>

                            <div class="form-group col-sm-6">
                              <label for="nueva">Tiene alguna Beca?</label>
                            </div>
                            <div class="form-group col-sm-6">
                                <div class="form-group col-sm-6">
                                    <select class="form-control" id="becatutor">
                                        <option value="0" selected="selected">Seleccionar...</option>
                                        <option value="SI">SI</option>
                                        <option value="NO">NO</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group col-sm-6">
                              <label for="promedio">Ocupación</label>
                            </div>
                            <div class="form-group col-sm-6">
                                <div class="form-group col-sm-12">
                                    <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupaciontutor" placeholder="Ocupacion">
                                </div>
                            </div>

                            <div class="form-group col-sm-6">
                              <label for="promedio">Ingreso Neto Mensual</label>
                            </div>
                            <div class="form-group col-sm-6">
                                <div class="form-group col-sm-6">
                                    <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresotutor" placeholder="Ingreso">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-12" style="text-align: right;">
                            <button class="btn btn-lg btn-success" id="validar" onclick="guardasec2t()">Continuar</button>
                        </div>
                    </div>
                    <!--Fin Seccion 2-->
                    
                    <!--Inicio Seccion 4 continuacion familiares-->
                    <legend id="t4" style="display: none;">Seccion 2: Información de Familiares que viven en la Misma Casa</legend>
                    <legend id="t42" style="display: none;">Seccion 2: Información de Familiares que viven en la Misma Casa - Completa</legend>
                    <div id="sec22" style="display: none;">
                        <!-------------------------------------------------------------------------->
                        <div class="form-group col-sm-6">
                          <label for="nueva">Conyuge</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="conyuge" onclick="mostrar5()">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="infoconyuge" style="display: none;" class="form-group col-sm-12">
                            <div class="form-group col-sm-12">
                                <label for="solicitante">Información de Conyuge</label>
                              </div>
                              <div class="form-group col-sm-6">
                                <label for="promedio">Nombre Completo</label>
                              </div>
                              <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="nombreconyuge" placeholder="Nombre">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1conyuge" placeholder="Apellido Paterno">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2conyuge" placeholder="Apellido Materno">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Edad</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="edadconyuge" placeholder="Edad">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Nivel de Estudio</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="estudiosconyuge" placeholder="Nivel de Estudio">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="nueva">Tiene alguna Beca?</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="becaconyuge">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                          <option value="SI">SI</option>
                                          <option value="NO">NO</option>
                                      </select>
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ocupación</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionconyuge" placeholder="Ocupacion">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ingreso Neto Mensual</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresoconyuge" placeholder="Ingreso">
                                  </div>
                              </div>
                        </div>
                        <!--------------------------------------------------------------------------------->
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">Hijos</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="verhijos"  onclick="mostrar6()">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="infohijos" style="display: none;" class="form-group col-sm-12">
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarhijo(1)">Registro 1</button>
                            </div>
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarhijo(2)">Registro 2</button>
                            </div>
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarhijo(3)">Registro 3</button>
                            </div>
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarhijo(4)">Registro 4</button>
                            </div>
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarhijo(5)">Registro 5</button>
                            </div>
                            
                            <div id="infohijo1" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                <label for="solicitante">Información de Hijo</label>
                              </div>
                              <div class="form-group col-sm-6">
                                <label for="promedio">Nombre Completo</label>
                              </div>
                              <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="nombrehijo1" placeholder="Nombre">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1hijo1" placeholder="Apellido Paterno">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2hijo1" placeholder="Apellido Materno">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Edad</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="edadhijo1" placeholder="Edad">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Nivel de Estudio</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="estudioshijo1" placeholder="Nivel de Estudio">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="nueva">Tiene alguna Beca?</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="becahijo1">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                          <option value="SI">SI</option>
                                          <option value="NO">NO</option>
                                      </select>
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ocupación</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionhijo1" placeholder="Ocupacion">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ingreso Neto Mensual</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresohijo1" placeholder="Ingreso">
                                  </div>
                              </div>
                            </div>
                            
                            <div id="infohijo2" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                <label for="solicitante">Información de Hijo</label>
                              </div>
                              <div class="form-group col-sm-6">
                                <label for="promedio">Nombre Completo</label>
                              </div>
                              <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="nombrehijo2" placeholder="Nombre">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1hijo2" placeholder="Apellido Paterno">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2hijo2" placeholder="Apellido Materno">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Edad</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="edadhijo2" placeholder="Edad">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Nivel de Estudio</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="estudioshijo2" placeholder="Nivel de Estudio">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="nueva">Tiene alguna Beca?</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="becahijo2">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                          <option value="SI">SI</option>
                                          <option value="NO">NO</option>
                                      </select>
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ocupación</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionhijo2" placeholder="Ocupacion">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ingreso Neto Mensual</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresohijo2" placeholder="Ingreso">
                                  </div>
                              </div>
                            </div>
                            
                            <div id="infohijo3" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                <label for="solicitante">Información de Hijo</label>
                              </div>
                              <div class="form-group col-sm-6">
                                <label for="promedio">Nombre Completo</label>
                              </div>
                              <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="nombrehijo3" placeholder="Nombre">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1hijo3" placeholder="Apellido Paterno">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2hijo3" placeholder="Apellido Materno">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Edad</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="edadhijo3" placeholder="Edad">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Nivel de Estudio</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="estudioshijo3" placeholder="Nivel de Estudio">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="nueva">Tiene alguna Beca?</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="becahijo3">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                          <option value="SI">SI</option>
                                          <option value="NO">NO</option>
                                      </select>
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ocupación</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionhijo3" placeholder="Ocupacion">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ingreso Neto Mensual</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresohijo3" placeholder="Ingreso">
                                  </div>
                              </div>
                            </div>
                            
                            <div id="infohijo4" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                <label for="solicitante">Información de Hijo</label>
                              </div>
                              <div class="form-group col-sm-6">
                                <label for="promedio">Nombre Completo</label>
                              </div>
                              <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="nombrehijo4" placeholder="Nombre">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1hijo4" placeholder="Apellido Paterno">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2hijo4" placeholder="Apellido Materno">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Edad</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="edadhijo4" placeholder="Edad">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Nivel de Estudio</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="estudioshijo4" placeholder="Nivel de Estudio">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="nueva">Tiene alguna Beca?</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="becahijo4">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                          <option value="SI">SI</option>
                                          <option value="NO">NO</option>
                                      </select>
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ocupación</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionhijo4" placeholder="Ocupacion">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ingreso Neto Mensual</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresohijo4" placeholder="Ingreso">
                                  </div>
                              </div>
                            </div>
                            
                            <div id="infohijo5" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                <label for="solicitante">Información de Hijo</label>
                              </div>
                              <div class="form-group col-sm-6">
                                <label for="promedio">Nombre Completo</label>
                              </div>
                              <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="nombrehijo5" placeholder="Nombre">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1hijo5" placeholder="Apellido Paterno">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2hijo5" placeholder="Apellido Materno">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Edad</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="edadhijo5" placeholder="Edad">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Nivel de Estudio</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="estudioshijo5" placeholder="Nivel de Estudio">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="nueva">Tiene alguna Beca?</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="becahijo5">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                          <option value="SI">SI</option>
                                          <option value="NO">NO</option>
                                      </select>
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ocupación</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionhijo5" placeholder="Ocupacion">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ingreso Neto Mensual</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresohijo5" placeholder="Ingreso">
                                  </div>
                              </div>
                            </div>
                        </div>
                        <!--------------------------------------------------------------------------------->
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">Otros familiares</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="otros" onclick="mostrar7()">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="SI">SI</option>
                                    <option value="NO">NO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="infootros" style="display: none;" class="form-group col-sm-12">
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarfam(1)">Registro 1</button>
                            </div>
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarfam(2)">Registro 2</button>
                            </div>
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarfam(3)">Registro 3</button>
                            </div>
                            
                            <div id="infofam1" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                <label for="solicitante">Información de Familiar</label>
                              </div>
                              
                              <div class="form-group col-sm-6">
                                <label for="nueva">Parentesco</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="tipofam1">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                            <option value="ABUELO">ABUELO</option>
                                            <option value="HERMANO">HERMANO</option>
                                            <option value="PRIMO">PRIMO</option>
                                            <option value="SOBRINO">SOBRINO</option>
                                            <option value="TIO">TIO</option>
                                      </select>
                                  </div>
                              </div>
                                
                              <div class="form-group col-sm-6">
                                <label for="promedio">Nombre Completo</label>
                              </div>
                              <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="nombrefam1" placeholder="Nombre">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1fam1" placeholder="Apellido Paterno">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2fam1" placeholder="Apellido Materno">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Edad</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="edadfam1" placeholder="Edad">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Nivel de Estudio</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="estudiosfam1" placeholder="Nivel de Estudio">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="nueva">Tiene alguna Beca?</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="becafam1">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                          <option value="SI">SI</option>
                                          <option value="NO">NO</option>
                                      </select>
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ocupación</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionfam1" placeholder="Ocupacion">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ingreso Neto Mensual</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresofam1" placeholder="Ingreso">
                                  </div>
                              </div>
                            </div>
                            
                            <div id="infofam2" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                <label for="solicitante">Información de Familiar</label>
                              </div>
                              
                              <div class="form-group col-sm-6">
                                <label for="nueva">Parentesco</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="tipofam2">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                            <option value="ABUELO">ABUELO</option>
                                            <option value="HERMANO">HERMANO</option>
                                            <option value="PRIMO">PRIMO</option>
                                            <option value="SOBRINO">SOBRINO</option>
                                            <option value="TIO">TIO</option>
                                      </select>
                                  </div>
                              </div>
                                
                              <div class="form-group col-sm-6">
                                <label for="promedio">Nombre Completo</label>
                              </div>
                              <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="nombrefam2" placeholder="Nombre">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1fam2" placeholder="Apellido Paterno">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2fam2" placeholder="Apellido Materno">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Edad</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="edadfam2" placeholder="Edad">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Nivel de Estudio</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="estudiosfam2" placeholder="Nivel de Estudio">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="nueva">Tiene alguna Beca?</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="becafam2">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                          <option value="SI">SI</option>
                                          <option value="NO">NO</option>
                                      </select>
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ocupación</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionfam2" placeholder="Ocupacion">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ingreso Neto Mensual</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresofam2" placeholder="Ingreso">
                                  </div>
                              </div>
                            </div>
                            
                            <div id="infofam3" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                <label for="solicitante">Información de Familiar</label>
                              </div>
                              
                              <div class="form-group col-sm-6">
                                <label for="nueva">Parentesco</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="tipofam3">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                            <option value="ABUELO">ABUELO</option>
                                            <option value="HERMANO">HERMANO</option>
                                            <option value="PRIMO">PRIMO</option>
                                            <option value="SOBRINO">SOBRINO</option>
                                            <option value="TIO">TIO</option>
                                      </select>
                                  </div>
                              </div>
                                
                              <div class="form-group col-sm-6">
                                <label for="promedio">Nombre Completo</label>
                              </div>
                              <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="nombrefam3" placeholder="Nombre">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap1fam3" placeholder="Apellido Paterno">
                                  </div>
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ap2fam3" placeholder="Apellido Materno">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Edad</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="edadfam3" placeholder="Edad">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Nivel de Estudio</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="estudiosfam3" placeholder="Nivel de Estudio">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="nueva">Tiene alguna Beca?</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <select class="form-control" id="becafam3">
                                          <option value="0" selected="selected">Seleccionar...</option>
                                          <option value="SI">SI</option>
                                          <option value="NO">NO</option>
                                      </select>
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ocupación</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-12">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="ocupacionfam3" placeholder="Ocupacion">
                                  </div>
                              </div>

                              <div class="form-group col-sm-6">
                                <label for="promedio">Ingreso Neto Mensual</label>
                              </div>
                              <div class="form-group col-sm-6">
                                  <div class="form-group col-sm-6">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="ingresofam3" placeholder="Ingreso">
                                  </div>
                              </div>
                            </div>
                        </div>
                        <!--------------------------------------------------------------------------------->
                        
                        <div class="form-group col-sm-12" style="text-align: right;">
                            <button class="btn btn-lg btn-success" id="validar" onclick="guardasec2f()">Finalizar Seccion 2</button>
                        </div>
                    </div>
                    <!--Fin Seccion 4-->
                    
                    <!--Inicio Seccion 3 Informacion Financiera-->
                    <legend id="t5" style="display: none;">Seccion 3: Información Financiera</legend>
                    <legend id="t51" style="display: none;">Seccion 3: Información Financiera - Completa</legend>
                    <div id="sec3" style="display: none;">
                        <div class="form-group col-sm-6">
                            <label for="promedio">Ingreso neto mensual de la Familia</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="ingneto" placeholder="Ingreso">
                            </div>
                        </div>

                        <div class="form-group col-sm-6">
                            <label for="promedio">Otros ingresos</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="otrosing" placeholder="Otros Ingreso">
                            </div>
                        </div>

                        <div class="form-group col-sm-6">
                            <label for="promedio">Ingreso neto Total</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="netotal" placeholder="Ingreso Total">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Aguinaldo</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="aguinaldo" placeholder="Aguinaldo">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Reparto de Utilidades</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="utilidades" placeholder="Utilidades">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Prestaciones sociales</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-12">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="prestaciones" placeholder="Prestaciones">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                          <label for="nueva">Tipo de Vivienda</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <select class="form-control" id="tipov" onclick="mostrar8()">
                                    <option value="0" selected="selected">Seleccionar...</option>
                                    <option value="PROPIA">PROPIA</option>
                                    <option value="RENTADA">RENTADA</option>
                                    <option value="OTROS">OTROS</option>
                                </select>
                            </div>
                        </div>
                        
                            <div id="renta1" style="display: none;" class="form-group col-sm-6">
                                <label for="promedio">Renta</label>
                            </div>
                            <div id="renta2" style="display: none;" class="form-group col-sm-6">
                                <div class="form-group col-sm-6">
                                    <input type="number" class="form-control" style="text-transform:uppercase;" id="rentaprecio" placeholder="$ renta">
                                </div>
                            </div>

                            <div id="otrav2" style="display: none;" class="form-group col-sm-6">
                                <label for="promedio">Especificar</label>
                            </div>
                            <div id="otrav2" style="display: none;" class="form-group col-sm-6">
                                <div class="form-group col-sm-6">
                                    <input type="text" class="form-control" style="text-transform:uppercase;" id="otracasa" placeholder="renta">
                                </div>
                            </div>
                        
                        <div class="form-group col-sm-6">
                                <label for="promedio">Cuenta con algun tipo de bienes raices, especifique</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-12">
                                <input type="text" class="form-control" style="text-transform:uppercase;" id="bienesraices" placeholder="Bienes raices">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Cuantos automoviles poseé</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="autos" placeholder="Automoviles">
                            </div>
                        </div>
                        <!--------------------------------------------------------------------------------->
                        <div class="form-group col-sm-12">
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarauto(1)">Automovil 1</button>
                            </div>
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarauto(2)">Automovil 2</button>
                            </div>
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarauto(3)">Automovil 3</button>
                            </div>
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarauto(4)">Automovil 4</button>
                            </div>
                            <div class="form-group col-sm-2">
                                <button class="btn btn-lg btn-success" onclick="mostrarauto(5)">Automovil 5</button>
                            </div>
                            
                            <div id="infoauto1" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                    <label for="solicitante">Especificación de Automoviles</label>
                                </div>
                              
                                <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="marcaauto1" placeholder="Marca">
                                  </div>
                                  
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="modeloauto1" placeholder="Modelo">
                                  </div>
                                  
                                  <div class="form-group col-sm-3">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="valorauto1" placeholder="Valor Comercial">
                                  </div>
                                    
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="tipoauto1" placeholder="propio, empresa, familiar">
                                  </div>
                                    
                                  <div class="form-group col-sm-3">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="pagoauto1" placeholder="pago mensual">
                                  </div>
                                </div>
                            </div>
                            
                            <div id="infoauto2" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                    <label for="solicitante">Especificación de Automoviles</label>
                                </div>
                              
                                <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="marcaauto2" placeholder="Marca">
                                  </div>
                                  
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="modeloauto2" placeholder="Modelo">
                                  </div>
                                  
                                  <div class="form-group col-sm-3">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="valorauto2" placeholder="Valor Comercial">
                                  </div>
                                    
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="tipoauto2" placeholder="propio, empresa, familiar">
                                  </div>
                                    
                                  <div class="form-group col-sm-3">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="pagoauto2" placeholder="pago mensual">
                                  </div>
                                </div>
                            </div>
                            
                            <div id="infoauto3" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                    <label for="solicitante">Especificación de Automoviles</label>
                                </div>
                              
                                <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="marcaauto3" placeholder="Marca">
                                  </div>
                                  
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="modeloauto3" placeholder="Modelo">
                                  </div>
                                  
                                  <div class="form-group col-sm-3">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="valorauto3" placeholder="Valor Comercial">
                                  </div>
                                    
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="tipoauto3" placeholder="propio, empresa, familiar">
                                  </div>
                                    
                                  <div class="form-group col-sm-3">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="pagoauto3" placeholder="pago mensual">
                                  </div>
                                </div>
                            </div>
                            
                            <div id="infoauto4" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                    <label for="solicitante">Especificación de Automoviles</label>
                                </div>
                              
                                <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="marcaauto4" placeholder="Marca">
                                  </div>
                                  
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="modeloauto4" placeholder="Modelo">
                                  </div>
                                  
                                  <div class="form-group col-sm-3">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="valorauto4" placeholder="Valor Comercial">
                                  </div>
                                    
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="tipoauto4" placeholder="propio, empresa, familiar">
                                  </div>
                                    
                                  <div class="form-group col-sm-3">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="pagoauto4" placeholder="pago mensual">
                                  </div>
                                </div>
                            </div>
                            
                            <div id="infoauto5" style="display: none;" class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                    <label for="solicitante">Especificación de Automoviles</label>
                                </div>
                              
                                <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="marcaauto5" placeholder="Marca">
                                  </div>
                                  
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="modeloauto5" placeholder="Modelo">
                                  </div>
                                  
                                  <div class="form-group col-sm-3">
                                      <input type="number" class="form-control" style="text-transform:uppercase;" id="valorauto5" placeholder="Valor Comercial">
                                  </div>
                                    
                                  <div class="form-group col-sm-6">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="tipoauto5" placeholder="propio, empresa, familiar">
                                  </div>
                                    
                                  <div class="form-group col-sm-3">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="pagoauto5" placeholder="pago mensual">
                                  </div>
                                </div>
                            </div>
                        </div>
                        <!--------------------------------------------------------------------------------->
                        
                        <div class="form-group col-sm-12">
                            <label for="promedio">Adeudos de la Familia</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <label for="promedio">Saldo deudor</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="adeudos" placeholder="Adeudo">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Pago mensual</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="pagodeuda" placeholder="Pago">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Bancos con los que tiene cuenta</label>
                        </div>
                        <div class="form-group col-sm-12">
                            <div class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                    <label for="solicitante">Nombre del banco</label>
                                </div>
                              
                                <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="banco1" placeholder="Nombre del banco">
                                  </div>
                                  
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="banco2" placeholder="Nombre del banco">
                                  </div>
                                  
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="banco3" placeholder="Nombre del banco">
                                  </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Tarjetas de Crédito</label>
                        </div>
                        <div class="form-group col-sm-12">
                            <div class="form-group col-sm-12">
                                <div class="form-group col-sm-12">
                                    <label for="solicitante">Nombre del banco de la tarjeta de crédito</label>
                                </div>
                              
                                <div class="form-group col-sm-12">
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="tarjeta1" placeholder="Nombre del banco">
                                  </div>
                                  
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="tarjeta2" placeholder="Nombre del banco">
                                  </div>
                                  
                                  <div class="form-group col-sm-4">
                                      <input type="text" class="form-control" style="text-transform:uppercase;" id="tarjeta3" placeholder="Nombre del banco">
                                  </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-12">
                            <label for="promedio">Gasto mensual familiar</label>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Vivienda</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="vivienda" placeholder="Vivienda">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Comida</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="comida" placeholder="Comida">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Lavanderia</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="lavanderia" placeholder="Lavanderia">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Gasolina</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="gasolina" placeholder="Gasolina">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Luz</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="luz" placeholder="Luz">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Servidumbre</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="servidumbre" placeholder="Servidumbre">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Transporte</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="transporte" placeholder="Transporte">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Teléfono</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="telefonogasto" placeholder="Teléfono">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Clubes</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="clubes" placeholder="Clubes">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Colegiaturas</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="colegiaturas" placeholder="Colegiaturas">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Gastos médicos</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="medico" placeholder="Gastos médicos">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Gas y Agua</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="gasagua" placeholder="Gas y Agua">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Cable/Internet</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="cableinternet" placeholder="Cable/Internet">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Otros</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="otrosgastos" placeholder="Otros">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-6">
                            <label for="promedio">Total</label>
                        </div>
                        <div class="form-group col-sm-6">
                            <div class="form-group col-sm-6">
                                <input type="number" class="form-control" style="text-transform:uppercase;" id="totalgastos" placeholder="Total">
                            </div>
                        </div>
                        
                        <div class="form-group col-sm-12" style="text-align: right;">
                            <button class="btn btn-lg btn-success" id="validar" onclick="guardasec3()">Finalizar</button>
                        </div>
                    </div>
                    <!--Fin Seccion 5-->
                    
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
    var folio     = '<?=$folio?>';
    var peopleid  = '<?=$idpc?>';
    
    $(document).ready(function () 
    {
        $("#nombre").focus();
        
        //Evitar que el modal se cierre al dar clic fuera de el
        $('#mensajes').modal({backdrop: 'static', keyboard: false});
            
        //Mensaje indicando el Procedimiento para llenar el formulario
        $('#mensajes').modal('show');
        $("#msj").empty();
        $("#msj").append('<div class="modal-header">'
                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                +'<span aria-hidden="true">&times;</span>'
                            +'</button>'
                        +'</div>'
                        +'<div class="modal-body">'
                            +'<div class="alert alert-info" role="alert">'
                                +'<p>El formulario consta de 3 secciones.</p>'
                                +'<p>Llene correctamente todas las secciones.</p>'
                                +'<p>Este formulario debe completarse en de una sola vez, si llegase a cerrar sesión, su información no se será almacenada correctamente.</p>'
                                +'<p>NO CIERRE SESIÓN HASTA TERMINAR EL FORMULARIO,en caso de cierre de sesion comunicarse al departamento de Programación ext. 734.</p>'
                                +'<p>Verifique correctamente toda la información antes de guardarla.</p>'
                                +'<p>Le solicitamos NO utilizar acentos, ya que todo el texto sera ingresado en MAYUSCULAS.</p>'
                                +'<p>Al finalizar será enviado a la seccion de documentos.</p>'
                            +'</div>'
                        +'</div>');
    });
    
    function mostrar(){
        var nueva       = $("#nueva").val();
        if(nueva == 'SI'){
            $("#ren1,#ren2").css("display", "none");
            $("#anterior").val('');
        }
        else if(nueva == 'NO'){
            $("#ren1,#ren2").css("display", "block");
        }
        else{
            $("#ren1,#ren2").css("display", "none");
            $("#anterior").val('');
        }
    }
    
    /*Validar la informacion de la seccion 1 y guardarla*/
    function guardasec1(){
        var nombre      = $.trim($("#nombre").val());
        nombre          = nombre.toUpperCase();
        var ap1         = $.trim($("#ap1").val());
        ap1             = ap1.toUpperCase();
        var ap2         = $.trim($("#ap2").val());
        ap2             = ap2.toUpperCase();
        var grado       = $("#grado").val();
        var nivel       = $("#nivel").val();
        var promedio    = $.trim($("#promedio").val());
        var nueva       = $("#nueva").val();
        var anterior    = $.trim($("#anterior").val());
        var motivo      = $.trim($("#motivo").val());
        
        if(nombre == ''){ msg('Debes ingresar tu Nombre'); }
        else if(ap1 == ''){ msg('Debes ingresar tu Apellido Paterno'); }
        else if(ap2 == ''){ msg('Debes ingresar tu Apellido Materno'); }
        else if(grado == 0){ msg('Debes seleccionar tu Grado Educativo'); }
        else if(nivel == 0){ msg('Debes seleccionar tu Nivel Educativo'); }
        else if(promedio == ''){ msg('Debes ingresar tu Promedio'); }
        else if(nueva == 0){ msg('Debes seleccionar es Nueva Beca o No'); }
        else if(nueva == 'NO' && anterior == ''){ msg('Debes ingresar el Porcentaje de Beca Anterior'); }
        else if(motivo == ''){ msg('Debes ingresar el Motivo por el cual solicitas Beca'); }
        else{ var todook = 1; }
        
        if(todook == 1){
            $.ajax({
                type: 'POST',
                data: 
                {
                    accion      : 'sec1',
                    folio       : folio,
                    peopleid    : peopleid,
                    nombre      : nombre,
                    ap1         : ap1,
                    ap2         : ap2,
                    grado       : grado,
                    nivel       : nivel,
                    promedio    : promedio,
                    nueva       : nueva,
                    anterior    : anterior,
                    motivo      : motivo
                },
                dataType: 'json',
                url:  'actions.php',
                success:  function(response)
                {   
                    var status  = response[0];
                    
                    if(status == 'ok')
                    {
                        $("#sec1").css("display", "none");
                        $("#t1").css("display", "none");
                        $("#t12").css("display", "block");
                        
                        $("#t2").css("display", "block");
                        $("#sec2").css("display", "block");
                        
                        msg('Información Almacenada Correctamente');
                    }
                    else
                    {
                        msgerror('Lo sentimos, ocurrio un problema con la solicitud, vuelve a intentarlo');
                    }
                }
            });  
        }
    }
    
    function mostrar2(){
        var bantes       = $("#bantes").val();
        if(bantes == 'SI'){ $("#ban1,#ban2").css("display", "block");}
        else if(bantes == 'NO'){ $("#ban1,#ban2").css("display", "none"); $("#cuando").val(''); }
        else{ $("#ban1,#ban2").css("display", "none"); $("#cuando").val(''); }
    }
    
    function mostrar3(){
        var discapacitado       = $("#discapacitado").val();
        if(discapacitado == 'SI'){ $("#dis1,#dis2").css("display", "block"); }
        else if(discapacitado == 'NO'){ $("#dis1,#dis2").css("display", "none"); $('#discapacidad').prop('selectedIndex',0); }
        else{ $("#dis1,#dis2").css("display", "none"); $('#discapacidad').prop('selectedIndex',0); }
    }
    
    /*Validar la informacion de la seccion 2 y guardarla*/
    function guardasec2(){
        var edad            = $.trim($("#edad").val());
        var genero          = $("#genero").val();
        var curp            = $.trim($("#curp").val());
        curp                = curp.toUpperCase();
        var telefono        = $.trim($("#telefono").val());
        var calle           = $.trim($("#calle").val());
        calle               = calle.toUpperCase();
        var num1            = $.trim($("#num1").val());
        var num2            = $.trim($("#num2").val());
        var colonia         = $.trim($("#colonia").val());
        colonia             = colonia.toUpperCase();
        var cp              = $.trim($("#cp").val());
        var localidad       = $.trim($("#localidad").val());
        localidad           = localidad.toUpperCase();
        var municipio       = $.trim($("#municipio").val());
        municipio           = municipio.toUpperCase();
        var residencia      = $.trim($("#residencia").val());
        residencia          = residencia.toUpperCase();
        var procedencia     = $.trim($("#procedencia").val());
        procedencia         = procedencia.toUpperCase();
        var bantes          = $("#bantes").val();
        var cuando          = $.trim($("#cuando").val());
        cuando              = cuando.toUpperCase();
        var ingreso         = $.trim($("#ingreso").val());
        var discapacitado   = $("#discapacitado").val();
        var discapacidad    = $("#discapacidad").val();
        var dependiente     = $("#dependiente").val();
        var padre           = $("#padre").val();
        var madre           = $("#madre").val();
        var situacion       = $("#situacion").val();
        var depende         = $.trim($("#depende").val());
        var hijos           = $.trim($("#hijos").val());
        var estudian        = $.trim($("#estudian").val());
        
        if(edad == ''){ msg('Debes ingresar tu Edad'); }
        else if(genero == 0){ msg('Debes seleccionar Genero'); }
        else if(curp == ''){ msg('Debes ingresar tu CURP o NIA en caso de ser de Bachillerato'); }
        else if(telefono == ''){ msg('Debes ingresar tu Teléfono'); }
        else if(calle == ''){ msg('Debes ingresar tu Calle'); }
        else if(num1 == ''){ msg('Debes ingresar al menos el Número Exterior'); }
        else if(colonia == ''){ msg('Debes ingresar tu Colobia'); }
        else if(cp == ''){ msg('Debes ingresar tu Codigo Postal'); }
        else if(localidad == ''){ msg('Debes ingresar tu Localidad'); }
        else if(municipio == ''){ msg('Debes ingresar tu Municipio'); }
        else if(residencia == ''){ msg('Debes ingresar tu Estado de Residencia'); }
        else if(procedencia == ''){ msg('Debes ingresar tu Estado de Procedencia'); }
        else if(bantes == 0){ msg('Debes seleccionar si ya habia solicitado beca anteriormente'); }
        else if(bantes == 'SI' && cuando == ''){ msg('Debes ingresar cuando solicitaste Beca anteriormente'); }
        else if(ingreso == ''){ msg('Debes ingresar el Año de ingreso a la Institución'); }
        else if(discapacitado == 0){ msg('Debes seleccionar si tiene una Discapacidad o no'); }
        else if(discapacitado == 'SI' && discapacidad == 0){ msg('Debes seleccionar el tipo de Discapacidad'); }
        else if(dependiente == 0){ msg('Debes seleccionar si depende de sus padres'); }
        else if(padre == 0){ msg('Vive su Padre?'); }
        else if(madre == 0){ msg('Vive su Madre?'); }
        else if(situacion == 0){ msg('Debes seleccionar la situación en la cual estan sus padres'); }
        else if(depende == ''){ msg('Debes ingresar cuantas personas dependen del Tutor'); }
        else if(hijos == ''){ msg('Debes ingresar cuantos Hijos tiene el Tutor'); }
        else if(estudian == ''){ msg('Debes ingresar cuantas hijos Estudian');  }
        else{ var todook = 1; }
        
        if(todook == 1){
            $.ajax({
                type: 'POST',
                data: 
                {
                    accion          : 'sec2',
                    folio           : folio,
                    peopleid        : peopleid,
                    edad            : edad,
                    genero          : genero,
                    curp            : curp,
                    telefono        : telefono,
                    calle           : calle,
                    num1            : num1,
                    num2            : num2,
                    colonia         : colonia,
                    cp              : cp,
                    localidad       : localidad,
                    municipio       : municipio,
                    residencia      : residencia,
                    procedencia     : procedencia,
                    bantes          : bantes,
                    cuando          : cuando,
                    ingreso         : ingreso,
                    discapacitado   : discapacitado,
                    discapacidad    : discapacidad,
                    dependiente     : dependiente,
                    padre           : padre,
                    madre           : madre,
                    situacion       : situacion,
                    depende         : depende,
                    hijos           : hijos,
                    estudian        : estudian
                },
                dataType: 'json',
                url:  'actions.php',
                success:  function(response)
                {   
                    var status  = response[0];
                    
                    if(status == 'ok')
                    {
                        $("#sec1").css("display", "none");
                        $("#t1").css("display", "none");
                        $("#t12").css("display", "block");
                        
                        $("#sec2").css("display", "none");
                        $("#t2").css("display", "none");
                        $("#t22").css("display", "block");
                        
                        $("#sec21").css("display", "block");
                        $("#t3").css("display", "block");
                        $("#t32").css("display", "none");
                        
                        msg('Información Almacenada Correctamente');
                    }
                    else
                    {
                        msgerror('Lo sentimos, ocurrio un problema con la solicitud, vuelve a intentarlo');
                    }
                }
            });  
        }
    }
    
    function mostrar4(){
        var tutor = $("#tutor").val();
        if(tutor == 'NO'){ $("#infotutor").css("display", "block"); }
        else if(tutor == 'SI'){ $("#infotutor").css("display", "none"); 
            $("#nombretutor").val('');
            $("#ap1tutor").val('');
            $("#ap2tutor").val('');
            $("#edadtutor").val('');
            $("#estudiostutor").val('');
            $("#becatutor").val('');
            $("#ocupaciontutor").val('');
            $("#ingresotutor").val('');
        }
        else{ $("#infotutor").css("display", "none"); 
            $("#nombretutor").val('');
            $("#ap1tutor").val('');
            $("#ap2tutor").val('');
            $("#edadtutor").val('');
            $("#estudiostutor").val('');
            $("#becatutor").val('');
            $("#ocupaciontutor").val('');
            $("#ingresotutor").val('');
        }
    }
    
    /*Validar la informacion de la seccion 2 Padre Madre Tutor*/
    function guardasec2t(){
        var nombrepadre     = $.trim($("#nombrepadre").val());
        var ap1padre        = $.trim($("#ap1padre").val());
        var ap2padre        = $.trim($("#ap2padre").val());
        var edadpadre       = $.trim($("#edadpadre").val());
        var estudiospadre   = $.trim($("#estudiospadre").val());
        var becapadre       = $.trim($("#becapadre").val());
        var ocupacionpadre  = $.trim($("#ocupacionpadre").val());
        var ingresopadre    = $.trim($("#ingresopadre").val());
        
        nombrepadre        = nombrepadre.toUpperCase();
        ap1padre           = ap1padre.toUpperCase();
        ap2padre           = ap2padre.toUpperCase();
        estudiospadre      = estudiospadre.toUpperCase();
        ocupacionpadre     = ocupacionpadre.toUpperCase();
        
        var nombremadre     = $.trim($("#nombremadre").val());
        var ap1madre        = $.trim($("#ap1madre").val());
        var ap2madre        = $.trim($("#ap2madre").val());
        var edadmadre       = $.trim($("#edadmadre").val());
        var estudiosmadre   = $.trim($("#estudiosmadre").val());
        var becamadre       = $.trim($("#becamadre").val());
        var ocupacionmadre  = $.trim($("#ocupacionmadre").val());
        var ingresomadre    = $.trim($("#ingresomadre").val());
        
        nombremadre        = nombremadre.toUpperCase();
        ap1madre           = ap1madre.toUpperCase();
        ap2madre           = ap2madre.toUpperCase();
        estudiosmadre      = estudiosmadre.toUpperCase();
        ocupacionmadre     = ocupacionmadre.toUpperCase();
        
        var tutor           = $.trim($("#tutor").val());
        var generotutor     = $.trim($("#generotutor").val());
        var nombretutor     = $.trim($("#nombretutor").val());
        var ap1tutor        = $.trim($("#ap1tutor").val());
        var ap2tutor        = $.trim($("#ap2tutor").val());
        var edadtutor       = $.trim($("#edadtutor").val());
        var estudiostutor   = $.trim($("#estudiostutor").val());
        var becatutor       = $.trim($("#becatutor").val());
        var ocupaciontutor  = $.trim($("#ocupaciontutor").val());
        var ingresotutor    = $.trim($("#ingresotutor").val());
        
        nombretutor        = nombretutor.toUpperCase();
        ap1tutor           = ap1tutor.toUpperCase();
        ap2tutor           = ap2tutor.toUpperCase();
        estudiostutor      = estudiostutor.toUpperCase();
        ocupaciontutor     = ocupaciontutor.toUpperCase();
        
        //alert(nombrepadre+'-->'+ap1padre+'-->'+ap2padre+'-->'+edadpadre+'-->'+estudiospadre+'-->'+becapadre+'-->'+ocupacionpadre+'-->'+ingresopadre+'-->'+nombremadre+'-->'+ap1madre+'-->'+ap2madre+'-->'+edadmadre+'-->'+estudiosmadre+'-->'+becamadre+'-->'+ocupacionmadre+'-->'+ingresomadre+'-->'+tutor+'-->'+nombretutor+'-->'+ap1tutor+'-->'+ap2tutor+'-->'+edadtutor+'-->'+estudiostutor+'-->'+becatutor+'-->'+ocupaciontutor+'-->'+ingresotutor);
        
        if(nombrepadre != ''){ 
            if(ap1padre == ''){ msg('Debes ingresar el Apellido Paterno del Padre'); }
            else if(ap2padre == ''){ msg('Debes ingresar Apellido Materno del Padre'); }
            else if(edadpadre == ''){ msg('Debes ingresar la edad del Padre'); }
            else if(estudiospadre == ''){ msg('Debes ingresar el nivel de estudios del Padre'); }
            else if(becapadre == 0){ msg('Debes Seleccionar si el Padre tiene alguna Beca'); }
            else if(ocupacionpadre == ''){ msg('Debes ingresar la Ocupación del Padre'); }
            else if(ingresopadre == ''){ msg('Debes colocar el ingreso neto mensual del Padre'); } 
            else{ var todook1 = 1; }
        }
        
        
        if(nombremadre != ''){ 
            if(ap1madre == ''){ msg('Debes ingresar el Apellido Paterno de la Madre'); }
            else if(ap2madre == ''){ msg('Debes ingresar Apellido Materno de la Madre'); }
            else if(edadmadre == ''){ msg('Debes ingresar la edad de la Madre'); }
            else if(estudiosmadre == ''){ msg('Debes ingresar el nivel de estudios de la Madre'); }
            else if(becamadre == 0){ msg('Debes Seleccionar si la Madre tiene alguna Beca'); }
            else if(ocupacionmadre == ''){ msg('Debes ingresar la Ocupación de la Madre'); }
            else if(ingresomadre == ''){ msg('Debes colocar el ingreso neto mensual de la Madre'); }
            else{ var todook2 = 1; }
        }
        
        if(tutor == 'NO'){
            if(generotutor == 0){ msg('Debes seleccionar el genero del Tutor'); }
            else if(nombretutor == ''){ msg('Debes ingresar el Apellido Paterno del Tutor'); }
            else if(ap1tutor == ''){ msg('Debes ingresar el Apellido Paterno del Tutor'); }
            else if(ap2tutor == ''){ msg('Debes ingresar Apellido Materno del Tutor'); }
            else if(edadtutor == ''){ msg('Debes ingresar la edad del Tutor'); }
            else if(estudiostutor == ''){ msg('Debes ingresar el nivel de estudios del Tutor'); }
            else if(becatutor == 0){ msg('Debes Seleccionar si el Tutor tiene alguna Beca'); }
            else if(ocupaciontutor == ''){ msg('Debes ingresar la Ocupación del Tutor'); }
            else if(ingresotutor == ''){ msg('Debes colocar el ingreso neto mensual del Tutor'); }
            else{var todook3 = 1; }
        }
        
        if((todook1 == 1) || (todook2 == 1) || (todook3 == 1)){var todook = 1;}
        
        if(todook == 1){
            $.ajax({
                type: 'POST',
                data: 
                {
                    accion          : 'sec22',
                    folio           : folio,
                    peopleid        : peopleid,
                    
                    nombrepadre     : nombrepadre,
                    ap1padre        : ap1padre,
                    ap2padre        : ap2padre,
                    edadpadre       : edadpadre,
                    estudiospadre   : estudiospadre,
                    becapadre       : becapadre,
                    ocupacionpadre  : ocupacionpadre,
                    ingresopadre    : ingresopadre,
                    
                    nombremadre     : nombremadre,
                    ap1madre        : ap1madre,
                    ap2madre        : ap2madre,
                    edadmadre       : edadmadre,
                    estudiosmadre   : estudiosmadre,
                    becamadre       : becamadre,
                    ocupacionmadre  : ocupacionmadre,
                    ingresomadre    : ingresomadre,
                    
                    tutor           : tutor,
                    generotutor     : generotutor,
                    nombretutor     : nombretutor,
                    ap1tutor        : ap1tutor,
                    ap2tutor        : ap2tutor,
                    edadtutor       : edadtutor,
                    estudiostutor   : estudiostutor,
                    becatutor       : becatutor,
                    ocupaciontutor  : ocupaciontutor,
                    ingresotutor    : ingresotutor
                    
                },
                dataType: 'json',
                url:  'actions.php',
                success:  function(response)
                {   
                    var status  = response[0];
                    
                    if(status == 'ok')
                    {
                        $("#sec1").css("display", "none");
                        $("#t1").css("display", "none");
                        $("#t12").css("display", "block");
                        
                        $("#sec2").css("display", "none");
                        $("#t2").css("display", "none");
                        $("#t22").css("display", "block");
                        
                        $("#sec21").css("display", "none");
                        $("#t3").css("display", "none");
                        $("#t32").css("display", "block");
                        
                        $("#sec22").css("display", "block");
                        $("#t4").css("display", "block");
                        $("#t42").css("display", "none");
                        
                        msg('Información Almacenada Correctamente');
                    }
                    else
                    {
                        msgerror('Lo sentimos, ocurrio un problema con la solicitud, vuelve a intentarlo');
                    }
                }
            });  
        }
    }
    
    function mostrar5(){
        var conyuge = $("#conyuge").val();
        if(conyuge == 'SI'){ $("#infoconyuge").css("display", "block"); }
        else if(conyuge == 'NO'){ $("#infoconyuge").css("display", "none"); 
        }
        else{ $("#infoconyuge").css("display", "none"); 
        }
    }
    
    function mostrar6(){
        var verhijos = $("#verhijos").val();
        if(verhijos == 'SI'){ $("#infohijos").css("display", "block"); }
        else if(verhijos == 'NO'){ $("#infohijos").css("display", "none"); 
        }
        else{ $("#infohijos").css("display", "none"); 
        }
    }
    
    function mostrarhijo(num){
        for(var i = 1; i <= 5; i++){
           if(i == num){
               $("#infohijo"+i).css("display", "block");
           }else{
               $("#infohijo"+i).css("display", "none");
           }
        }
    }
    
    function mostrar7(){
        var otros = $("#otros").val();
        if(otros == 'SI'){ $("#infootros").css("display", "block"); }
        else if(otros == 'NO'){ $("#infootros").css("display", "none"); 
        }
        else{ $("#infootros").css("display", "none"); 
        }
    }
    
    function mostrarfam(num){
        for(var i = 1; i <= 3; i++){
           if(i == num){
               $("#infofam"+i).css("display", "block");
           }else{
               $("#infofam"+i).css("display", "none");
           }
        }
    }

    function guardasec2f(){
        var conyuge         = $.trim($("#conyuge").val());
        var nombreconyuge     = $.trim($("#nombreconyuge").val());
        var ap1conyuge        = $.trim($("#ap1conyuge").val());
        var ap2conyuge        = $.trim($("#ap2conyuge").val());
        var edadconyuge       = $.trim($("#edadconyuge").val());
        var estudiosconyuge   = $.trim($("#estudiosconyuge").val());
        var becaconyuge       = $.trim($("#becaconyuge").val());
        var ocupacionconyuge  = $.trim($("#ocupacionconyuge").val());
        var ingresoconyuge    = $.trim($("#ingresoconyuge").val());
        
        nombreconyuge        = nombreconyuge.toUpperCase();
        ap1conyuge           = ap1conyuge.toUpperCase();
        ap2conyuge           = ap2conyuge.toUpperCase();
        estudiosconyuge      = estudiosconyuge.toUpperCase();
        ocupacionconyuge     = ocupacionconyuge.toUpperCase();
        
        var verhijos         = $.trim($("#verhijos").val());
        var nombrehijo1     = $.trim($("#nombrehijo1").val());
        var ap1hijo1        = $.trim($("#ap1hijo1").val());
        var ap2hijo1        = $.trim($("#ap2hijo1").val());
        var edadhijo1       = $.trim($("#edadhijo1").val());
        var estudioshijo1   = $.trim($("#estudioshijo1").val());
        var becahijo1       = $.trim($("#becahijo1").val());
        var ocupacionhijo1  = $.trim($("#ocupacionhijo1").val());
        var ingresohijo1    = $.trim($("#ingresohijo1").val());
        
        nombrehijo1        = nombrehijo1.toUpperCase();
        ap1hijo1           = ap1hijo1.toUpperCase();
        ap2hijo1           = ap2hijo1.toUpperCase();
        estudioshijo1      = estudioshijo1.toUpperCase();
        ocupacionhijo1     = ocupacionhijo1.toUpperCase();

        var nombrehijo2     = $.trim($("#nombrehijo2").val());
        var ap1hijo2        = $.trim($("#ap1hijo2").val());
        var ap2hijo2        = $.trim($("#ap2hijo2").val());
        var edadhijo2       = $.trim($("#edadhijo2").val());
        var estudioshijo2   = $.trim($("#estudioshijo2").val());
        var becahijo2       = $.trim($("#becahijo2").val());
        var ocupacionhijo2  = $.trim($("#ocupacionhijo2").val());
        var ingresohijo2    = $.trim($("#ingresohijo2").val());
        
        nombrehijo2        = nombrehijo2.toUpperCase();
        ap1hijo2           = ap1hijo2.toUpperCase();
        ap2hijo2           = ap2hijo2.toUpperCase();
        estudioshijo2      = estudioshijo2.toUpperCase();
        ocupacionhijo2    = ocupacionhijo2.toUpperCase();
        
        var nombrehijo3     = $.trim($("#nombrehijo3").val());
        var ap1hijo3        = $.trim($("#ap1hijo3").val());
        var ap2hijo3        = $.trim($("#ap2hijo3").val());
        var edadhijo3       = $.trim($("#edadhijo3").val());
        var estudioshijo3   = $.trim($("#estudioshijo3").val());
        var becahijo3       = $.trim($("#becahijo3").val());
        var ocupacionhijo3  = $.trim($("#ocupacionhijo3").val());
        var ingresohijo3    = $.trim($("#ingresohijo3").val());
        
        nombrehijo3        = nombrehijo3.toUpperCase();
        ap1hijo3           = ap1hijo3.toUpperCase();
        ap2hijo3           = ap2hijo3.toUpperCase();
        estudioshijo3      = estudioshijo3.toUpperCase();
        ocupacionhijo3    = ocupacionhijo3.toUpperCase();
        
        var nombrehijo4     = $.trim($("#nombrehijo4").val());
        var ap1hijo4        = $.trim($("#ap1hijo4").val());
        var ap2hijo4        = $.trim($("#ap2hijo4").val());
        var edadhijo4       = $.trim($("#edadhijo4").val());
        var estudioshijo4   = $.trim($("#estudioshijo4").val());
        var becahijo4       = $.trim($("#becahijo4").val());
        var ocupacionhijo4  = $.trim($("#ocupacionhijo4").val());
        var ingresohijo4    = $.trim($("#ingresohijo4").val());
        
        nombrehijo4        = nombrehijo4.toUpperCase();
        ap1hijo4           = ap1hijo4.toUpperCase();
        ap2hijo4           = ap2hijo4.toUpperCase();
        estudioshijo4      = estudioshijo4.toUpperCase();
        ocupacionhijo4    = ocupacionhijo4.toUpperCase();
        
        var nombrehijo5     = $.trim($("#nombrehijo5").val());
        var ap1hijo5        = $.trim($("#ap1hijo5").val());
        var ap2hijo5        = $.trim($("#ap2hijo5").val());
        var edadhijo5       = $.trim($("#edadhijo5").val());
        var estudioshijo5   = $.trim($("#estudioshijo5").val());
        var becahijo5       = $.trim($("#becahijo5").val());
        var ocupacionhijo5  = $.trim($("#ocupacionhijo5").val());
        var ingresohijo5    = $.trim($("#ingresohijo5").val());
        
        nombrehijo5        = nombrehijo5.toUpperCase();
        ap1hijo5           = ap1hijo5.toUpperCase();
        ap2hijo5           = ap2hijo5.toUpperCase();
        estudioshijo5      = estudioshijo5.toUpperCase();
        ocupacionhijo5    = ocupacionhijo5.toUpperCase();

        var tipofam1       = $.trim($("#tipofam1").val());
        var otros         = $.trim($("#otros").val());
        var nombrefam1     = $.trim($("#nombrefam1").val());
        var ap1fam1        = $.trim($("#ap1fam1").val());
        var ap2fam1        = $.trim($("#ap2fam1").val());
        var edadfam1       = $.trim($("#edadfam1").val());
        var estudiosfam1   = $.trim($("#estudiosfam1").val());
        var becafam1       = $.trim($("#becafam1").val());
        var ocupacionfam1  = $.trim($("#ocupacionfam1").val());
        var ingresofam1    = $.trim($("#ingresofam1").val());
        
        nombrefam1        = nombrefam1.toUpperCase();
        ap1fam1           = ap1fam1.toUpperCase();
        ap2fam1           = ap2fam1.toUpperCase();
        estudiosfam1      = estudiosfam1.toUpperCase();
        ocupacionfam1     = ocupacionfam1.toUpperCase();

        var tipofam2       = $.trim($("#tipofam2").val());
        var nombrefam2     = $.trim($("#nombrefam2").val());
        var ap1fam2        = $.trim($("#ap1fam2").val());
        var ap2fam2        = $.trim($("#ap2fam2").val());
        var edadfam2       = $.trim($("#edadfam2").val());
        var estudiosfam2   = $.trim($("#estudiosfam2").val());
        var becafam2       = $.trim($("#becafam2").val());
        var ocupacionfam2  = $.trim($("#ocupacionfam2").val());
        var ingresofam2    = $.trim($("#ingresofam2").val());
        
        nombrefam2        = nombrefam2.toUpperCase();
        ap1fam2           = ap1fam2.toUpperCase();
        ap2fam2           = ap2fam2.toUpperCase();
        estudiosfam2      = estudiosfam2.toUpperCase();
        ocupacionfam2    = ocupacionfam2.toUpperCase();
        
        var tipofam3       = $.trim($("#tipofam3").val());
        var nombrefam3     = $.trim($("#nombrefam3").val());
        var ap1fam3        = $.trim($("#ap1fam3").val());
        var ap2fam3        = $.trim($("#ap2fam3").val());
        var edadfam3       = $.trim($("#edadfam3").val());
        var estudiosfam3   = $.trim($("#estudiosfam3").val());
        var becafam3       = $.trim($("#becafam3").val());
        var ocupacionfam3  = $.trim($("#ocupacionfam3").val());
        var ingresofam3    = $.trim($("#ingresofam3").val());
        
        nombrefam3        = nombrefam3.toUpperCase();
        ap1fam3           = ap1fam3.toUpperCase();
        ap2fam3           = ap2fam3.toUpperCase();
        estudiosfam3      = estudiosfam3.toUpperCase();
        ocupacionfam3    = ocupacionfam3.toUpperCase();

        $.ajax({
                type: 'POST',
                data: 
                {
                    accion          : 'sec23',
                    folio           : folio,
                    peopleid        : peopleid,
                    
                    conyuge             : conyuge,
                    nombreconyuge       : nombreconyuge,
                    ap1conyuge          : ap1conyuge,
                    ap2conyuge          : ap2conyuge,
                    edadconyuge         : edadconyuge,
                    estudiosconyuge     : estudiosconyuge,
                    becaconyuge         : becaconyuge,
                    ocupacionconyuge    : ocupacionconyuge,
                    ingresoconyuge      : ingresoconyuge,
                    
                    verhijos            : verhijos,
                    nombrehijo1         : nombrehijo1,
                    ap1hijo1            : ap1hijo1,
                    ap2hijo1            : ap2hijo1,
                    edadhijo1           : edadhijo1,
                    estudioshijo1       : estudioshijo1,
                    becahijo1           : becahijo1,
                    ocupacionhijo1      : ocupacionhijo1,
                    ingresohijo1        : ingresohijo1,
                    nombrehijo2         : nombrehijo2,
                    ap1hijo2            : ap1hijo2,
                    ap2hijo2            : ap2hijo2,
                    edadhijo2           : edadhijo2,
                    estudioshijo2       : estudioshijo2,
                    becahijo2           : becahijo2,
                    ocupacionhijo2      : ocupacionhijo2,
                    ingresohijo2        : ingresohijo2,
                    nombrehijo3         : nombrehijo3,
                    ap1hijo3            : ap1hijo3,
                    ap2hijo3            : ap2hijo3,
                    edadhijo3           : edadhijo3,
                    estudioshijo3       : estudioshijo3,
                    becahijo3           : becahijo3,
                    ocupacionhijo3      : ocupacionhijo3,
                    ingresohijo3        : ingresohijo3,
                    nombrehijo4         : nombrehijo4,
                    ap1hijo4            : ap1hijo4,
                    ap2hijo4            : ap2hijo4,
                    edadhijo4           : edadhijo4,
                    estudioshijo4       : estudioshijo4,
                    becahijo4           : becahijo4,
                    ocupacionhijo4      : ocupacionhijo4,
                    ingresohijo4        : ingresohijo4,
                    nombrehijo5         : nombrehijo5,
                    ap1hijo5            : ap1hijo5,
                    ap2hijo5            : ap2hijo5,
                    edadhijo5           : edadhijo5,
                    estudioshijo5       : estudioshijo5,
                    becahijo5           : becahijo5,
                    ocupacionhijo5      : ocupacionhijo5,
                    ingresohijo5        : ingresohijo5,
                    
                    otros               : otros,
                    tipofam1            : tipofam1,
                    nombrefam1          : nombrefam1,
                    ap1fam1             : ap1fam1,
                    ap2fam1             : ap2fam1,
                    edadfam1            : edadfam1,
                    estudiosfam1        : estudiosfam1,
                    becafam1            : becafam1,
                    ocupacionfam1       : ocupacionfam1,
                    ingresofam1         : ingresofam1,
                    tipofam2            : tipofam2,
                    nombrefam2          : nombrefam2,
                    ap1fam2             : ap1fam2,
                    ap2fam2             : ap2fam2,
                    edadfam2            : edadfam2,
                    estudiosfam2        : estudiosfam2,
                    becafam2            : becafam2,
                    ocupacionfam2       : ocupacionfam2,
                    ingresofam2         : ingresofam2,
                    tipofam3            : tipofam3,
                    nombrefam3          : nombrefam3,
                    ap1fam3             : ap1fam3,
                    ap2fam3             : ap2fam3,
                    edadfam3            : edadfam3,
                    estudiosfam3        : estudiosfam3,
                    becafam3            : becafam3,
                    ocupacionfam3       : ocupacionfam3,
                    ingresofam3         : ingresofam3
                },
                dataType: 'json',
                url:  'actions.php',
                success:  function(response)
                {   
                    var status  = response[0];
                    
                    if(status == 'ok')
                    {
                        $("#sec1").css("display", "none");
                        $("#t1").css("display", "none");
                        $("#t12").css("display", "block");
                        
                        $("#sec2").css("display", "none");
                        $("#t2").css("display", "none");
                        $("#t22").css("display", "block");
                        
                        $("#sec21").css("display", "none");
                        $("#t3").css("display", "none");
                        $("#t32").css("display", "block");
                        
                        $("#sec22").css("display", "none");
                        $("#t4").css("display", "none");
                        $("#t42").css("display", "block");
                        
                        $("#sec3").css("display", "block");
                        $("#t5").css("display", "block");
                        $("#t52").css("display", "none");
                        
                        msg('Información Almacenada Correctamente');
                    }
                    else
                    {
                        msgerror('Lo sentimos, ocurrio un problema con la solicitud, vuelve a intentarlo');
                    }
                }
            });
    }
    
    function mostrar8(){
        var tipov = $("#tipov").val();
        if(tipov == 'RENTADA'){ 
            $("#renta1,#renta2").css("display", "block"); 
            $("#otrav2,#otrav2").css("display", "none");
            $("#otracasa").val('');
        }else if(tipov == 'OTROS'){ 
            $("#otrav2,#otrav2").css("display", "block"); 
            $("#renta1,#renta2").css("display", "none");
            $("#rentaprecio").val('');
        }
        else{ 
            $("#renta1,#renta2").css("display", "none"); 
            $("#otrav2,#otrav2").css("display", "none");
            
            $("#otracasa").val('');
            $("#rentaprecio").val('');
        }
    }
    
    function mostrarauto(num){
        for(var i = 1; i <= 5; i++){
           if(i == num){
               $("#infoauto"+i).css("display", "block");
           }else{
               $("#infoauto"+i).css("display", "none");
           }
        }
    }
    
    function guardasec3(){
        var conyuge         = $.trim($("#conyuge").val());
        var nombreconyuge     = $.trim($("#nombreconyuge").val());
        var ap1conyuge        = $.trim($("#ap1conyuge").val());
        var ap2conyuge        = $.trim($("#ap2conyuge").val());
        var edadconyuge       = $.trim($("#edadconyuge").val());
        var estudiosconyuge   = $.trim($("#estudiosconyuge").val());
        var becaconyuge       = $.trim($("#becaconyuge").val());
        var ocupacionconyuge  = $.trim($("#ocupacionconyuge").val());
        var ingresoconyuge    = $.trim($("#ingresoconyuge").val());
        
        nombreconyuge        = nombreconyuge.toUpperCase();
        ap1conyuge           = ap1conyuge.toUpperCase();
        ap2conyuge           = ap2conyuge.toUpperCase();
        estudiosconyuge      = estudiosconyuge.toUpperCase();
        ocupacionconyuge     = ocupacionconyuge.toUpperCase();
        
        var ingneto             = $.trim($("#ingneto").val());
        var otrosing            = $.trim($("#otrosing").val());
        var netotal             = $.trim($("#netotal").val());
        var aguinaldo           = $.trim($("#aguinaldo").val());
        var utilidades          = $.trim($("#utilidades").val());
        var prestaciones        = $.trim($("#prestaciones").val());
        var tipov               = $.trim($("#tipov").val());
        var rentaprecio         = $.trim($("#rentaprecio").val());
        var otracasa            = $.trim($("#otracasa").val());
        var bienesraices        = $.trim($("#bienesraices").val());
        var autos               = $.trim($("#autos").val());
        
        prestaciones        = prestaciones.toUpperCase();
        otracasa            = otracasa.toUpperCase();
        bienesraices        = bienesraices.toUpperCase();
               
        var marcaauto1      = $.trim($("#marcaauto1").val());
        var modeloauto1     = $.trim($("#modeloauto1").val());
        var valorauto1      = $.trim($("#valorauto1").val());
        var tipoauto1       = $.trim($("#tipoauto1").val());
        var pagoauto1       = $.trim($("#pagoauto1").val());
        
        marcaauto1          = marcaauto1.toUpperCase();
        modeloauto1         = modeloauto1.toUpperCase();
        tipoauto1           = tipoauto1.toUpperCase();
        
        var marcaauto2      = $.trim($("#marcaauto2").val());
        var modeloauto2     = $.trim($("#modeloauto2").val());
        var valorauto2      = $.trim($("#valorauto2").val());
        var tipoauto2       = $.trim($("#tipoauto2").val());
        var pagoauto2       = $.trim($("#pagoauto2").val());
        
        marcaauto2          = marcaauto2.toUpperCase();
        modeloauto2         = modeloauto2.toUpperCase();
        tipoauto2           = tipoauto2.toUpperCase();
        
        var marcaauto3      = $.trim($("#marcaauto3").val());
        var modeloauto3     = $.trim($("#modeloauto3").val());
        var valorauto3      = $.trim($("#valorauto3").val());
        var tipoauto3       = $.trim($("#tipoauto3").val());
        var pagoauto3       = $.trim($("#pagoauto3").val());
        
        marcaauto3          = marcaauto3.toUpperCase();
        modeloauto3         = modeloauto3.toUpperCase();
        tipoauto3           = tipoauto3.toUpperCase();
        
        var marcaauto4      = $.trim($("#marcaauto4").val());
        var modeloauto4     = $.trim($("#modeloauto4").val());
        var valorauto4      = $.trim($("#valorauto4").val());
        var tipoauto4       = $.trim($("#tipoauto4").val());
        var pagoauto4       = $.trim($("#pagoauto4").val());
        
        marcaauto4          = marcaauto4.toUpperCase();
        modeloauto4         = modeloauto4.toUpperCase();
        tipoauto4           = tipoauto4.toUpperCase();
        
        var marcaauto5      = $.trim($("#marcaauto5").val());
        var modeloauto5     = $.trim($("#modeloauto5").val());
        var valorauto5      = $.trim($("#valorauto5").val());
        var tipoauto5       = $.trim($("#tipoauto5").val());
        var pagoauto5       = $.trim($("#pagoauto5").val());
        
        marcaauto5          = marcaauto5.toUpperCase();
        modeloauto5         = modeloauto5.toUpperCase();
        tipoauto5           = tipoauto5.toUpperCase();
        
        var adeudos         = $.trim($("#adeudos").val());
        var pagodeuda       = $.trim($("#pagodeuda").val());
        
        var banco1          = $.trim($("#banco1").val());
        var banco2          = $.trim($("#banco2").val());
        var banco3          = $.trim($("#banco3").val());
        var tarjeta1        = $.trim($("#tarjeta1").val());
        var tarjeta2        = $.trim($("#tarjeta2").val());
        var tarjeta3        = $.trim($("#tarjeta3").val());
        
        banco1              = banco1.toUpperCase();
        banco2              = banco2.toUpperCase();
        banco3              = banco3.toUpperCase();
        tarjeta1              = tarjeta1.toUpperCase();
        tarjeta2              = tarjeta2.toUpperCase();
        tarjeta3              = tarjeta3.toUpperCase();

        var vivienda        = $.trim($("#vivienda").val());
        var comida          = $.trim($("#comida").val());
        var lavanderia      = $.trim($("#lavanderia").val());
        var gasolina        = $.trim($("#gasolina").val());
        var luz             = $.trim($("#luz").val());
        var servidumbre     = $.trim($("#servidumbre").val());
        var transporte      = $.trim($("#transporte").val());
        var telefonogasto   = $.trim($("#telefonogasto").val());
        var clubes          = $.trim($("#clubes").val());
        var colegiaturas    = $.trim($("#colegiaturas").val());
        var medico          = $.trim($("#medico").val());
        var gasagua         = $.trim($("#gasagua").val());
        var cableinternet   = $.trim($("#cableinternet").val());
        var otrosgastos     = $.trim($("#otrosgastos").val());
        var totalgastos     = $.trim($("#totalgastos").val());
        
        $.ajax({
                type: 'POST',
                data: 
                {
                    accion          : 'sec3',
                    folio           : folio,
                    peopleid        : peopleid,
                    
                    ingneto             : ingneto,
                    otrosing            : otrosing,
                    netotal             : netotal,
                    aguinaldo           : aguinaldo,
                    utilidades          : utilidades,
                    prestaciones        : prestaciones,
                    tipov               : tipov,
                    rentaprecio         : rentaprecio,
                    otracasa            : otracasa,
                    bienesraices        : bienesraices,
                    autos               : autos,
                    
                    marcaauto1          : marcaauto1,
                    modeloauto1         : modeloauto1,
                    valorauto1          : valorauto1,
                    tipoauto1           : tipoauto1,
                    pagoauto1           : pagoauto1,
                    marcaauto2          : marcaauto2,
                    modeloauto2         : modeloauto2,
                    valorauto2          : valorauto2,
                    tipoauto2           : tipoauto2,
                    pagoauto2           : pagoauto2,
                    marcaauto3          : marcaauto3,
                    modeloauto3         : modeloauto3,
                    valorauto3          : valorauto3,
                    tipoauto3           : tipoauto3,
                    pagoauto3           : pagoauto3,
                    marcaauto4          : marcaauto4,
                    modeloauto4         : modeloauto4,
                    valorauto4          : valorauto4,
                    tipoauto4           : tipoauto4,
                    pagoauto4           : pagoauto4,
                    marcaauto5          : marcaauto5,
                    modeloauto5         : modeloauto5,
                    valorauto5          : valorauto5,
                    tipoauto5           : tipoauto5,
                    pagoauto5           : pagoauto5,
                    
                    adeudos             : adeudos,
                    pagodeuda           : pagodeuda,
                    
                    banco1              : banco1,
                    banco2              : banco2,
                    banco3              : banco3,
                    tarjeta1            : tarjeta1,
                    tarjeta2            : tarjeta2,
                    tarjeta3            : tarjeta3,
                    
                    vivienda            : vivienda,
                    comida              : comida,
                    lavanderia          : lavanderia,
                    gasolina            : gasolina,
                    luz                 : luz,
                    servidumbre         : servidumbre,
                    transporte          : transporte,
                    telefonogasto       : telefonogasto,
                    clubes              : clubes,
                    colegiaturas        : colegiaturas,
                    medico              : medico,
                    gasagua             : gasagua,
                    cableinternet       : cableinternet,
                    otrosgastos         : otrosgastos,
                    totalgastos         : totalgastos
                },
                dataType: 'json',
                url:  'actions.php',
                success:  function(response)
                {   
                    var status  = response[0];
                    
                    if(status == 'ok')
                    {
                        $("#sec1").css("display", "none");
                        $("#t1").css("display", "none");
                        $("#t12").css("display", "block");
                        
                        $("#sec2").css("display", "none");
                        $("#t2").css("display", "none");
                        $("#t22").css("display", "block");
                        
                        $("#sec21").css("display", "none");
                        $("#t3").css("display", "none");
                        $("#t32").css("display", "block");
                        
                        $("#sec22").css("display", "none");
                        $("#t4").css("display", "none");
                        $("#t42").css("display", "block");
                        
                        $("#sec3").css("display", "none");
                        $("#t5").css("display", "none");
                        $("#t52").css("display", "block");
                        
                        msg('Información Almacenada Correctamente, el siguiente paso es subir la Documentacion');
                        window.open('documentos_rendimiento.php','_self');
                     }
                    else
                    {
                        msgerror('Lo sentimos, ocurrio un problema con la solicitud, vuelve a intentarlo');
                    }
                }
            });
    }
    
    function msg(mensaje){
        $('#mensajes').modal('show');
        $("#msj").empty();
        $("#msj").append('<div class="modal-header">'
                        +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                            +'<span aria-hidden="true">&times;</span>'
                        +'</button>'
                    +'</div>'
                    +'<div class="modal-body">'
                        +'<div class="alert alert-info" role="alert">'
                            +'<p>'+mensaje+'</p>'
                        +'</div>'
                    +'</div>');
    }
    
    function msg2(previo){
        $('#mensajes').modal('show');
        $("#msj").empty();
        $("#msj").append('<div class="modal-header">'
                        +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarsalir()">'
                            +'<span aria-hidden="true">&times;</span>'
                        +'</button>'
                    +'</div>'
                    +'<div class="modal-body">'
                        +'<div class="alert alert-info" role="alert">'
                            +'<p>'+previo+'</p>'
                        +'</div>'
                    +'</div>');
    }
    
    function msgerror(falla){
        $("#msj").empty();
        $('#mensajes').modal('show');
        $("#msj").append('<div class="modal-header">'
                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarsalir()">'
                                +'<span aria-hidden="true">&times;</span>'
                            +'</button>'
                        +'</div>'
                        +'<div class="modal-body">'
                            +'<div class="alert alert-danger" role="alert">'
                                +'<p>'+falla+'</p>'
                            +'</div>'
                        +'</div>');
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
