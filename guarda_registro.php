<?
include_once "classes/mssql_data.php";
include_once("db_params.php");

/*
* Función para validar una entrada. 
*/
function validate_input($input){
    return addslashes(strip_tags($input));
}

if(isset($_POST["nombre"]) && isset($_POST["apellidos"]) && isset($_POST["direccion"]) && isset($_POST["colonia"]) && isset($_POST["correo"]) && isset($_POST["tipo"]) && isset($_POST["telefono"]) && isset($_POST["genero"]) && isset($_POST["pais"]) && isset($_POST["fnac"]) && isset($_POST["year"]) && isset($_POST["plantel"]) && isset($_POST["carrera"]) && isset($_POST["periodo"]))
{
    $nombre =       strtoupper (validate_input(utf8_decode($_POST["nombre"])));
    $apellidos =    strtoupper (validate_input(utf8_decode($_POST["apellidos"])));
    $direccion =    validate_input(utf8_decode($_POST["direccion"]));
    $colonia =      validate_input(utf8_decode($_POST["colonia"]));
    $correo =       validate_input($_POST["correo"]);
    $tipo =         validate_input($_POST["tipo"]);
    $telefono =     validate_input($_POST["telefono"]);
    $genero =       validate_input($_POST["genero"]);
    $pais =         validate_input($_POST["pais"]);
    $fnac =         validate_input($_POST["fnac"]);
    $year =         validate_input($_POST["year"]);
    $plantel =      validate_input($_POST["plantel"]);
    $carrera =      validate_input($_POST["carrera"]);
    $periodo =      validate_input($_POST["periodo"]);
}
else
{
    die("Parametros invalidos.");
}

### MSSQL CONNECTION
$msSql= new  mssqlCnx($pc_server_address,$pc_dbuser,$pc_dbpasswd,$pc_db);
$con=$msSql->Open();

