<?php
session_start();
include "include/session_check.php";
require_once("../conexiones/conecta_alu.php");
include_once "../classes/mssql_data.php";
include_once("../classes/db_params.php");

### MSSQL CONNECTION SIIE
$siieSql= new  mssqlCnx($siie_server_address,$siie_dbuser,$siie_dbpasswd,$siie_db);
$siieCon=$siieSql->Open();

$idpc   = $_SESSION["alumno"];##People Id del alumno 9 digitos
$folio  = $_SESSION["recibo"];##Folio, numero de recibo

##################################################################################
##Este es el sistema para la documentacion de la beca                           ##
##En este sitio se mostrara la interfaz para la administracion de los           ##
##documentos del alumno.                                                        ##
##Se detectara el tipo de beca solicitada y el proceso a realizar para el tipo  ##
##beca, esto para mostrar los campos si es renovacion o nueva beca              ##
##                                                                              ##
##Renovacion de Beca, esto solo aplica a la beca de convenio empresarial en la  ##
##cual solo se solicitaran 2 documentos, Credencial y Boleta                    ##
##                                                                              ##
## Actualizacion 12-01-2018 se implemento un nuenvo documento en la beca de     ##
##convenio, se agrego el comprobante de ingresos de los ultimos 2 meses         ##
##Para la beca de rendimiento académico se tomara en cuenta un solo documento   ##
##el resolutivo de la beca anterior, en caso de tenerla, de lo contrario se     ##
##agregara un documento indicando que no aplica                                 ##
##                                                                              ##
##Nueva Beca, al solicitar una nueva Beca, se mostraran los documentos          ##
##solicitados para el tipo de Beca, hasta el momento solo 2 tipo de beca        ##
##requieren documentos, Rendimiento Academico y Convenio Empresarial            ##
##################################################################################

##Se obtiene la informacion de los documentos que el alumno ha subido, para verificarlos
$query_docs = "SELECT nombredoc, localizacion, estatus, notas, doc FROM beca_documentos WHERE folio = '$folio' ORDER BY doc;";
$cmd = new mssqlCommand($query_docs,$siieCon);
$info = $cmd->ExecuteReader(true);

##Valores principales del arreglo
$iddocumento    = array('sd');
$estados        = array('3');
$localizacion   = array('');
$notas          = array('');

if(count($info) > 0)
{
    $existe = 1;
    foreach ($info as $valor)
    {
        ##Arreglo de informacion de los documentos
        $iddocumento[]  = $valor['doc'];            ##Id del documento d1 foto, d2 mapa, d3 boleta, d4 ingresos, d5 inscripcion, d6 domicilio, d7 curp, d8 beca anterior
        $estados[]      = $valor['estatus'];        ##Estatus del documento 0 en revision, 1 aceptado, 2 rechazado
        $localizacion[] = $valor['localizacion'];   ##URL del documento
        $notas[]        = $valor['notas'];          ##Notas del documento rechazado
    }   
}
else
{
    $existe = 0;
}

//$existe;
##Arreglo de estatus de documentos
$msjestatus = array(3 =>'Agrega el Documento', 0 => 'Tu Documento esta siendo REVISADO por Control Economico.', 1 => 'Tu Documento ha sido ACEPTADO.', 2 => 'Tu Documento fue RECHAZADO, verifica en las notas el motivo y elige un nuevo documento.');

