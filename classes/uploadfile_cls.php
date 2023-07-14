<?
####################3UPLOAD OPTIONS#############################
$filetype_desc="Hoja de Calculo de Excel";
$VALID_MIME=array("application/vnd.ms-excel","application/msexcel","application/x-msexcel","application/x-ms-excel","application/x-excel","application/x-dos_ms_excel","application/xls","application/x-xls","application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
$FILESIZE_LIMIT="800480";

function UPLOAD_FILE($VALID_MIME,$FILESIZE_LIMIT,$THE_FILE,$DESTINATION){
	if(isset($THE_FILE) && isset($DESTINATION)){
		if (in_array($THE_FILE['type'],$VALID_MIME)){
			if($THE_FILE['size'] <= $FILESIZE_LIMIT){
				if ($THE_FILE['error'] > 0){
					$PHOTO_MSG="Error desconocido: ".$THE_FILE['error'];
				}else{
					if (file_exists($DESTINATION."/" . $THE_FILE['name'])){
						$PHOTO_MSG="El archivo: ".$THE_FILE['name'].", ya existe!";
					}else{
						move_uploaded_file($THE_FILE['tmp_name'],$DESTINATION."/" . $THE_FILE['name']);
						$PHOTO_MSG="El archivo: ".$THE_FILE['name'].", fue copiado con éxito!";
					}
				}
			}else{
				$PHOTO_MSG="El archivo que intenta cargar es demasiado grande, solo se admiten archivos hasta de ".($FILESIZE_LIMIT/1024)." Kb";
			}
		}else{
			$PHOTO_MSG="El archivo que intenta cargar tiene un formato no válido(".$THE_FILE['type']."), solo se admiten archivos de tipo ".$filetype_desc;
		}
	}else{
		$PHOTO_MSG="Debe elegir un archivo y destino válidos";	
	}
	?>
	<script>alert('<?=$PHOTO_MSG?>')</script>
<? } ?>