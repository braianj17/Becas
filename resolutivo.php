<?php
session_start();
include "include/session_check.php";
require_once("../conexiones/conecta_alu.php");
include_once "../classes/mssql_data.php";
include_once("../classes/db_params.php");

$idpc   = $_SESSION["alumno"];##People Id del alumno 9 digitos
$folio  = $_SESSION["recibo"];##Folio, numero de recibo

### MSSQL CONNECTION
$msSql = new  mssqlCnx($pc_server_address,$pc_dbuser,$pc_dbpasswd,$pc_db);
$con = $msSql->Open();

### MSSQL CONNECTION SIIE
$siieSql= new  mssqlCnx($siie_server_address,$siie_dbuser,$siie_dbpasswd,$siie_db);
$siieCon=$siieSql->Open();
#####################################################################################
##Nivel del alumno en el periodo
$query = "SELECT FIRST_NAME FROM PEOPLE WHERE PEOPLE_ID = '$idpc';";
$cmd = new mssqlCommand($query,$con);
$info = $cmd->ExecuteReader();
$alumno = $info[0]['FIRST_NAME'];##Alumno de beca

#####################################################################
##Generales de la Beca#
$query_beca     = "SELECT people_id, beca_estatus, estatus_documentos, beca_tipo_otorgado, beca_otorgado, academic_year, academic_term, academic_session, proceso
                    FROM beca_generales WHERE folio = $folio;";
$cmd            = new mssqlCommand($query_beca,$siieCon);
$info_beca      = $cmd->ExecuteReader();
$estado_beca    = trim($info_beca[0]['beca_estatus']); ##Estado de Beca 0 en espera, 1 aceptado, 2 rechazado
$becaotorgada   = trim($info_beca[0]['beca_tipo_otorgado']); ##id de beca otorgado
$otorgado       = trim($info_beca[0]['beca_otorgado']); ##Id de porcentaje otorgado
$year_actual    = trim($info_beca[0]['academic_year']); ##año actual del periodo actual del alumno
$periodo_actual = trim($info_beca[0]['academic_term']); ##periodo actual del alumno
$plantel_actual = trim($info_beca[0]['academic_session']); ##plantel del alumno
$proceso        = trim($info_beca[0]['proceso']); ##proceso del alumno, nueva, renovacion
if($proceso == 'nueva'){
    $proceso = 'N';
}else{
    $proceso = 'R';
}

##Datos del periodo anterior, para obtener informacion ya que en el periodo actual no hay informacion aun
if($periodo_actual == 'SEMESTREA'){ $periodo_anterior = 'SEMESTREB'; $new_year = ($year_actual-1);}
elseif ($periodo_actual == 'SEMESTREB'){ $periodo_anterior = 'SEMESTREA'; $new_year = $year_actual;}
elseif ($periodo_actual == 'CUATRIMESA'){ $periodo_anterior = 'CUATRIMESC'; $new_year = ($year_actual-1);}
elseif ($periodo_actual == 'CUATRIMESB'){ $periodo_anterior = 'CUATRIMESA'; $new_year = $year_actual;}
elseif ($periodo_actual == 'CUATRIMESC'){ $periodo_anterior = 'CUATRIMESB'; $new_year = $year_actual;}
#############################################################################################################
##Nivel del alumno en el periodo
$query = "SELECT DEGREE FROM ACADEMIC WHERE PEOPLE_ID = '$idpc' AND ACADEMIC_SESSION = '$plantel_actual' AND ACADEMIC_TERM = '$periodo_actual' AND ACADEMIC_YEAR = '$year_actual'";
$cmd = new mssqlCommand($query,$con);
$info = $cmd->ExecuteReader();
$nivel = $info[0]['DEGREE'];##Detectar el nivel de maestria en semestral