##Funcion para mostrar el icono del documento en el sistema
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
                    <legend>Documentación</legend>
                    <p>Revisa los Documentos solicitados para la Beca.</p>
                    <p>Si ya subiste documentos, verifica el documento dando clic en la VISTA asi como el estatus de los mismos, y realiza los cambios de ser necesario, YA NO ES NECESARIO DAR CLIC EN EL BOTON GUARDAR</p>
                    <p>Si aun no subes documentos, es necesario que los agregues para continuar tu proceso de Beca</p>
                    <p>Dudas con los documentos, comunicarse al Depto. de Control Económico, ext 188.</p>
                    <p>Para problemas con el sistema, comunicarse al Depto. de Programación, ext 734.</p>
                    <form id="doc_beca_convenio" name="doc_beca_convenio">
                        <table class="table table-hover table-responsive">
                            <thead>
                              <tr>
                                <th scope="col">Documento</th>
                                <th scope="col">Seleccionar archivo</th>
                                <th scope="col">Vista</th>
                                <th scope="col">Estatus</th>
                                <th scope="col">Notas</th>
                              </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php
                                        $p1     = array_search('d1',$iddocumento);##Obtener la posicion del elemento en los arreglos
                                    ?>
                                    <th scope="row">Credencial de Empleado</th>
                                    <td>
                                        <div class="form-group">
                                        <?php 
                                        if($existe == 0 || $estados[$p1] == 2  || $estados[$p1] == 3)
                                        {
                                        ?>
                                            <input type="file" class="form-control-file" id="renuevafoto" name="renuevafoto">
                                        <?php
                                        }
                                        else if($estados[$p1] == 1){ echo 'Documento Aceptado'; }
                                        elseif($estados[$p1] == 0){echo 'Espere la Revisión del Documento';}
                                        ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?=$localizacion[$p1]?>" target="_blank">
                                            <img src="img/<?=tipo_imagen($localizacion[$p1])?>" style="width:70px;height:70px;border:0;">
                                        </a>
                                    </td>
                                    <td><?=$msjestatus[$estados[$p1]]?></td>
                                    <td><?=$notas[$p1]?></td>
                                </tr>

                                <tr>
                                    <?php
                                        $p3     = array_search('d3',$iddocumento);##Obtener la posicion del elemento en los arreglos
                                    ?>
                                    <th scope="row">Boleta</th>
                                    <td>
                                        <div class="form-group">
                                        <?php 
                                        if($existe == 0 || $estados[$p3] == 2  || $estados[$p3] == 3)
                                        {
                                        ?>
                                            <input type="file" class="form-control-file" id="renuebaboleta" name="renuebaboleta">
                                        <?php
                                        }
                                        else if($estados[$p3] == 1){ echo 'Documento Aceptado'; }
                                        elseif($estados[$p3] == 0){echo 'Espere la Revisión del Documento';}
                                        ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?=$localizacion[$p3]?>" target="_blank">
                                            <img src="img/<?=tipo_imagen($localizacion[$p3])?>" style="width:70px;height:70px;border:0;">
                                        </a>
                                    </td>
                                    <td><?=$msjestatus[$estados[$p3]]?></td>
                                    <td><?=$notas[$p3]?></td>
                                </tr>
                                
                                <tr>
                                    <?php
                                        $p4     = array_search('d4',$iddocumento);##Obtener la posicion del elemento en los arreglos
                                    ?>
                                    <th scope="row">Comprobante de Ingresos(ultimos 2 meses)</th>
                                    <td>
                                        <div class="form-group">
                                        <?php 
                                        if($existe == 0 || $estados[$p4] == 2 || $estados[$p4] == 3)
                                        {
                                        ?>
                                            <input type="file" class="form-control-file" id="renuevaingresos" name="renuevaingresos">
                                        <?php
                                        }
                                        else if($estados[$p4] == 1){ echo 'Documento Aceptado'; }
                                        elseif($estados[$p4] == 0){echo 'Espere la Revisión del Documento';}
                                        ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?=$localizacion[$p4]?>" target="_blank">
                                            <img src="img/<?=tipo_imagen($localizacion[$p4])?>" style="width:70px;height:70px;border:0;">
                                        </a>
                                    </td>
                                    <td><?=$msjestatus[$estados[$p4]]?></td>
                                    <td><?=$notas[$p4]?></td>
                                </tr>
                                
                            </tbody>
                        </table>
                    </form>
                    <br>
                    <div align="right" id="btn1">
                        <button class="btn btn-lg btn-success" id="guardar" onclick="">Guardar</button>
                    </div>
                </fieldset>
            </div>
            
            <!--Mensaje al alumno-->
            <div class="form-signin" id="aviso" style="display: none; text-align: center;">
                <fieldset>
                    <legend>Estamos procesando tus Documentos, espera un momento por favor.</legend>
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
        $(document).ready(function () 
        {
            //Evitar que el modal se cierre al dar clic fuera de el
            $('#mensajes').modal({backdrop: 'static', keyboard: false});
            $("#msj").empty();
            $('#mensajes').modal('show');
            $("#msj").append('<div class="modal-header">'
                                +'<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                                    +'<span aria-hidden="true">&times;</span>'
                                +'</button>'
                            +'</div>'
                            +'<div class="modal-body">'
                                +'<div class="alert alert-info" role="alert">'
                                    +'<p>Revisa los Documentos solicitados para la Beca.</p>'
                                    +'<p>Si ya subiste documentos, verifica el estatus de los mismos, y realiza los cambios de ser necesario.</p>'
                                    +'<p>Si aun no subes documentos, es necesario que los agregues para continuar tu proceso de Beca</p>'
                                    +'<p>Dudas con los documentos, comunicarse al Depto. de Control Económico, ext 188.</p>'
                                    +'<p>Para problemas con el sistema, comunicarse al Depto. de Programación, ext 734.</p>'
                                +'</div>'
                            +'</div>');
        });
         
        /***********************************************************************************/
        /*Envio de Documentos*/
        $('#guardar').click(function()
        {
            /*Ocultamos el formulario y se muestra el aviso de espera*/
            $("#aviso").css("display", "block");
            $("#btn1").css("display", "none");

                //Obtiene los datos del formulario con class=formulario para postearlos, necesario para subir archivos
                var data = new FormData($("#doc_beca_convenio")[0]);
                $.ajax({
                    url: 'guarda_doc_convenio.php',
                    type: 'POST',
                    data: data,
                    //necesario para subir archivos via ajax
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function (resultado) {
                        if(resultado === 'success')
                        {								
                            //window.setTimeout("recarga()",2000);
                            $("#msj").empty();
                            $('#mensajes').modal('show');
                            $("#msj").append('<div class="modal-header">'
                                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="volver()">'
                                                +'<span aria-hidden="true">&times;</span>'
                                            +'</button>'
                                        +'</div>'
                                        +'<div class="modal-body">'
                                            +'<div class="alert alert-success" role="alert">'
                                                +'<p>Tus Documentos fueron ingresados correctamente.</p>'
                                                +'<p>Espera a que sean revisados por Control Economico.</p>'
                                                +'<p>Mantente atetento al sistema para conocer el Resolutivo de los documentos</p>'
                                            +'</div>'
                                        +'</div>');
                            $("#aviso").css("display", "none");
                        }
                        else
                        {
                            //window.setTimeout("recarga()",1000);
                            $("#msj").empty();
                            $('#mensajes').modal('show');
                            $("#msj").append('<div class="modal-header">'
                                            +'<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarsalir()">'
                                                +'<span aria-hidden="true">&times;</span>'
                                            +'</button>'
                                        +'</div>'
                                        +'<div class="modal-body">'
                                            +'<div class="alert alert-danger" role="alert">'
                                                +'<p>Ocurrio un problema al guardar tus documentos, intentalo nuevamente.</p>'
                                                +'<p>Si el error persiste, comunicate al Depto. de Programación, Ext. 734.</p>'
                                            +'</div>'
                                        +'</div>');
                            $("#aviso").css("display", "none");
                        }
                    }
                });
            return false;
        });
        
        //Mostrar Docs sin salir de la pagina
        function volver(){
            location.reload();
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