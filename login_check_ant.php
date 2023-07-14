<?php
session_start();
include_once "../classes/mssql_data.php";
include_once("../classes/db_params.php");

##Se envia el numero de recibo
$recibo   = $_GET["recibo"];

### MSSQL CONNECTION
$msSql = new  mssqlCnx($pc_server_address,$pc_dbuser,$pc_dbpasswd,$pc_db);
$con = $msSql->Open();   

### MSSQL CONNECTION SIIE
$siieSql= new  mssqlCnx($siie_server_address,$siie_dbuser,$siie_dbpasswd,$siie_db);
$siieCon=$siieSql->Open();

##Verifica el alumno y su informacion principal de acuerdo al recibo
$query = "SELECT DISTINCT 
c.RECEIPT_NUMBER folio,
c.PEOPLE_ORG_ID pid,
p.TAX_ID matricula, 
p.FIRST_NAME nombre,
c.ACADEMIC_YEAR anio,
c.ACADEMIC_TERM periodo,
(SELECT CASE c.ACADEMIC_TERM WHEN 'SEMESTREA' THEN 'S' WHEN 'SEMESTREB' THEN 'S' WHEN 'CUATRIMESA' THEN 'C'WHEN 'CUATRIMESB' THEN 'C'WHEN 'CUATRIMESC' THEN 'C' END) periodo2,
c.ACADEMIC_SESSION plantel,
c.AMOUNT monto,
c.PAID_AMOUNT pagado,
c.BALANCE_AMOUNT deuda,
c3.CHARGE_CREDIT_CODE codigo,
a.DEGREE nivel,
a.PROGRAM programa,
a.CURRICULUM carrera
FROM CHARGECREDIT c
LEFT JOIN ChargeCreditApplication c2
ON c.CHARGECREDITNUMBER = c2.ChargeCreditSource
LEFT JOIN CHARGECREDIT c3
ON c2.ChargeAppliedTo = c3.CHARGECREDITNUMBER
LEFT JOIN ACADEMIC a
ON c.PEOPLE_ORG_ID = a.PEOPLE_ID
AND c.ACADEMIC_YEAR = a.ACADEMIC_YEAR
AND c.ACADEMIC_TERM = a.ACADEMIC_TERM
AND c.ACADEMIC_SESSION = a.ACADEMIC_SESSION
INNER JOIN PEOPLE p
ON p.PEOPLE_ID = c.PEOPLE_ORG_ID
WHERE c.RECEIPT_NUMBER = $recibo
AND c.VOID_FLAG = 'N'";
$cmd = new mssqlCommand($query,$con);
$fd = $cmd->ExecuteReader(true);

$people_id  = $fd[0]["pid"];
$folio      = $fd[0]['folio'];

##################################################################################
#####Datos del periodo para obtener informacion de los demas######################
##Datos del periodo en el cual el alumno esta solicitando la beca
$year_actual    = $fd[0]['anio'];
$periodo_actual = $fd[0]['periodo'];
$plantel_actual = $fd[0]['plantel'];

###########################################
##Nivel y programa para el acceso
##MTRIA Y ABIER
$nivel_escolar          = $fd[0]['nivel'];##MTRIA
$programa_escolar       = $fd[0]['programa'];##ABIER

$nivel_escolar1         = $fd[0]['nivel'];##MTRIA
$programa_escolar1      = $fd[0]['programa'];##ABIER

###########################################################
##Periodo al cual pertenece el alumno
$tipoperiodo    = $fd[0]['periodo2'];##Tipo de Periodo Semestral o Cuatrimestral

##Datos del periodo anterior, para obtener informacion ya que en el periodo actual no hay informacion aun
if($periodo_actual == 'SEMESTREA'){ $periodo_anterior = 'SEMESTREB'; $new_year = ($year_actual-1);}
elseif ($periodo_actual == 'SEMESTREB'){ $periodo_anterior = 'SEMESTREA'; $new_year = $year_actual;}
elseif ($periodo_actual == 'CUATRIMESA'){ $periodo_anterior = 'CUATRIMESC'; $new_year = ($year_actual-1);}
elseif ($periodo_actual == 'CUATRIMESB'){ $periodo_anterior = 'CUATRIMESA'; $new_year = $year_actual;}
elseif ($periodo_actual == 'CUATRIMESC'){ $periodo_anterior = 'CUATRIMESB'; $new_year = $year_actual;}
#############################################################################################################