if($nivel == ''){
    $query = "SELECT DEGREE FROM ACADEMIC WHERE PEOPLE_ID = '$idpc' AND ACADEMIC_SESSION = '$plantel_actual' AND ACADEMIC_TERM = '$periodo_anterior' AND ACADEMIC_YEAR = '$new_year'";
    $cmd = new mssqlCommand($query,$con);
    $info = $cmd->ExecuteReader();
    $nivel = $info[0]['DEGREE'];##Detectar el nivel de maestria en semestral
}
##############################################################################################################
##Obtener los datos de la Beca en Power Campus
$query = "SELECT s.ScholarshipLevel nivelbk, s2.ScholarshipType tipobk, 
(SELECT CASE a.ACADEMIC_TERM WHEN 'SEMESTREA' THEN 'S' WHEN 'SEMESTREB' THEN 'S' WHEN 'CUATRIMESA' THEN 'C'WHEN 'CUATRIMESB' THEN 'C'WHEN 'CUATRIMESC' THEN 'C' END) periodobk
FROM ScholarshipOfferingLevel s
INNER JOIN ScholarshipOffering s2
ON s.ScholarshipOfferingId = s2.ScholarshipOfferingId
INNER JOIN ACADEMICCALENDAR a
ON a.SessionPeriodId = s2.SessionPeriodId
WHERE s.ScholarshipOfferingLevelId = $otorgado;";
$cmd = new mssqlCommand($query,$con);
$info = $cmd->ExecuteReader();
//var_dump($info);
$nivelbk    = $info[0]['nivelbk'];##Indica la carta a mostrar con el porcentaje de beca
$tipobk     = $info[0]['tipobk'];##Indica la carpeta con el tipo de beca a mostrar
$periodobk  = $info[0]['periodobk'];##Indica el periodo de la carta a mostrar

##Carta de aceptacion, si vuelve a entrar al sistema
$query_docs = "SELECT url, estatus, notas FROM beca_aceptar WHERE folio = $folio;";
$cmd = new mssqlCommand($query_docs,$siieCon);
$infocarta = $cmd->ExecuteReader();
$localizar  = $infocarta[0]['url'];//url del documento
$estatus    = $infocarta[0]['estatus'];//estatus del documento, 0 en espera, 1 aceptado, 2 rechazado
$notas      = $infocarta[0]['notas'];//Notas del documento si este esta mal

?>
<html>
    <title>
        Becas
    </title>
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <style type="text/css">
	<!--
	body,td,th {
		font-family: Arial, Helvetica, sans-serif;
		font-size: 12px;
                border-collapse: collapse; 
                border: 1px solid black;
	}
        -->
        </style>
</head>
<body topmargin="0"> 
    <div style="text-align:right; padding:5px;">
        <button type="button" style="color: #174ae0; font-size: 15px; padding: 10px;" onclick="recarga()">
                Salir
        </button>
    </div>
    
     <div id="sep3" style="text-align:center; padding:15px;">
        <div style="background-color:#F7F7F7; text-align:center; padding:15px">
            <div style="font-weight: bold; font-size: 20px;">
                <div style="font-weight: bold; font-size: 30px;">FELICIDADES <?php echo utf8_decode($alumno);?>!!, TU BECA HA SIDO ACEPTADA</div>
                <p>Ofrecemos una disculpa por el atraso en la entrega de los lineamientos de aplicación de BECA/CONVENIO. Estos serán enviados el día lunes 10 de septiembre a partir de las 17:00 hrs.</p>
                <p>Favor de ingresar al sistema en la fecha y hora indicada para concluir tu proceso. GRACIAS POR TU COMPRENSIÓN.</p>
