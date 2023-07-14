<?php 
class phpComboBox{
	private $Name;
	private $trigerEvent;
	private $cliFunction;
	private $cliParam;
	public $selectedIndex;
	
	public function __construct($objName,$trigerEvent,$cliFunction,$cliParam){
		$this->Name=$objName;
		$this->trigerEvent=$trigerEvent;
		$this->cliFunction=$cliFunction;	
		$this->cliParam=$cliParam;			
	}

	public function getSelectedIndex($defaultValue){
		if(isset($_POST[$this->Name])){
			if($_POST[$this->Name]==$defaultValue){
				$SelIndex=0;			
			}else{
				$SelIndex=$_POST[$this->Name];
			}
		
		}else{
			$SelIndex=0;
		}
		return $SelIndex;
	}
	
	public function setSelectedIndex($selectedIndex){
		$this->selectedIndex=$selectedIndex;	
	}
	
	
	public function DataSource($dictionary=array()){
		switch($this->trigerEvent){
			case "change";
				$jsEvent=" onchange=\"".$this->cliFunction."()\"";
			break;
			case "blur":
				$jsEvent=" onblur=\"".$this->cliFunction."()\"";
			break;
			case "focus":
				$jsEvent=" onfocus=\"".$this->cliFunction."()\"";			
			break;
			default:
				$jsEvent="";						
			break;
		}
	
	
		$this->objStructure="<select name=\"".$this->Name."\" id=\"".$this->Name."\" ".$jsEvent." >";
		if($this->selectedIndex<1){
			$this->objStructure.="<option value=\"0\" > --- </option>";			
		}
		
		$selected="";
		$found=false;
		if(count($dictionary)>0){
			if(isset($dictionary[0]) && is_array($dictionary[0])){
				foreach($dictionary as $TableRow){
					if($this->selectedIndex==$TableRow["key_field"]){
						$found=true;
						$selected=" selected=\"selected\"";
					}else{
						$selected="";
					}
					$this->objStructure.="<option value=\"".$TableRow["key_field"]."\"".$selected.">".$TableRow["value_field"]."</option>";
				}
			}else{
				$this->objStructure.="<option value=\"".$dictionary["key_field"]."\"".$selected.">".$dictionary["value_field"]."</option>";			
			}
		}
		if($found==false)
			$this->objStructure.="<option value=\"0\" selected> --- </option>";			
		$this->objStructure.="</select>";
	}
	public function Dispose(){
		return $this->objStructure;
	}
}

?>