##Obtener los datos del alumnos en el periodo anterior, esto para definir si el alumno pasa de un grado a otro
$query = "SELECT PROGRAM,DEGREE,CURRICULUM FROM ACADEMIC WHERE ACADEMIC_TERM = '$periodo_anterior' AND ACADEMIC_YEAR = $new_year AND ACADEMIC_SESSION = '$plantel_actual' AND PEOPLE_ID = $people_id;";
$cmd = new mssqlCommand($query,$con);
$nd =$cmd->ExecuteReader(true);

if($nivel_escolar == '' || $programa_escolar == ''){
    $nivel_escolar      = $nd[0]['DEGREE'];##nivel MTRIA LIC
    $programa_escolar   = $nd[0]['PROGRAM'];##PROGRAMA ABIER ESCOL
}

$nivel_escolar2      = $nd[0]['DEGREE']; ##Nivel anterior
$programa_escolar2   = $nd[0]['PROGRAM']; ## Programa anterior 
############################################################################

##Cuantas inscripciones tiene el alumno, si es mayor a 1 alumno de reingreso, de lo contrario alumno nuevo
$query = "SELECT COUNT([SECTION]) total_insc FROM TRANSCRIPTDETAIL WHERE PEOPLE_ID = '$people_id' AND EVENT_ID = '000000';";
$cmd= new mssqlCommand($query,$con);
$cmdin=$cmd->ExecuteReader(true);
$conteo = $cmdin[0]['total_insc'];

if($nivel_escolar == 'LIC' && $nivel_escolar2 == 'BACH')
{
    $alumnotipo = 1; ##Alumno nuevo
}
else if($nivel_escolar == 'MTRIA' && $nivel_escolar2 == 'LIC')
{
    $alumnotipo = 1; ##Alumno nuevo
}
else if($nivel_escolar == 'DOCT' && $nivel_escolar2 == 'MTRIA')
{
    $alumnotipo = 1; ##Alumno nuevo
}
else if($conteo == 0 || $conteo == 1)
{
    $alumnotipo = 1; ##Alumno nuevo
}
else
{
    $alumnotipo = 2; ##Alumno reingreso
}

##################################################
###Comparar fechas
function compararFechas($primera, $segunda)
{
  $valoresPrimera = explode ("/", $primera);   
  $valoresSegunda = explode ("/", $segunda); 
  $diaPrimera    = $valoresPrimera[0];  
  $mesPrimera  = $valoresPrimera[1];  
  $anyoPrimera   = $valoresPrimera[2]; 
  $diaSegunda   = $valoresSegunda[0];  
  $mesSegunda = $valoresSegunda[1];  
  $anyoSegunda  = $valoresSegunda[2];
  $diasPrimeraJuliano = gregoriantojd($mesPrimera, $diaPrimera, $anyoPrimera);  
  $diasSegundaJuliano = gregoriantojd($mesSegunda, $diaSegunda, $anyoSegunda);     
  if(!checkdate($mesPrimera, $diaPrimera, $anyoPrimera)){
    // "La fecha ".$primera." no es válida";
    return 0;
  }elseif(!checkdate($mesSegunda, $diaSegunda, $anyoSegunda)){
    // "La fecha ".$segunda." no es válida";
    return 0;
  }else{
    return  $diasPrimeraJuliano - $diasSegundaJuliano;
  } 
}
#####Fechas de inicio y fin de la solicitud de Becas#############
$fecha_inicio =  date("d/m/Y");##actual, fecha actual
##$tipoperiodo --> S o C

##Reingreso
$fecha_cierreS = "26/01/2018";##Fecha de cierre Alumnos Reinscritos Semestral
$fecha_cierreC = "30/04/2018";##Fecha de cierre Alumnos Reinscritos Cuatrimestral

