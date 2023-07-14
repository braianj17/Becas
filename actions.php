<?php 
session_start();
require_once("../conexiones/conecta_alu.php");
include_once "../classes/mssql_data.php";
include_once("../classes/db_params.php");

$idpc   = $_SESSION["alumno"];##People Id del alumno 9 digitos
$folio  = $_SESSION["recibo"];##Folio, numero de recibo

### MSSQL CONNECTION Power Campus
$msSql= new  mssqlCnx($pc_server_address,$pc_dbuser,$pc_dbpasswd,$pc_db); ##Produccion
$con=$msSql->Open();

### MSSQL CONNECTION SIIE
$siieSql= new  mssqlCnx($siie_server_address,$siie_dbuser,$siie_dbpasswd,$siie_db);
$consiie=$siieSql->Open();

##Variable que indica la accion para guardar la informacion
$accion = $_POST['accion'];

##Acciones del sistema, Guardar y Actualizar la informacion
    switch ($accion)
    {
        case 'generales':
            ##Aqui se guardaran los generales de la beca, que es la principal seccion de busqueda de solicitud de un alumno, aqui se determina si la solicitud ha sido hecha
            ##si ha sido aceptada, rechazada, si su beca debe de contener documentacion, si la documentacion ha sido revisada y aceptada
            ##Valores a guardar
            ##Folio, people_id, beca_tipo, beca_solicitado, porcentaje_solicitado, beca_estatus (0 es espera, 1 aceptado, 2 rechazado), 
            ##documentos (0 no solicita, 1 solicita), estatus_documentos (0 sin documentos, 1 en espera, 2 aceptados)
            ##año, periodo, plantel, seccion_completa (c completo, 0 solicitud, 1 seccion 1, 2 seccion 2, etc), tipo_beca (1 academica, 2 empresarial, 0 otra)
            ##Si la seccion es completo, se verifica si solicita documentos, si solicita documentos, estos se verifican para saber su estatus
            
            ##Valores a guardar
            $foliobeca              = $_POST['folio'];
            $peopleid               = $_POST['peopleid'];
            $beca_tipo              = $_POST['beca_tipo'];##id de la beca solicitada
            $beca_solicitado        = $_POST['beca_solicitado'];##id del nivel solicitado
            $porcentaje_solicitado  = $_POST['porcentaje_solicitado'];##% solicitado
            $academic_year          = $_POST['academic_year'];
            $academic_term          = $_POST['academic_term'];
            $academic_session       = $_POST['academic_session'];
            
            $tiposol                = $_POST['tiposol']; ##tipo de solicitud: renovacion,nueva
            
            $tipobs                 = $_POST['tipobs']; ##EMPRESARIA, ACADEMICA, otra
            if($tipobs == 'ACADEMICA'){ 
                $tipo_beca = 1; 
                $documentos         = 1;##Indica si el tipo de beca solicita Documentos
                $estatus_documentos = 0;##Indica si el estatus de los Documentos ingresados
                $seccion_completa   = 0;##Indica la seccion completada por el alumno
            }            
            else if($tipobs == 'EMPRESARIA'){ 
                $tipo_beca = 2; 
                $documentos         = 1;##Indica si el tipo de beca solicita Documentos
                $estatus_documentos = 0;##Indica si el estatus de los Documentos ingresados
                $seccion_completa   = 0;##Indica la seccion completada por el alumno
            }
            else{ 
                $tipo_beca = 0; 
                $documentos         = 0;  ##Indica si el tipo de beca solicita Documentos
                $estatus_documentos = 2;  ##Indica si el estatus de los Documentos ingresados
                $seccion_completa   = 'c';##Indica la seccion completada por el alumno
            }
            
            /*$proceder               = $_POST['proceder']; ##directo, procesar
            if($proceder == 'directo'){ 
                $documentos         = 0;  ##Indica si el tipo de beca solicita Documentos
                $estatus_documentos = 0;  ##Indica si el estatus de los Documentos ingresados
                $seccion_completa   = 'c';##Indica la seccion completada por el alumno
            }else if($proceder == 'procesar'){
                $documentos         = 1;##Indica si el tipo de beca solicita Documentos
                $estatus_documentos = 0;##Indica si el estatus de los Documentos ingresados
                $seccion_completa   = 0;##Indica la seccion completada por el alumno
            }*/
            
            $nivelb                 = $_POST['nivelb']; ##nivel de Beca solicitado 75 => 75.00
            $promedio               = $_POST['promedio']; ##promedio del alumno
            $empresaconv            = $_POST['empresaconv']; ##empresa de convenio del alumno
            
            
            ##Obtener el PersonId del alumno solicitante de Beca
            $query_person ="SELECT PersonId FROM PEOPLE WHERE PEOPLE_ID = '$peopleid';";
            $cmd = new mssqlCommand($query_person,$con);
            $dataperson = $cmd->ExecuteReader(true);
            $personid = $dataperson[0]['PersonId'];
            
            ##Se guarda el registro en Power Campus
            $query_pc="INSERT INTO ScholarshipApplication (ScholarshipOfferingId,ScholarshipOfferingLevelId,PersonId,ScholarshipStatus,StatusDate,AwardedPercentage,
                                AwardedDate,CREATE_DATE,CREATE_TIME,CREATE_OPID,CREATE_TERMINAL,REVISION_DATE,REVISION_TIME,REVISION_OPID,REVISION_TERMINAL,Score)
                        VALUES ('$beca_tipo', '$beca_solicitado', '$personid','ENESPERA', DATEADD(dd, 0, DATEADD(dd, 0, DATEDIFF(dd, 0, GETDATE()))),$porcentaje_solicitado,
                                DATEADD(dd, 0, DATEADD(dd,0,DATEDIFF(dd,0,GETDATE()))),
                                DATEADD(dd, 0, DATEADD(dd,0,DATEDIFF(dd,0,GETDATE()))),
                                convert(datetime,'1900-01-01 '+ CONVERT(varchar(15),getdate(),114)), 'SCT', '0001', 
                                DATEADD(dd, 0, DATEDIFF(dd,0,getdate())), 
                                convert(datetime, '1900-01-01 ' + CONVERT(varchar(15),getdate(), 114)), 'SCT', '0001',10);";
            $cmd= new mssqlCommand($query_pc,$con);
            $datosolbecpc=$cmd->ExecuteNonQuery(true);
            if($datosolbecpc != "bool(FALSE)")
            {
                ##Se guardan los datos de la solicitud de beca en SIIE
                $query_siie="INSERT INTO beca_generales 
                                values ('$foliobeca','$peopleid','$beca_tipo','$beca_solicitado','$porcentaje_solicitado',
                                '0','0','0',
                                '0','$documentos','$estatus_documentos','$academic_year','$academic_term','$academic_session','$seccion_completa','$tipo_beca','$promedio','$empresaconv','$tiposol','1');";
		$cmd= new mssqlCommand($query_siie,$consiie);
                $databeca=$cmd->ExecuteNonQuery(true);
                if($databeca != "bool(FALSE)")
                {
                    $status = 'ok';
                }
                else
                {
                    $status = 'fail';
                }
                
                ##Se actualiza la beca en Power Campus, descontando el numero de existentes
                ##1 Obtener el numero de becas disponibles
                ##2 Disminuir el numero de becas disponibles
                $query_numero ="SELECT NumScholarshipAvailable disponible FROM ScholarshipOfferingLevel WHERE ScholarshipOfferingLevelId = '$beca_solicitado';";
                $cmd = new mssqlCommand($query_numero,$con);
                $datanumero = $cmd->ExecuteReader(true);
                $disponible = $datanumero[0]['disponible'];##Numero de becas disponibles
                $disponible = $disponible-1;
                
                $query_updispone="UPDATE ScholarshipOfferingLevel SET NumScholarshipAvailable = $disponible WHERE ScholarshipOfferingLevelId = '$beca_solicitado';";
                $cmd= new mssqlCommand($query_updispone,$con);
                $dato_numbecas=$cmd->ExecuteNonQuery(true);
                
            }
            else
            {
                $status = 'fail';
            }
            
            $response = array( $status, $tipo_beca);
            echo json_encode($response);
            break;
            
            case 'sec1':
            ##Aqui se guardaran la informacion de la seccion 1
             
            ##Valores a guardar
            $foliobeca  = $_POST['folio'];
            $peopleid   = $_POST['peopleid'];
            
            $nombre     = $_POST['nombre'];
            $ap1        = $_POST['ap1'];
            $ap2        = $_POST['ap2'];
            $grado      = $_POST['grado'];
            $nivel      = $_POST['nivel'];
            $promedio   = $_POST['promedio'];
            $nueva      = $_POST['nueva'];
            $anterior   = $_POST['anterior'];
            $motivo     = $_POST['motivo'];
            
            ##Se obtiene el porcentaje solicitado de la tabla beca_generales
            $query_por ="SELECT porcentaje_solicitado FROM beca_generales WHERE folio = $foliobeca;";
            $cmd = new mssqlCommand($query_por,$consiie);
            $datapor = $cmd->ExecuteReader(true);
            $pocentaje_solicitado = $datapor[0]['porcentaje_solicitado'];
            
            ##Se guarda el registro en SIIE en tabla beca_sec1
            $query_siie="INSERT INTO beca_sec1 
                        VALUES ('$foliobeca','$peopleid','$nombre','$ap1','$ap2','$grado','$nivel','$promedio','$nueva','$anterior','$pocentaje_solicitado','$motivo');";
            $cmd= new mssqlCommand($query_siie,$consiie);
            $becasec1=$cmd->ExecuteNonQuery(true);
            
            if($becasec1 != "bool(FALSE)")
            {
                $status = 'ok';
            }
            else
            {
                $status = 'fail';
            }
            
            $response = array( $status );
            echo json_encode($response);
            break;
            
            case 'sec2':
            ##Aqui se guardaran la informacion de la seccion 2
             
            ##Valores a guardar
            $foliobeca  = $_POST['folio'];
            $peopleid   = $_POST['peopleid'];
            
            $edad           =$_POST['edad'];
            $genero         =$_POST['genero'];
            $curp           =$_POST['curp'];
            $telefono       =$_POST['telefono'];
            $calle          =$_POST['calle'];
            $num1           =$_POST['num1'];
            $num2           =$_POST['num2'];
            $colonia        =$_POST['colonia'];
            $cp             =$_POST['cp'];
            $localidad      =$_POST['localidad'];
            $municipio      =$_POST['municipio'];
            $residencia     =$_POST['residencia'];
            $procedencia    =$_POST['procedencia'];
            $bantes         =$_POST['bantes'];
            $cuando         =$_POST['cuando'];
            $ingreso        =$_POST['ingreso'];
            $discapacitado  =$_POST['discapacitado'];
            $discapacidad   =$_POST['discapacidad'];
            $dependiente    =$_POST['dependiente'];
            $padre          =$_POST['padre'];
            $madre          =$_POST['madre'];
            $situacion      =$_POST['situacion'];
            $depende        =$_POST['depende'];
            $hijos          =$_POST['hijos'];
            $estudian       =$_POST['estudian'];
            
            ##Se guarda el registro en SIIE en tabla beca_sec2
            $query_siie="INSERT INTO beca_sec2 
                        VALUES ('$foliobeca','$peopleid','$edad','$genero','$curp','$calle','$num1','$num2','$colonia','$cp','$localidad','$municipio','$procedencia','$residencia','$telefono','$bantes','$cuando','$ingreso','$discapacitado','$discapacidad','$dependiente','$padre','$madre','$situacion','$depende','$hijos','$estudian');";
            $cmd= new mssqlCommand($query_siie,$consiie);
            $becasec1=$cmd->ExecuteNonQuery(true);
            
            if($becasec1 != "bool(FALSE)")
            {
                $status = 'ok';
            }
            else
            {
                $status = 'fail';
            }
            
            $response = array( $status );
            echo json_encode($response);
            break;
            
            case 'sec22':
            ##Aqui se guardaran la informacion de la seccion 2 de los padres y tutor
             
            ##Valores a guardar
            $foliobeca  = $_POST['folio'];
            $peopleid   = $_POST['peopleid'];
            
            $nombrepadre     = $_POST['nombrepadre'];
            $ap1padre        = $_POST['ap1padre'];
            $ap2padre        = $_POST['ap2padre'];
            $edadpadre       = $_POST['edadpadre'];
            $estudiospadre   = $_POST['estudiospadre'];
            $becapadre       = $_POST['becapadre'];
            $ocupacionpadre  = $_POST['ocupacionpadre'];
            $ingresopadre    = $_POST['ingresopadre'];

            $nombremadre     = $_POST['nombremadre'];
            $ap1madre        = $_POST['ap1madre'];
            $ap2madre        = $_POST['ap2madre'];
            $edadmadre       = $_POST['edadmadre'];
            $estudiosmadre   = $_POST['estudiosmadre'];
            $becamadre       = $_POST['becamadre'];
            $ocupacionmadre  = $_POST['ocupacionmadre'];
            $ingresomadre    = $_POST['ingresomadre'];

            $tutor           = $_POST['tutor'];
            $generotutor     = $_POST['generotutor'];
            $nombretutor     = $_POST['nombretutor'];
            $ap1tutor        = $_POST['ap1tutor'];
            $ap2tutor        = $_POST['ap2tutor'];
            $edadtutor       = $_POST['edadtutor'];
            $estudiostutor   = $_POST['estudiostutor'];
            $becatutor       = $_POST['becatutor'];
            $ocupaciontutor  = $_POST['ocupaciontutor'];
            $ingresotutor    = $_POST['ingresotutor'];
            
            ##Se guarda el registro en SIIE en tabla beca_tutores
            if($nombrepadre != ''){
                $query_siie="INSERT INTO beca_tutores 
                        VALUES ('$foliobeca','$peopleid','H','PADRE','$nombrepadre','$ap1padre','$ap2padre',
                        '$edadpadre','$estudiospadre','$becapadre','$ocupacionpadre','$ingresopadre');";
                $respuesta1 = statusguardatutor($query_siie);
            }
            
            if($nombremadre != ''){
                $query_siie="INSERT INTO beca_tutores 
                        VALUES ('$foliobeca','$peopleid','M','MADRE','$nombremadre','$ap1madre','$ap2madre',
                        '$edadmadre','$estudiosmadre','$becamadre','$ocupacionmadre','$ingresomadre');";
                $respuesta2 = statusguardatutor($query_siie);
            }
            
            if($tutor == 'NO'){
                $query_siie="INSERT INTO beca_tutores 
                        VALUES ('$foliobeca','$peopleid','$generotutor','TUTOR','$nombretutor','$ap1tutor','$ap2tutor',
                        '$edadtutor','$estudiostutor','$becatutor','$ocupaciontutor','$ingresotutor');";
                $respuesta3 = statusguardatutor($query_siie);
            }
            
            if($nombrepadre != '' && $respuesta1 == 'ok'){ $status1 = 1; }
            if($nombremadre != '' && $respuesta2 == 'ok'){ $status2 = 1; }
            if($tutor == 'NO' && $respuesta3 == 'ok'){ $status3 = 1; }
            
            if($status1 == 1 || $status2 == 1 || $status3 == 1){
                $status = 'ok';
            }
            else{
                $status = 'fail';
            }
            
            $response = array( $status );
            echo json_encode($response);
            break;
            
            case 'sec23':
            ##Aqui se guardaran la informacion de la seccion 2 de los familiarez, conyuge, hijos y otros
             
            ##Valores a guardar
            $foliobeca  = $_POST['folio'];
            $peopleid   = $_POST['peopleid'];
            
            $conyuge            =$_POST['conyuge'];
            $nombreconyuge      =$_POST['nombreconyuge'];
            $ap1conyuge         =$_POST['ap1conyuge'];
            $ap2conyuge         =$_POST['ap2conyuge'];
            $edadconyuge        =$_POST['edadconyuge'];
            $estudiosconyuge    =$_POST['estudiosconyuge'];
            $becaconyuge        =$_POST['becaconyuge'];
            $ocupacionconyuge   =$_POST['ocupacionconyuge'];
            $ingresoconyuge     =$_POST['ingresoconyuge'];

            $verhijos           = $_POST['verhijos'];
            $nombrehijo1        = $_POST['nombrehijo1'];
            $ap1hijo1           = $_POST['ap1hijo1'];
            $ap2hijo1           = $_POST['ap2hijo1'];
            $edadhijo1          = $_POST['edadhijo1'];
            $estudioshijo1      = $_POST['estudioshijo1'];
            $becahijo1          = $_POST['becahijo1'];
            $ocupacionhijo1     = $_POST['ocupacionhijo1'];
            $ingresohijo1       = $_POST['ingresohijo1'];
            
            $nombrehijo2        = $_POST['nombrehijo2'];
            $ap1hijo2           = $_POST['ap1hijo2'];
            $ap2hijo2           = $_POST['ap2hijo2'];
            $edadhijo2          = $_POST['edadhijo2'];
            $estudioshijo2      = $_POST['estudioshijo2'];
            $becahijo2          = $_POST['becahijo2'];
            $ocupacionhijo2     = $_POST['ocupacionhijo2'];
            $ingresohijo2       = $_POST['ingresohijo2'];
            
            $nombrehijo3        = $_POST['nombrehijo3'];
            $ap1hijo3           = $_POST['ap1hijo3'];
            $ap2hijo3           = $_POST['ap2hijo3'];
            $edadhijo3          = $_POST['edadhijo3'];
            $estudioshijo3      = $_POST['estudioshijo3'];
            $becahijo3          = $_POST['becahijo3'];
            $ocupacionhijo3     = $_POST['ocupacionhijo3'];
            $ingresohijo3       = $_POST['ingresohijo3'];
            
            $nombrehijo4        = $_POST['nombrehijo4'];
            $ap1hijo4           = $_POST['ap1hijo4'];
            $ap2hijo4           = $_POST['ap2hijo4'];
            $edadhijo4          = $_POST['edadhijo4'];
            $estudioshijo4      = $_POST['estudioshijo4'];
            $becahijo4          = $_POST['becahijo4'];
            $ocupacionhijo4     = $_POST['ocupacionhijo4'];
            $ingresohijo4       = $_POST['ingresohijo4'];
            
            $nombrehijo5        = $_POST['nombrehijo5'];
            $ap1hijo5           = $_POST['ap1hijo5'];
            $ap2hijo5           = $_POST['ap2hijo5'];
            $edadhijo5          = $_POST['edadhijo5'];
            $estudioshijo5      = $_POST['estudioshijo5'];
            $becahijo5          = $_POST['becahijo5'];
            $ocupacionhijo5     = $_POST['ocupacionhijo5'];
            $ingresohijo5       = $_POST['ingresohijo5'];

            $otros              = $_POST['otros'];
            $tipofam1           = $_POST['tipofam1'];       
            $nombrefam1         = $_POST['nombrefam1'];
            $ap1fam1            = $_POST['ap1fam1'];
            $ap2fam1            = $_POST['ap2fam1'];
            $edadfam1           = $_POST['edadfam1'];
            $estudiosfam1       = $_POST['estudiosfam1'];
            $becafam1           = $_POST['becafam1'];
            $ocupacionfam1      = $_POST['ocupacionfam1'];
            $ingresofam1        = $_POST['ingresofam1'];
            
            $tipofam2           = $_POST['tipofam2'];
            $nombrefam2         = $_POST['nombrefam2'];
            $ap1fam2            = $_POST['ap1fam2'];
            $ap2fam2            = $_POST['ap2fam2'];
            $edadfam2           = $_POST['edadfam2'];
            $estudiosfam2       = $_POST['estudiosfam2'];
            $becafam2           = $_POST['becafam2'];
            $ocupacionfam2      = $_POST['ocupacionfam2'];
            $ingresofam2        = $_POST['ingresofam2'];
            
            $tipofam3           = $_POST['tipofam3'];
            $nombrefam3         = $_POST['nombrefam3'];
            $ap1fam3            = $_POST['ap1fam3'];
            $ap2fam3            = $_POST['ap2fam3'];
            $edadfam3           = $_POST['edadfam3'];
            $estudiosfam3       = $_POST['estudiosfam3'];
            $becafam3           = $_POST['becafam3'];
            $ocupacionfam3      = $_POST['ocupacionfam3'];
            $ingresofam3        = $_POST['ingresofam3'];
            
            ##Se guarda el registro en SIIE en tabla beca_familia
            if($conyuge == 'SI' && $nombreconyuge != ''){
                $query_siie="INSERT INTO beca_familia
                        VALUES ('$foliobeca','$peopleid','CONYUGE','$nombreconyuge','$ap1conyuge','$ap2conyuge',
                        '$edadconyuge','$estudiosconyuge','$becaconyuge','$ocupacionconyuge','$ingresoconyuge');";
                $respuesta1 = statusguardatutor($query_siie);
            }
            
            if($verhijos == 'SI'){
                if($nombrehijo1 != ''){
                    $query_siie="INSERT INTO beca_familia 
                            VALUES ('$foliobeca','$peopleid','HIJO','$nombrehijo1','$ap1hijo1','$ap2hijo1',
                            '$edadhijo1','$estudioshijo1','$becahijo1','$ocupacionhijo1','$ingresohijo1');";
                    $respuesta21 = statusguardatutor($query_siie);
                }
                if($nombrehijo2 != ''){
                    $query_siie="INSERT INTO beca_familia 
                            VALUES ('$foliobeca','$peopleid','HIJO','$nombrehijo2','$ap1hijo2','$ap2hijo2',
                            '$edadhijo2','$estudioshijo2','$becahijo2','$ocupacionhijo2','$ingresohijo2');";
                    $respuesta22 = statusguardatutor($query_siie);
                }
                if($nombrehijo3 != ''){
                    $query_siie="INSERT INTO beca_familia 
                            VALUES ('$foliobeca','$peopleid','HIJO','$nombrehijo3','$ap1hijo3','$ap2hijo3',
                            '$edadhijo3','$estudioshijo3','$becahijo3','$ocupacionhijo3','$ingresohijo3');";
                    $respuesta23 = statusguardatutor($query_siie);
                }
                if($nombrehijo4 != ''){
                    $query_siie="INSERT INTO beca_familia 
                            VALUES ('$foliobeca','$peopleid','HIJO','$nombrehijo4','$ap1hijo4','$ap2hijo4',
                            '$edadhijo4','$estudioshijo4','$becahijo4','$ocupacionhijo4','$ingresohijo4');";
                    $respuesta24 = statusguardatutor($query_siie);
                }
                if($nombrehijo5 != ''){
                    $query_siie="INSERT INTO beca_familia 
                            VALUES ('$foliobeca','$peopleid','HIJO','$nombrehijo5','$ap1hijo5','$ap2hijo5',
                            '$edadhijo5','$estudioshijo5','$becahijo5','$ocupacionhijo5','$ingresohijo5');";
                    $respuesta25 = statusguardatutor($query_siie);
                }
            }
            
            if($otros == 'SI'){
                if($nombrefam1 != ''){
                    $query_siie="INSERT INTO beca_familia 
                            VALUES ('$foliobeca','$peopleid','$tipofam1','$nombrefam1','$ap1fam1','$ap2fam1',
                            '$edadfam1','$estudiosfam1','$becafam1','$ocupacionfam1','$ingresofam1');";
                    $respuesta31 = statusguardatutor($query_siie);
                }
                if($nombrefam2 != ''){
                    $query_siie="INSERT INTO beca_familia 
                            VALUES ('$foliobeca','$peopleid','$tipofam2','$nombrefam2','$ap1fam2','$ap2fam2',
                            '$edadfam2','$estudiosfam2','$becafam2','$ocupacionfam2','$ingresofam2');";
                    $respuesta32 = statusguardatutor($query_siie);
                }
                if($nombrefam3 != ''){
                    $query_siie="INSERT INTO beca_familia 
                            VALUES ('$foliobeca','$peopleid','$tipofam3','$nombrefam3','$ap1fam3','$ap2fam3',
                            '$edadfam3','$estudiosfam3','$becafam3','$ocupacionfam3','$ingresofam3');";
                    $respuesta33 = statusguardatutor($query_siie);
                }
            }
            
            $status = 'ok';
            
            $response = array( $status );
            echo json_encode($response);
            break;
            
            case 'sec3':
            ##Aqui se guardaran la informacion de la seccion 2 de los padres y tutor
             
            ##Valores a guardar
            $foliobeca  = $_POST['folio'];
            $peopleid   = $_POST['peopleid'];
            
            $ingneto            = $_POST['ingneto'];
            $otrosing           = $_POST['otrosing'];
            $netotal            = $_POST['netotal'];
            $aguinaldo          = $_POST['aguinaldo'];
            $utilidades         = $_POST['utilidades'];
            $prestaciones       = $_POST['prestaciones'];
            $tipov              = $_POST['tipov'];
            $rentaprecio        = $_POST['rentaprecio'];
            $otracasa           = $_POST['otracasa'];
            $bienesraices       = $_POST['bienesraices'];
            
            ##################################################
            $autos              = $_POST['autos'];
            $marcaauto1         = $_POST['marcaauto1'];
            $modeloauto1        = $_POST['modeloauto1'];
            $valorauto1         = $_POST['valorauto1'];
            $tipoauto1          = $_POST['tipoauto1'];
            $pagoauto1          = $_POST['pagoauto1'];
            $marcaauto2         = $_POST['marcaauto2'];
            $modeloauto2        = $_POST['modeloauto2'];
            $valorauto2         = $_POST['valorauto2'];
            $tipoauto2          = $_POST['tipoauto2'];
            $pagoauto2          = $_POST['pagoauto2'];
            $marcaauto3         = $_POST['marcaauto3'];
            $modeloauto3        = $_POST['modeloauto3'];
            $valorauto3         = $_POST['valorauto3'];
            $tipoauto3          = $_POST['tipoauto3'];
            $pagoauto3          = $_POST['pagoauto3'];
            $marcaauto4         = $_POST['marcaauto4'];
            $modeloauto4        = $_POST['modeloauto4'];
            $valorauto4         = $_POST['valorauto4'];
            $tipoauto4          = $_POST['tipoauto4'];
            $pagoauto4          = $_POST['pagoauto4'];
            $marcaauto5         = $_POST['marcaauto5'];
            $modeloauto5        = $_POST['modeloauto5'];
            $valorauto5         = $_POST['valorauto5'];
            $tipoauto5          = $_POST['tipoauto5'];
            $pagoauto5          = $_POST['pagoauto5'];
            
            $adeudos            = $_POST['adeudos'];
            $pagodeuda          = $_POST['pagodeuda'];
            
            $banco1             = $_POST['banco1'];
            $banco2             = $_POST['banco2'];
            $banco3             = $_POST['banco3'];
            $tarjeta1           = $_POST['tarjeta1'];
            $tarjeta2           = $_POST['tarjeta2'];
            $tarjeta3           = $_POST['tarjeta3'];
            ####################################################
            
            $vivienda           = $_POST['vivienda'];
            $comida             = $_POST['comida'];
            $lavanderia         = $_POST['lavanderia'];
            $gasolina           = $_POST['gasolina'];
            $luz                = $_POST['luz'];
            $servidumbre        = $_POST['servidumbre'];
            $transporte         = $_POST['transporte'];
            $telefonogasto      = $_POST['telefonogasto'];
            $clubes             = $_POST['clubes'];
            $colegiaturas       = $_POST['colegiaturas'];
            $medico             = $_POST['medico'];
            $gasagua            = $_POST['gasagua'];
            $cableinternet      = $_POST['cableinternet'];
            $otrosgastos        = $_POST['otrosgastos'];
            $totalgastos        = $_POST['totalgastos'];
            
            ##Se guarda el registro en SIIE en tabla beca_sec3
            $query_siie="INSERT INTO beca_sec3
                        VALUES ('$foliobeca','$peopleid','$ingneto','$otrosing','$netotal',
                        '$aguinaldo','$utilidades','$prestaciones','$tipov','$rentaprecio',
                        '$otracasa','$bienesraices','$vivienda','$comida','$lavanderia',
                        '$gasolina','$luz','$servidumbre','$transporte','$telefonogasto',
                        '$clubes','$colegiaturas','$medico','$gasagua','$cableinternet',
                        '$otrosgastos','$totalgastos');";
            $respuesta1 = statusguardatutor($query_siie);
            
            if($autos != '' && $autos > 0){
                if($marcaauto1 != ''){
                    $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','auto','$marcaauto1','$modeloauto1','$valorauto1',
                            '$tipoauto1','$pagoauto1','0','0','0','0','0');";
                    $respuesta21 = statusguardatutor($query_siie);
                }
                if($marcaauto2 != ''){
                    $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','auto','$marcaauto2','$modeloauto2','$valorauto2',
                            '$tipoauto2','$pagoauto2','0','0','0','0','0');";
                    $respuesta22 = statusguardatutor($query_siie);
                }
                if($marcaauto3 != ''){
                    $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','auto','$marcaauto3','$modeloauto3','$valorauto3',
                            '$tipoauto3','$pagoauto3','0','0','0','0','0');";
                    $respuesta22 = statusguardatutor($query_siie);
                }
                if($marcaauto4 != ''){
                    $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','auto','$marcaauto4','$modeloauto4','$valorauto4',
                            '$tipoauto4','$pagoauto4','0','0','0','0','0');";
                    $respuesta22 = statusguardatutor($query_siie);
                }
                if($marcaauto5 != ''){
                    $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','auto','$marcaauto5','$modeloauto5','$valorauto5',
                            '$tipoauto5','$pagoauto5','0','0','0','0','0');";
                    $respuesta22 = statusguardatutor($query_siie);
                }
            }
            
            if($adeudos != ''){
                $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','deuda','0','0','0',
                            '0','0','1','$adeudos','$pagodeuda','0','0');";
                $respuesta31 = statusguardatutor($query_siie);
            }
            
            if($banco1 != ''){
                $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','banco','0','0','0',
                            '0','0','0','0','0','$banco1','0');";
                $respuesta31 = statusguardatutor($query_siie);
            }
            
            if($banco2 != ''){
                $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','banco','0','0','0',
                            '0','0','0','0','0','$banco2','0');";
                $respuesta32 = statusguardatutor($query_siie);
            }
            
            if($banco3 != ''){
                $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','banco','0','0','0',
                            '0','0','0','0','0','$banco3','0');";
                $respuesta33 = statusguardatutor($query_siie);
            }
            
            if($tarjeta1 != ''){
                $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','tarjeta','0','0','0',
                            '0','0','0','0','0','0','$tarjeta1');";
                $respuesta41 = statusguardatutor($query_siie);
            }
            
            if($tarjeta2 != ''){
                $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','tarjeta','0','0','0',
                            '0','0','0','0','0','0','$tarjeta2');";
                $respuesta42 = statusguardatutor($query_siie);
            }
            
            if($tarjeta3 != ''){
                $query_siie="INSERT INTO beca_otros 
                            VALUES ('$foliobeca','$peopleid','tarjeta','0','0','0',
                            '0','0','0','0','0','0','$tarjeta3');";
                $respuesta43 = statusguardatutor($query_siie);
            }
            
            ##Actualizar el registro de beca_generales para colocar la seccion de la beca en c
            ##Se guardan los datos de la solicitud de beca en SIIE
            $query_siie="UPDATE beca_generales SET seccion_completa = 'c' WHERE folio = '$foliobeca';";
            $respuestalast = statusguardatutor($query_siie);
            
            $status = 'ok';
            
            $response = array( $status );
            echo json_encode($response);
            break;           
    }

    function statusguardatutor($consulta){
        global $consiie;
        $cmd= new mssqlCommand($consulta,$consiie);
        $becasec1=$cmd->ExecuteNonQuery(true);
            
        if($becasec1 != "bool(FALSE)")
        {
            $save = 'ok';
        }
        else
        {
            $save = 'fail';
        }
        
        return $save;
    } 
?>