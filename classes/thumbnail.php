<?
class Thumb{
	private $ImageName;
	private $pathToImages;
	private $pathToThumbs;
	private $thumbWidth;
	/*
	[IMAGETYPE_GIF] => 1
	[IMAGETYPE_JPEG] => 2
	[IMAGETYPE_PNG] => 3
	*/
	public function __construct($ImageName, $pathToImages, $pathToThumbs, $thumbWidth){
		$error=false;
		$this->ImageName=$ImageName;
		$this->pathToImages=$pathToImages;
		$this->pathToThumbs=$pathToThumbs;	
		$this->thumbWidth=$thumbWidth;	
		
		if(isset($this->ImageName) && isset($this->pathToImages) && isset($this->pathToThumbs) && isset($this->thumbWidth)){
			if(file_exists($this->pathToImages.$this->ImageName)){		
				list($width, $height,$imagetype,$attrib) = getimagesize($pathToImages.$ImageName);

				switch($imagetype){
					case "1";
					
						$img = imagecreatefromgif( "{$pathToImages}{$ImageName}" );
					break;
					case "2";
						$img = imagecreatefromjpeg( "{$pathToImages}{$ImageName}" );
					break;
					case "3";
						$img = imagecreatefrompng( "{$pathToImages}{$ImageName}" );			
					break;
					default:
						$error=true;
						echo "<script>alert('Solo se aceptan imagenes jpg,gif o png')</script>";
					break;
				}
				if($error==false){	
					try {
					    // calculate thumbnail size
					    $new_width = $thumbWidth;
					    $new_height = floor( $height * ( $thumbWidth / $width ) );
					    // create a new temporary image
					    $tmp_img = imagecreatetruecolor( $new_width, $new_height );
					    // copy and resize old image into new image 
					    imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
					    // save thumbnail into a file
					    imagejpeg( $tmp_img, "{$pathToThumbs}{$ImageName}" );
					} catch (Exception $e) {
						$this->Error=3;
					    echo 'Error: ',  $e->getMessage(), "\n";
					}				
				    
				}
			}else{
				$error=true;
				echo "<script>alert('Error al abrir el archivo, no existe..')</script>";
			}
		}else{
			$error=true;
			echo "<script>alert('Falta algun parametro')</script>";
		}
		return $error;
	}
}
?>