if($tipoperiodo == 'S'){
    $diast = compararFechas ($fecha_inicio,$fecha_cierreS);
}else if($tipoperiodo == 'C'){
    $diast = compararFechas ($fecha_inicio,$fecha_cierreC);
}

if($diast > 0){
    $cerrar1 = 'si'; ##Cerrar el Sistema para los alumnos inscritos
}else{
    $cerrar1 = 'no'; ##
}

##Nuevo ingreso
$fecha_cierre2S = "26/01/2018";##Fecha de cierre Alumnos Nuevo Ingreso Semestral
$fecha_cierre2C = "11/05/2018";##Fecha de cierre Alumnos Nuevo Ingreso Cuatrimestral

if($tipoperiodo == 'S'){
    $diast = compararFechas ($fecha_inicio,$fecha_cierre2S);
}else if($tipoperiodo == 'C'){
    $diast = compararFechas ($fecha_inicio,$fecha_cierre2C);
}

if($diast2 > 0){
    $cerrar2 = 'si'; ##Cerrar el Sistema para los alumnos de Nuevo Ingreso
}else{
    $cerrar2 = 'no'; ##
}

##################################################################
##Funcion que realiza la actualización del registro de un alumno con beca tipo especial
function updespecial($registro, $newfolio){
    global $siieCon;
    $query_siie="UPDATE beca_generales SET folio = $newfolio WHERE folio = $registro;";
    $cmd= new mssqlCommand($query_siie,$siieCon);
    $databeca=$cmd->ExecuteNonQuery(true);
    if($databeca != "bool(FALSE)")
    {
        $status = 1;
    }
    else
    {
        $status = 0;
    }
    return $status;
}
######################################################################
###Excepciones al sistema, folios de alumno que requieren renovar beca y por tiempo no pudieron, lista enviada por Control Economico
##$recibos    = array(1418375,1417873,1417329, 1418318,1417943,1418310,1418623,1417153,1418597,1418661,1418657,1418679, 1408986, 1418237,1415842,1418759,1409055,1410793,1417189,1409627,);
$recibos    = array(1409643,1418307,1416328,1415842,1418327,1418657,1414500,1423635,1410901,1425496,1418624,1417642,1418307,1421993,1424300, 1425758,1416328,1415842,1418811,1425782,
                    1425727,1416947,1425727,1425336,1425219,1425026,1423964,1411112,1422135, 1425871,1425931,1426036,1426038,1426022,1426013,1426014,1425536,1425986,1425955,
                    1425957,1425958,1424544,1425510,1415219,1424843,1409670,1409502,1421815,1410034,1422135,1424914,1426077,1426078,1415219,1425896,1426228,1414370,
                    1417187,1425603,1409804,1418128,1426287,1426339,1426077,1426313,1426127,1425640,1416497,1425264,1418363,1410901,1426547,
                    1426609,1426608, 1426696,1426397,1426717,1413342,1426287,1426078, 1418292,1427103,1414370,1427103,1409655,1411396,1409904,1410045,1426395,1426396,
                    1428316, 1429614, 1430159, 1430243, 1417099,1417988,1412639,1418028,1414177,1414175,1414575,1414843, 1409641, 1417910,1433726,1413420,1418684, 1417456, 1439182,
    1442494, 1442543, 1442772, 1442604, 1442491, 1441671, 1441668, 1442540, 1441873, 1441767, 1442819, 1442517, 1443741, 1441924, 1444873, 1442722, 1418299, 1441533);

$recibos2    = array(1425276,1426184,1426185,1420691,1420380,1419827,1419366,1419788,1419143,1422895,1422422,1419346,1423244,1412630,1416309,1426048,1426783,1426124,
                    1426277,1426683,1417285,1423482,1423187,1430386, 1427063,1427158,1430369,1434221,1433507,1435176,1426240,1434583,1440821, 1441772, 
    1441696, 1440747, 1442695, 1409723, 1441923, 1441676, 1441875, 1442752, 1445126);