$query="SELECT P.PEOPLE_ID AS MATRICULA, (P.FIRST_NAME+' '+P.LAST_NAME) AS NOMBRE, CONVERT(nvarchar,P.BIRTH_DATE,103) AS FECHA_NACIMIENTO, T.PhoneNumber AS TELEFONO, (A.ADDRESS_LINE_1+' Col.'+A.ADDRESS_LINE_2) AS DIRECCION, A.EMAIL_ADDRESS AS CORREO
            FROM PEOPLE AS P,PersonPhone AS T,ADDRESSSCHEDULE AS A
            WHERE P.PrimaryPhoneId = T.PersonPhoneId
            AND P.PEOPLE_ID = A.PEOPLE_ORG_ID
            AND P.FIRST_NAME = '$nombre'
            AND P.LAST_NAME = '$apellidos'
            AND P.BIRTH_DATE = '$fnac'
            AND A.EMAIL_ADDRESS = '$correo'";
        $cmd= new mssqlCommand($query,$con);
        $res=$cmd->ExecuteReader();

    if($res[0]["MATRICULA"] != '')
    {
        $status = 'stop';
        $msg="Esta persona ya habia sido registrada con anterioridad, sus datos son los siguientes.";
        $response = array( $status, $msg, $res[0]["MATRICULA"], strtoupper ($res[0]["NOMBRE"]), $res[0]["FECHA_NACIMIENTO"], $res[0]["TELEFONO"], $res[0]["DIRECCION"], $res[0]["CORREO"] );
    }
    else
    {
        $query="SELECT CODE_VALUE_KEY FROM CODE_COUNTRY WHERE CountryId = $pais";
        $cmd= new mssqlCommand($query,$con);
        $data=$cmd->ExecuteReader();
        $country =  $data[0]["CODE_VALUE_KEY"];
   
        $sql="DECLARE 
        @id VARCHAR(12),
        @idp VARCHAR(10),
        @phone VARCHAR(15)

        BEGIN TRANSACTION
        BEGIN TRY

        UPDATE ABT_SETTINGS 
        SET SETTING = (SELECT  REPLICATE('0', (9 - LEN(SETTING+1) ))+ convert(varchar, SETTING+1) NEXTID 
                        FROM ABT_SETTINGS 
                        WHERE LABEL_NAME='PEOPLE' 
                        AND SECTION_NAME='NEXTKEY') 
        WHERE LABEL_NAME='PEOPLE' 
        AND SECTION_NAME='NEXTKEY';

        SET @id = (SELECT SETTING FROM ABT_SETTINGS WHERE LABEL_NAME='PEOPLE' AND SECTION_NAME='NEXTKEY');

        INSERT INTO PEOPLE(PEOPLE_CODE,PEOPLE_ID,PEOPLE_CODE_ID,PREVIOUS_ID,FIRST_NAME,MIDDLE_NAME,LAST_NAME,PREFERRED_ADD,BIRTH_DATE,DECEASED_FLAG,CREATE_DATE,CREATE_TIME,CREATE_OPID,CREATE_TERMINAL,REVISION_DATE,REVISION_TIME,REVISION_OPID,REVISION_TERMINAL,ABT_JOIN) VALUES('P',@id,'P'+@id,@id,'$nombre',' ','$apellidos','DOM','$fnac','N',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),convert(datetime, '1900-01-01 ".date("H:i:s")."'),'CHASIDE','0001',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),convert(datetime, '1900-01-01 ".date("H:i:s")."'),'CHASIDE','0001','*');

        SET @idp = (SELECT PersonId FROM PEOPLE WHERE PEOPLE_ID = @id);

        INSERT INTO PersonPhone(PersonId,CountryId,PhoneNumber,CREATE_DATE,CREATE_TIME,CREATE_OPID,CREATE_TERMINAL,REVISION_DATE,REVISION_TIME,REVISION_OPID,REVISION_TERMINAL,PhoneType) VALUES(@idp,'$pais','$telefono',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),convert(datetime, '1900-01-01 ".date("H:i:s")."'),'CHASIDE','0001',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),convert(datetime, '1900-01-01 ".date("H:i:s")."'),'CHASIDE','0001','$tipo');

        SET @phone = (SELECT PersonPhoneId FROM PersonPhone WHERE PersonId = @idp);

        UPDATE PEOPLE 
        SET PrimaryPhoneId = @phone
        WHERE PEOPLE_ID = @id
        AND PersonId = @idp;

        INSERT INTO ADDRESSSCHEDULE(PEOPLE_ORG_CODE,PEOPLE_ORG_ID,PEOPLE_ORG_CODE_ID,ADDRESS_TYPE,ADDRESS_LINE_1,ADDRESS_LINE_2,COUNTRY,NO_MAIL,EMAIL_ADDRESS,STATUS,RECURRING,APPROVED,CREATE_DATE,CREATE_TIME,CREATE_OPID,CREATE_TERMINAL,REVISION_DATE,REVISION_TIME,REVISION_OPID,REVISION_TERMINAL) VALUES('P',@id,'P'+@id,'DOM','$direccion','$colonia','$country',' ','$correo','A','N','Y',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),convert(datetime, '1900-01-01 ".date("H:i:s")."'),'CHASIDE','0001',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),convert(datetime, '1900-01-01 ".date("H:i:s")."'),'CHASIDE','0001');

        INSERT INTO DEMOGRAPHICS(PEOPLE_CODE,PEOPLE_ID,PEOPLE_CODE_ID,ACADEMIC_YEAR,ACADEMIC_TERM,ACADEMIC_SESSION,GENDER,RETIRED,CREATE_DATE,CREATE_TIME,CREATE_OPID,CREATE_TERMINAL,REVISION_DATE,REVISION_TIME,REVISION_OPID,REVISION_TERMINAL,ABT_JOIN,LAST_ACTIVITY,CURRENT_ACTIVITY) VALUES('P',@id,'P'+@id,' ',' ',' ','$genero','N',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),convert(datetime, '1900-01-01 ".date("H:i:s")."'),'CHASIDE','0001',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),convert(datetime, '1900-01-01 ".date("H:i:s")."'),'CHASIDE','0001','*','N','N');

        COMMIT TRANSACTION 
        END TRY

        BEGIN CATCH

        ROLLBACK TRANSACTION 
        PRINT 'Se ha producido un error!'
        END CATCH;";

        //echo $sql;
        $cmd= new mssqlCommand($sql,$con);
        $dato=$cmd->ExecuteNonQuery();

       //if($dato != "bool(FALSE)")
        //{
            //si la transaccion se realizo correctamente se ubica la matricula para ingresar datos en Academic
            $query="SELECT P.PEOPLE_ID AS MATRICULA, (P.FIRST_NAME+' '+P.LAST_NAME) AS NOMBRE
                    FROM PEOPLE AS P,PersonPhone AS T,ADDRESSSCHEDULE AS A
                    WHERE P.PrimaryPhoneId = T.PersonPhoneId
                    AND P.PEOPLE_ID = A.PEOPLE_ORG_ID
                    AND P.FIRST_NAME = '$nombre'
                    AND P.LAST_NAME = '$apellidos'
                    AND P.BIRTH_DATE = '$fnac'
                    AND T.PhoneNumber = '$telefono'
                    AND A.ADDRESS_LINE_1 = '$direccion'
                    AND A.ADDRESS_LINE_2 = '$colonia'
                    AND A.EMAIL_ADDRESS = '$correo'";
            $cmd= new mssqlCommand($query,$con);
            $res=$cmd->ExecuteReader();

            if($res[0]["MATRICULA"] != '')
            {
                //si existe la matricula se realiza la insercion de infromacion en Academic
                $id = $res[0]["MATRICULA"];
                $query_academic="INSERT INTO ACADEMIC(PEOPLE_CODE,PEOPLE_ID,PEOPLE_CODE_ID,ACADEMIC_YEAR,ACADEMIC_TERM,ACADEMIC_SESSION,PROGRAM,DEGREE,CURRICULUM,COLLEGE,DEPARTMENT,CLASS_LEVEL,NONTRAD_PROGRAM,POPULATION,MATRIC,REGISTER_LIMIT,CREDITS,CREATE_DATE,CREATE_TIME,CREATE_OPID,CREATE_TERMINAL,REVISION_DATE,REVISION_TIME,REVISION_OPID,REVISION_TERMINAL,ABT_JOIN,PREREG_VALIDATE,REG_VALIDATE,GRADUATED,ORG_CODE_ID,ACADEMIC_FLAG,APPLICATION_FLAG,COLLEGE_ATTEND,ACA_PLAN_SETUP,STATUS,TRANSCRIPT_SEQ,LAST_ACTIVITY,CURRENT_ACTIVITY,INQUIRY_FLAG,INQUIRY_DATE,FIN_AID_CANDIDATE,EXTRA_CURRICULAR,DEGREE_CANDIDATE,INQ_STATUS,INQ_STATUS_DATE,PRIMARY_FLAG,PROTECT_COUNSELOR) VALUES('P','".$id."','P".$id."','$year','$periodo','$plantel','ESCOL','LIC','$carrera',' ',' ',' ',' ',' ','N','.00','.00',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),convert(datetime, '1900-01-01 ".date("H:i:s")."'),'CHASIDE','0001',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),convert(datetime, '1900-01-01 ".date("H:i:s")."'),'CHASIDE','0001','*','N','N','N','O000000001','N','N','ALNU','N','A','001','N','N','Y',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),'N','N','N','SOLI',DATEADD(dd, 0,DATEDIFF(dd, 0, getdate())),'N','N')";
                $cmdacademic= new mssqlCommand($query_academic,$con);
                $dato_academic=$cmdacademic->ExecuteNonQuery();
                
                //si se realiza la insercion correctamente
                if($dato_academic != "bool(FALSE)")
                {
                    $status = 'success';
                    $msg="Datos almacenados correctamente!";
                    $response = array( $status, $msg, $res[0]["MATRICULA"], strtoupper ($res[0]["NOMBRE"] ));
                }
                else
                {
                    $status = 'failed';
                    $msg = 'Ocurrio un problema al Ingresar en Academic, Recarga la pagina e intenta nuevamente!';
                    $response = array( $status, $msg );
                }
            }
            else
            {
                $status = 'failed';
                $msg = 'Ocurrio un problema al ingresar los datos, Recarga la pagina e intenta nuevamente!';
                $response = array( $status, $msg );
            }
        //}
        //else
        /*{
            $status = 'failed';
            $msg = 'Parametros invalidos, No se guardo la informacion, Recarga la pagina e intenta nuevamente!!!';
            $response = array( $status, $msg );
        }*/
    }
echo json_encode($response);
?>