<!--                <p>Los lineamientos de aplicación de Beca serán enviados el día Lunes 20 de Agosto a partir de las 17:00 hrs.</p>
                <p>por favor ingresar al sistema en esa fecha y hora para concluir con tu proceso.</p>
                <p>Gracias.</p>-->
            </div>
            <div>
                <img src="img/checklist.png" style="width: 25%;" />
            </div>
        </div>
     </div>
    
    <div id="sep2" style="text-align:center; padding:15px;">
        <div style="background-color:#F7F7F7; text-align:center; padding:15px">
            <?php
                if($estado_beca == 2){
            ?>
                <div style="font-weight: bold; font-size: 20px;">LO SENTIMOS, TU BECA HA SIDO DENEGADA, PARA MAYOR INFORMACIÓN ACUDE A CONTROL ECONOMICO.</div>
            <?php
                }else{
            ?>
                <p><div style="font-weight: bold; font-size: 30px;">FELICIDADES <?php echo utf8_decode($alumno);?>!!, TU BECA HA SIDO ACEPTADA</div></p>
                <p><div id="c1" style="font-weight: bold; font-size: 30px;">Lee atentamente este documento, debes descargarlo, imprimirlo, firmalo y subirlo nuevamente al sistema.</div></p>
                <?$nivelbk = trim($nivelbk, '%'); ?>
                <iframe id="c2" src="https://aspaa.uvp.mx/becas/otorgado/<?=$tipobk?>/<?=$nivelbk.$periodobk.$proceso?>.pdf" style="width:100%; height:800px;" frameborder="0">
                </iframe>
                
                <p>
                    <div id="documentos" style="text-align:center; padding:15px;">
                        <div style="width: 90%; margin-left: 5%; background-color:#F7F7F7; text-align:center; padding:15px; font-weight: bold; font-size: 18px;">
                            <div>Subir Documento Firmado</div>
                            <div style="font-size: 18px; text-align: justify;">
                                Indicaciones: 
                                <div id="ind1">
                                    <br>*DESCARGAR EL DOCUMENTO E IMPRIMIRLO
                                    <br>*COLOCAR NOMBRE, FECHA Y FIRMA
                                    <br>*SUBIR EL DOCUMENTO FIRMADO NUEVAMENTE AL SISTEMA DE BECAS
                                </div>
                                <br>*Archivos permitidos pdf y jpg
                                <br>*El nombre del documento no debe tener "." intermedio
                                <br>*El documento debe ser legible para  su aceptación
                                
                                <br>Recuerda que este es el ultimo paso para que la Beca sea aplicada.
                                <br>Una vez cargado el documento vuelve a entrar al sistema y verifica que tenga el nombre correcto, al abrir el documento este tiene que mostrar el nombre de la siguiente manera.
                                <br>https://aspaa.uvp.mx/becas/carta_aceptacion/(tu matricula 9 digitos)CARTA.(pdf o jpg), si este no es asi, comunicate a Programación Ext. 734
                                <br>Si el documento tiene algun detalle en el nombre o extensión, este sera eliminado por el sistema, revisa las indicaciones correctamente, gracias.
                            </div>
                            <br>
                                <fieldset>
                                    <form id="archivo" name="archivo">
                                        <input type="hidden" id="recfol" name="recfol" value="<?=$clave_recibo?>">
                                        <table border="0" style="padding: 5px" width="100%;">
                                            <thead>
                                                <th>Documento</th>
                                                <th>Seleccionar y Subir Documento</th>
                                                <th>Vista del documento</th>
                                                <th>Notas del documento</th>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="text-align:center;">Carta de Aceptación de Beca</td>
                                                    <td style="text-align:center;">
                                                        <?php 
                                                            function tipo_imagen($enlace){
                                                                if (!empty($enlace)) {
                                                                    $d = explode(".", $enlace);
                                                                    $tipod = $d[3];
                                                                    if($tipod == 'jpg' || $tipod == 'JPG' || $tipod == 'jpeg' || $tipod == 'png'){
                                                                        ##logo de imagen
                                                                        $icono = 'img.png';
                                                                    }else if($tipod == 'pdf' || $tipod == 'PDF'){
                                                                        ##Logo de pdf
                                                                        $icono = 'pdf.png';
                                                                    }else if($tipod == 'docx'){
                                                                        ##logo de doc
                                                                        $icono = 'word.png';
                                                                    }else{
                                                                        ##otro archivo
                                                                        $icono = 'file.png';
                                                                    }
                                                                }else{
                                                                  $icono = 'nofile.png';
                                                                }
                                                                return $icono;
                                                            }
                                                        
                                                            if($estatus == '' || $estatus == 2)
                                                            {
                                                        ?>
                                                            <input type="file" id="carta" name="carta" style="cursor:pointer;"/>
                                                        <?php
                                                            }
                                                            else
                                                            {
                                                        ?>
                                                            Tu Documento ha sido guardado correctamente, espera su revisión.
                                                        <?php
                                                            }
                                                        ?>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <a href="<?=$localizar?>" target="_blank">
                                                            <img src="img/<?=tipo_imagen($localizar)?>" style="width:70px;height:70px;border:0;">
                                                        </a>
                                                    </td>
                                                    <td style="text-align:justify; font-size: 18px; font-weight: bold;"><?php echo $notas;?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                    </div>
                    <div id="botones" style="text-align:center; padding:15px;">
                        <fieldset>
                            <button type="button" id="save3" style="color: #174ae0; font-weight: bold; font-size: 15px; padding: 10px;">
                                Guardar Documento
                            </button>
                        </fieldset>
                    </div>
                <div id="mensaje" style="text-align:center; padding:15px; font-size: 15px;">
                    <fieldset>
                        <div id="espera">TU INFORMACION ESTA SIENDO PROCESADA, ESPERA UN MOMENTO POR FAVOR.</div>
                        <div id="aviso">TU DOCUMENTO ESTA SIENDO REVISADO, UNA VEZ ACEPTADO SE APLICARA LA BECA CORRESPONDIENTE, VERIFICA QUE EL DOCUMENTO ESTE CORRECTO SOLO DA CLIC EN LA VISTA DEL DOCUMENTO, MANTENTE AL PENDIENTE DEL SISTEMA PARA VERIFICAR LA APLICACIÓN, SI TIENES DUDAS DEL TIEMPO DE APLIACION COMUNICATE A CONTROL ECONOMICO EXT 188, SI TIENES PROBLEMAS CON EL PORTAL DE BECAS O TU DOCUMENTO COMUNICATE A PROGRAMACIÓN EXT 734.</div>
                    </fieldset>
                </div>
                </p>
            <?php
                }
            ?>
        </div>
    </div>
