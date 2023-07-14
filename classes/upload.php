<?

function UPLOAD_FILE($VALID_MIME,$FILESIZE_LIMIT,$THE_FILE,$DESTINATION){
	if(isset($THE_FILE) && isset($DESTINATION)){
		if (in_array($THE_FILE['type'],$VALID_MIME)){
			if($THE_FILE['size'] <= $FILESIZE_LIMIT){
				if ($THE_FILE['error'] > 0){
					$PHOTO_MSG="Error desconocido: ".$THE_FILE['error'];
				}else{
					if (file_exists($DESTINATION."/" . $THE_FILE['name'])){
						$PHOTO_MSG="1";
					}else{
						move_uploaded_file($THE_FILE['tmp_name'],$DESTINATION."/" . $THE_FILE['name']);
						$PHOTO_MSG="1";
					}
				}
			}else{
				$PHOTO_MSG="El archivo que intenta cargar es demasiado grande, solo se admiten archivos hasta de ".($FILESIZE_LIMIT/1024)." Kb";
			}
		}else{
			$PHOTO_MSG="El archivo que intenta cargar tiene un formato no válido, solo se admiten archivos de mapa de bits JPG Y GIF ";
		}
	}else{
		$PHOTO_MSG="Debe elegir un archivo y destino válidos";	
	}
	return $PHOTO_MSG;
} ?>