##Si el recibo existe en la Base de Datos
if($folio != NULL)
{
    ##Se realiza el recorrido de los conceptos pagados con el recibo
    foreach ($fd as $value)
    {
        $codigo = $value["codigo"]; ##Codigo de los conceptos pagados con el recibo
        ##Si entre los conceptos pagados esta el estudio socioeconomico se procede a realizar el siguiente proceso
        if($codigo == 'OPCESOLBEC' || $codigo == 'OTCESOLBEC')
        {
            ##Verificar si el estudio ha sido pagado completamente
            $deuda = $fd[0]["deuda"];
            if($deuda > 0)
            {
                $MSG="FAIL2"; ##No se ha pagado completamente el estudio socieoconomico
            }
            else
            {
                ##Se verifica si el alumno ya ha solicitado la beca
                #####################################################################
                ##Verificacion del Alumno
                #####################################################################
                ##Verificar si el alumno ya tiene la solicitud realizada
                $query_beca = "SELECT folio, people_id, estatus_documentos, beca_estatus, beca_tipo, beca_tipo_otorgado, documentos, academic_year, academic_term, academic_session,
                                tipo_beca, seccion_completa, proceso FROM beca_generales WHERE folio = '$recibo'";
                $cmd = new mssqlCommand($query_beca,$siieCon);
                $info_beca = $cmd->ExecuteReader();
                $solicitud      = trim($info_beca[0]['folio']);                 ##Folio de Beca solicitada, Indica que el recibo fue utilizado o aun no
                $al_pid         = trim($info_beca[0]['people_id']);             ##Id del alumnos
                $estado_doc     = trim($info_beca[0]['estatus_documentos']);    ##Estado de los documentos,0 sin documentos, 1 En espera, 2 Aceptados
                $estado_beca    = trim($info_beca[0]['beca_estatus']);          ##Estado de Beca 0 en espera, 1 aceptado, 2 rechazado
                $beca           = trim($info_beca[0]['beca_tipo']);             ##Tipo de Beca solicitada
                $beca_otorgado  = trim($info_beca[0]['beca_tipo_otorgado']);    ##Porcentaje otorgado
                $beca_doc       = trim($info_beca[0]['documentos']);            ##Documentos 1 rendimiento, 0 otras becas
                $al_year        = trim($info_beca[0]['academic_year']);         ##Año academico de la solicitud
                $al_term        = trim($info_beca[0]['academic_term']);         ##Periodo academico de la solicitud
                $al_plantel     = trim($info_beca[0]['academic_session']);      ##Plantel
                $tipobks        = trim($info_beca[0]['tipo_beca']);             ##Tipo de Beca solicitada, 1 ACADEMICA solicita documentos, 2 EMPRESARIA solicita documentos, 0 otra beca no solicita documentos
                $seccion        = trim($info_beca[0]['seccion_completa']);      ##Seccion completa, 0 solo carga general, en el caso de Academica no ha realizado el formulario, c seccion completa, esta solo aparece con las becas diferentes a Academica 
                $proceso        = trim($info_beca[0]['proceso']);               ##renovacion o nueva
                    
                ##1 verificar si el folio esta en el sistema
                ##Si tiene solicitud ya creada, se verificara
                ##El estatus de la carta de liberacion de beca
                ##El estatus de la beca, Aceptada, Denegada, En espera
                ##El estatus de sus documentos
                
                ##Si no tiene una solicitud con ese numero de recibo, se procede a realizar lo siguiente
                if($solicitud == '') 
                {
                    ##Antes de proceder con el proceso de Beca, se idenficara si el alumno tiene una Beca especial o Deportiva, esto mediante el id de alumno
                    ##El id sera guardado hasta 2 veces, se verificara cuantas veces esta guardado y se procedera a realizar la actualizacion del registro
                    ##Cambiando el numero de folio por el Numero de Recibo que se ingresa.
                    ##Se busca en la tabla de generales, el id del alumno y el tipo de proceso, este deberá indicar Especial o Deportes
                    ##$query_especial = "SELECT people_id, folio FROM beca_generales WHERE people_id = '$people_id' AND proceso = 'especial';";
                    $query_especial = "SELECT people_id, folio FROM beca_generales
                                        WHERE people_id = '$people_id'
                                        AND academic_year = $year_actual AND academic_term = '$periodo_actual' AND academic_session = '$plantel_actual'
                                        AND proceso IN ('especial','deportes') AND utilizado = 1;";
                    $cmd = new mssqlCommand($query_especial,$siieCon);
                    $info_esp = $cmd->ExecuteReader();
                    $reg1 = $info_esp[0]['folio']; ##Registro 1
                    $reg2 = $info_esp[1]['folio']; ##Registro 2
                    
                    if($reg1 != ''){
                        $especial = 'si';
                    }else {
                        $especial = 'no';
                    }
                    
                    ##Si el alumno tiene Beca especial, se actualizaran los registros del alumno
                    if($especial == 'si')
                    {
                        ##Se actualiza el registro 1
                        if($reg1 != ''){
                            $reciboesp = $recibo;
                            $bkesp1 = updespecial($reg1, $reciboesp);##Estatus regresado es 0 no aplicado, 1 aplicado
                            if($bkesp1 == 1){
                               $status1 = 'ok'; 
                            }else{
                               $status1 = 'fail'; 
                            }
                        }
                        
                        ##Se actualiza el registro 2 en caso de existir
                        if($reg2 != ''){
                            $reciboesp = $recibo.'2';
                            $bkesp2 = updespecial($reg2, $reciboesp);##Estatus regresado es 0 no aplicado, 1 aplicado
                            if($bkesp2 == 1){
                               $status2 = 'ok'; 
                            }else{
                               $status2 = 'fail'; 
                            }
                        }
                        
                        if($reg2 != ''){
                            ##Si el segundo registro existe, se verifican ambos registros
                            if($status1 == 'ok' && $status2 == 'ok'){
                                $MSG="MSJ1"; ##Beca solicitada con éxito
                            }else{
                                $MSG="MSJ5"; ##Ocurrio un problema con la solicitud de Beca 
                            }
                        }else{
                            ##Se verifica solo el primer registro
                            if($status1 == 'ok'){
                                $MSG="MSJ1"; ##Beca solicitada con éxito
                            }else{
                                $MSG="MSJ5"; ##Ocurrio un problema con la solicitud de Beca 
                            }
                        }
                    }
                    ##De lo contrario se sigue el proceso normal
                    else
                    {
                        ##1 Aun no esta en el sistema, procede a iniciar sesion y solicitar Beca
                        ##2 Verificar si el sistetema no se ha cerrado
                        ##3 Verificar si el alumno es de nuevo ingreso o reingreso, si es reingreso y la fecha es igual a la fecha de cierre ya no puede entrar
                        ##$alumnotipo --> 1 nuevo, 2 reingreso

                        ##Si el alumno es de nuevo ingreso, se verifica la fecha de cierre del sistema
                        if($alumnotipo == 1 && $cerrar2 == 'no')
                        {
                            ##Si es alumno de Nuevo Ingreso, se debe de realizar la Solicitud de Beca inicial
                            $solicito = 0; ##No ha solicitado Beca
                            $_SESSION['logged']= true;
                            $_SESSION["alumno"]= $people_id;
                            $_SESSION["recibo"]= $folio;
                            $MSG="SUCCESS";
                        }
                        ##Excepcion de fecha para Renovacion de Beca
                        else if(in_array($folio, $recibos)){
                            ##Indica renivacion de Beca ya solicitada en el periodo anterior
                            $solicito = 0; ##No ha solicitado Beca
                            $_SESSION['logged']= true;
                            $_SESSION["alumno"]= $people_id;
                            $_SESSION["recibo"]= $folio;
                            $MSG="SUCCESS2"; ##Renovacion de Beca
                        }
                        ##Excepcion de fecha para Nueva de Beca
                        else if(in_array($folio, $recibos2)){
                            ##Indica renivacion de Beca ya solicitada en el periodo anterior
                            $solicito = 0; ##No ha solicitado Beca
                            $_SESSION['logged']= true;
                            $_SESSION["alumno"]= $people_id;
                            $_SESSION["recibo"]= $folio;
                            $MSG="SUCCESS"; ##Renovacion de Beca
                        }
                        ##Si el alumnos de es reingreso, se verifica la fecha de cierre del sistema
                        else if($alumnotipo == 2 && $cerrar1 == 'no')
                        {
                            ##Si el alumno es de reingreso se debe de realizar lo siguiente
                            ##1 Verificar el periodo en el cual se esta realizando la Solicitud de Beca 
                                ##SemestreA, CuatrimesA, CuatrimesC Renovacion de Beca
                                ##SemestreB, CuatrimesC Solicitud de Beca Nueva
                                ##$periodo_actual;

                            ##2 Seleccionar las becas otorgadas en el periodo de solicitud pasado, esto mediante el año anterior al cual se esta solicitando, asi como con los periodos 
                            ##en los cuales se realiza la solicitud de beca
                            ##3 Seleccionar el PersonId del alumno, para verificar en tabla de aplicacion de becas
                            ##4 Si el alumno esta en las opciones de Renovacion de Beca, verificar que tenga una beca solicitada del periodo anterior de solicitud de beca
                            ##5 Si el alumno SI tiene solicitud de Beca anterior, se mostrara el sistema de Renovacion de Beca
                            ##6 Si el alumno NO tiene solicitud anterior, se mostrara el sistema para Solicitud de Beca

                            if(($periodo_actual == 'SEMESTREA') || ($periodo_actual == 'CUATRIMESA') || ($periodo_actual == 'CUATRIMESB'))
                            {
                                $query_person = "SELECT PersonId FROM PEOPLE WHERE PEOPLE_ID = '$people_id'";
                                $cmd = new mssqlCommand($query_person,$con);
                                $infoP = $cmd->ExecuteReader(true);
                                $personid = $infoP[0]['PersonId']; ##PersonId del alumno, ligado a la beca otorgada

                                $anio_beca = $year_actual; ##año en el cual solicita Beca
                                $anio_anterior = $anio_beca - 1; ##año anterior para buscar la beca en otro periodo

                                $query_becas = "SELECT DISTINCT beca_tipo_otorgado FROM beca_generales WHERE academic_year = $anio_anterior ORDER BY beca_tipo_otorgado";
                                $cmd = new mssqlCommand($query_becas,$siieCon);
                                $infoB = $cmd->ExecuteReader();
                                $renueva = 'no';##Variable que indica si el alumno Renueva o Solicita beca, el valor predefinido es NO, es decir que solicita nueva beca

                                foreach ($infoB as $b)
                                {
                                    $beca_torgada = $b['beca_tipo_otorgado'];
                                    ##Se recorren las becas que fueron otorgadas en el periodo, estas se revisan con el alumno para indicar si el alunmno renovara beca
                                    $query_scholar = "SELECT PersonId FROM ScholarshipApplication WHERE PersonId = '$personid' AND ScholarshipOfferingId = '$beca_torgada' AND ScholarshipStatus = 'OTOR';";
                                    $cmd = new mssqlCommand($query_scholar,$con);
                                    $infoS = $cmd->ExecuteReader(true);
                                    $banterior = $infoS[0]['PersonId']; ##Indica si el alumno tiene esa beca otorgada y la tiene que renovar
                                    if($banterior != '')
                                    {
                                        $renueva = 'si'; ##Esto indica que el alumno Renueva la beca, lo que dice que el alumno no tiene ninguna beca anterior
                                        break;
                                    }
                                }

                                if($renueva == 'no')
                                {
                                    ##Indica Nueva Beca
                                    $solicito = 0; ##No ha solicitado Beca
                                    $_SESSION['logged']= true;
                                    $_SESSION["alumno"]= $people_id;
                                    $_SESSION["recibo"]= $folio;
                                    $MSG="SUCCESS"; ##Nueva Solicitud
                                }
                                else if($renueva == 'si')
                                {
                                    ##Indica renivacion de Beca ya solicitada en el periodo anterior
                                    $solicito = 0; ##No ha solicitado Beca
                                    $_SESSION['logged']= true;
                                    $_SESSION["alumno"]= $people_id;
                                    $_SESSION["recibo"]= $folio;
                                    $MSG="SUCCESS2"; ##Renovacion de Beca
                                }
                            }
                            else
                            {
                                ##Si el alumno solicita beca en el periodo de SemestreA o CuatrimesC, se habilita el acceso a Nueva Beca
                                $solicito = 0; ##No ha solicitado Beca
                                $_SESSION['logged']= true;
                                $_SESSION["alumno"]= $people_id;
                                $_SESSION["recibo"]= $folio;
                                $MSG="SUCCESS";
                            }                
                        }

                        ##Si no se cumple el requisito, no se ingresa al sistema
                        else
                        {
                            ##El tiempo de solicitud de beca se ha terminado
                            $MSG="FAIL4";
                        }
                    }
                }
                else
                {
                    ##Si existe una solicitud con ese numero de recibo, se procede a realizar lo siguiente
                    ##Revisar el tipo de beca solicitada 0 otra beca, 1 rendimiento, 2 convenio
                    ##Si el tipo de beca es 0, 
                    ##Se verifica el estado de la beca 0 pendiente, 1 aceptada, 2 rechazada, se mostrara un mensaje indicando lo que sucede
                    ##Si esta aceptada,
                    ##Se verifica el estado de la carta de aceptacion, 0 en revision, 1 aceptada, 2 rechazada 
                    ##Si aun no existe la carta de aceptacion se carga la pagina de Resolutivo de Beca, donde se firmara la beca y esta se subira en Cartas de Aceptacion
                    ##Si la carta es 0 se mostrara el mensaje indicando esa revision
                    ##Si la carta es 2 se mostrara el mensaje indicado el rechazo de la carta
                    ##Si la carta es 1 se enviara a la ultima pagina indicando la aplicacion de la beca
                    
                    if($tipobks == 0){
                        if($estado_beca == 0){
                            $MSG="MSJ1"; ##Pendiente
                        }else if($estado_beca == 2){
                            $MSG="MSJ2"; ##Rechazada
                        }else{
                            $query_carta = "SELECT folio,estatus FROM beca_aceptar WHERE folio = '$solicitud';";
                            $cmd = new mssqlCommand($query_carta,$siieCon);
                            $infocarta = $cmd->ExecuteReader();
                            $foliocarta = $infocarta[0]['folio']; ##Folio de la carta de aceptacion
                            $edocarta   = $infocarta[0]['estatus']; ##Estatus de la carta de aceptacion
                            ##Si el folio existe, se procede a verificar el estatus de la carta
                            if($foliocarta == $solicitud){
                                if($edocarta == 0){
                                    //$MSG="MSJ3"; ##Carta Pendiente de Revision
                                    $MSG="SUCCESS3"; ##Carta Rechazada, se da acceso al sistema de carta de aceptacion
                                    $_SESSION['logged']= true;
                                    $_SESSION["alumno"]= $people_id;
                                    $_SESSION["recibo"]= $folio;
                                }else if($edocarta == 1){
                                    $MSG="MSJ4"; ##Carta Aceptada
                                }else{
                                    $MSG="SUCCESS3"; ##Carta Rechazada, se da acceso al sistema de carta de aceptacion
                                    $_SESSION['logged']= true;
                                    $_SESSION["alumno"]= $people_id;
                                    $_SESSION["recibo"]= $folio;
                                }
                            }
                            else
                            {
                                $MSG="SUCCESS3"; ##Aun no sube su carta de aceptacion, se da acceso al sistema de carta de aceptacion
                                $_SESSION['logged']= true;
                                $_SESSION["alumno"]= $people_id;
                                $_SESSION["recibo"]= $folio;
                            }
                        }
                    }
                    
                    ##Si el tipo de beca es diferente a 0
                    ##Se verifica el estado de la beca 0 pendiente, 1 aceptada, 2 rechazada, se mostrara un mensaje indicando lo que sucede
                    ##Si esta pendiente
                    ##Se verifica el estatus de los documentos 0 no ha subido documentos, 1 documentos en revision, 2 documentos aceptados
                    ##Si estan en 0 o 1 se envia al sistema de documentos, mostrandolos e indicando la situacion con cada uno de ellos
                    ##Si estan en 2, se mostrara el mensaje indicando incicando que la beca esta en revision
                    ##Si esta aceptada 
                    ##Se verifica el estado de la carta de aceptacion, 0 en revision, 1 aceptada, 2 rechazada 
                    ##Si aun no existe la carta de aceptacion se carga la pagina de Resolutivo de Beca, donde se firmara la beca y esta se subira en Cartas de Aceptacion
                    ##Si la carta es 0 se mostrara el mensaje indicando esa revision
                    ##Si la carta es 2 se mostrara el mensaje indicado el rechazo de la carta
                    ##Si la carta es 1 se enviara a la ultima pagina indicando la aplicacion de la beca
                    
                    else{
                        if($estado_beca == 0){
                            if($estado_doc == 0 || $estado_doc == 1){
                                ##Enviar en el mensaje $tipobks la cual indica la beca solcitante, esto para enviar los datos a los documentos y se muestre uno u otro sistema
                                ##1 Academico
                                ##2 Empresarial
                                if($tipobks == 1){
                                    if($seccion == 'c' || $proceso == 'renovacion'){
                                        $MSG="SUCCESS4"; ##Pendiente, se da acceso al sistema para verificar sus documentos de beca Academica
                                        $_SESSION['logged']= true;
                                        $_SESSION["alumno"]= $people_id;
                                        $_SESSION["recibo"]= $folio;
                                    }
                                    else{
                                        $MSG="SUCCESS6"; ##Pendiente, se da acceso al formulario de beca Academica
                                        $_SESSION['logged']= true;
                                        $_SESSION["alumno"]= $people_id;
                                        $_SESSION["recibo"]= $folio;
                                    }
                                }else{
                                    $MSG="SUCCESS5"; ##Pendiente, se da acceso al sistema para verificar sus documentos de beca Empresarial
                                    $_SESSION['logged']= true;
                                    $_SESSION["alumno"]= $people_id;
                                    $_SESSION["recibo"]= $folio;
                                }
                            }
                            else{
                                $MSG="MSJ1"; ##Pendiente, beca en revision
                            }
                        }else if($estado_beca == 2){
                            $MSG="MSJ2"; ##Rechazada
                        }else{
                            $query_carta = "SELECT folio,estatus FROM beca_aceptar WHERE folio = '$solicitud';";
                            $cmd = new mssqlCommand($query_carta,$siieCon);
                            $infocarta = $cmd->ExecuteReader();
                            $foliocarta = $infocarta[0]['folio']; ##Folio de la carta de aceptacion
                            $edocarta   = $infocarta[0]['estatus']; ##Estatus de la carta de aceptacion
                            ##Si el folio existe, se procede a verificar el estatus de la carta
                            if($foliocarta == $solicitud){
                                if($edocarta == 0){
                                    //$MSG="MSJ3"; ##Carta Pendiente de Revision
                                    $MSG="SUCCESS3"; ##Carta Rechazada, se da acceso al sistema de carta de aceptacion
                                    $_SESSION['logged']= true;
                                    $_SESSION["alumno"]= $people_id;
                                    $_SESSION["recibo"]= $folio;
                                }else if($edocarta == 1){
                                    $MSG="MSJ4"; ##Carta Aceptada
                                }else{
                                    $MSG="SUCCESS3"; ##Carta Rechazada, se da acceso al sistema de carta de aceptacion
                                    $_SESSION['logged']= true;
                                    $_SESSION["alumno"]= $people_id;
                                    $_SESSION["recibo"]= $folio;
                                }
                            }
                            else
                            {
                                $MSG="SUCCESS3"; ##Aun no sube su carta de aceptacion, se da acceso al sistema de carta de aceptacion
                                $_SESSION['logged']= true;
                                $_SESSION["alumno"]= $people_id;
                                $_SESSION["recibo"]= $folio;
                            }
                        }
                    }
                }
            }
            break;
        }
        else
        {
            $MSG="FAIL3"; ##El Recibo no pertenece a Estudio Socioeconomico 
        }
    }##Fin del ciclo for para verificar los conceptos pagados con el recibo
}##Fin de la condicion que indica si el folio o recibo existe en la base de datos
else
{
    $MSG="Fail1"; ##Indica que el Recibo no existe en el sistema
}
echo $MSG;
?>