</body>
<script type="text/javascript" src="../jquery/jquery-3.1.0.min.js"></script>
<script>
    $(document).ready(function () 
    {
        var tipobk      = '<?=$tipobk;?>'; //ACADEMICA
        var periodobk   = '<?=$periodobk;?>'; //C o S
        
        /*Tipos de Beca tipobk
        A.P.I.T.	A.P.I.T.
        ACADEMICA	RENDIMIENTO ACADEMICO
        AJUSTE		AJUSTE
        ALMAMATER	ALMAMATER
        APOYOTRABA	APOYO A LOS TRABAJADORES (APTI)
        DEPARTAMEN	DEPARTAMENTAL
        DEPOCULT	DEPORTIVA Y CULTURAL
        DEPTO		DEPARTAMENTAL
        DESCUENTO	DESCUENTO PAGOS/TEMPRANO/SEMESTRAL
        EMPRESARIA	EMPRESARIAL
        ESPECIAL	ESPECIAL
        EXCELENCIA	EXCELENCIA ACADEMICA
        FAMILIAR	FAMILIAR
        INMEMORIAM	INMEMORIA
        PROMOCIONA	PROMOCIONAL
        TRABCAT		TRABAJADORES Y CATEDRATICOS
        */

        if(tipobk == 'EMPRESARIA' || tipobk == 'ACADEMICA' || tipobk == 'PROMOCIONA' || tipobk == 'ESPECIAL' || tipobk == 'DEPOCULT' || tipobk == 'FAMILIAR' || tipobk == 'TRABCAT' || tipobk == 'A.P.I.T.' || tipobk == 'DEPARTAMEN'  ){
            if(periodobk == 'S' || periodobk == 'C'){
                $('#sep2').show();
                $('#sep3').hide();
                
                $('#mensaje,#espera,#aviso').hide();
                var estatus_doc = '<?=$estatus?>'; //0 en espera, 1 aceptado, 2 rechazado
                //alert(estatus_doc);
                if(estatus_doc == ''){
                    $('#c1,#c2,#botones,#espera,#ind1').show();
                    $('#mensaje,#aviso').hide();
                }
                else if(estatus_doc == 0){
                    $('#c1,#c2,#botones,#espera,#ind1').hide();
                    $('#mensaje,#aviso').show();
                }
                else if(estatus_doc == 2){
                    $('#c1,#c2,#botones,#espera,#ind1').show();
                    $('#mensaje,#aviso').hide();
                }
            }
            else {
                $('#sep2').hide();
                $('#sep3').show();
                
            }
        } 
        else{
            $('#sep2').hide();
            $('#sep3').show();
        }
    });
    /***********************************************************************************/
    /*Envio de Documentos*/
    $('#save3').click(function()
    {
        /*Ocultamos el formulario y se muestra el aviso de espera*/
        $("#mensaje,#espera").show();
        $("#documentos,#botones,#aviso").hide();
            //Obtiene los datos del formulario con class=formulario para postearlos, necesario para subir archivos
            var data = new FormData($("#archivo")[0]);
            $.ajax({
                url: 'guarda_carta_beca.php',
                type: 'POST',
                data: data,
                //necesario para subir archivos via ajax
                cache: false,
                contentType: false,
                processData: false,
                success: function (resultado) {
                    if(resultado === 'success')
                    {								
                        //alert(message);
                        alert('Su Documento han sido guardado correctamente, por favor verifica que el documento este correcto. Gracias.');
                        window.setTimeout("volver()",2000);
                    }
                    else if(resultado === 'failext')
                    {
                        alert('La extensión del documento no esta permitida, debe ser pdf o jpg, revise su documento antes de subirlo. Gracias.');
                        window.setTimeout("volver()",2000);
                    }
                    else
                    {
                        alert('Ocurrio un error al subir el documentos, intentelo nuevamente!');
                        window.setTimeout("recarga()",2000);
                    }
                }
            });
        return false;
    });
    
    //Mostrar Docs sin salir de la pagina
    function volver(){
        location.reload();
    }
    
    function recarga(){
    //location.reload();
    //url = "index.php?cierra=1";
    url = "logout.php";
    $(location).attr('href',url);
}
</script>
</html>