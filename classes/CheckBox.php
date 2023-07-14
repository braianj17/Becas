<?php 
class CheckBox{
	private $Name;
	private $trigerEvent;
	private $cliFunction;
	public $Value;	
	public $Checked;
	
	public function __construct($objName,$trigerEvent,$cliFunction,$value,$checked){
		$this->Name=$objName;
		$this->trigerEvent=$trigerEvent;
		$this->cliFunction=$cliFunction;	
		$this->Value=$value;					
		$this->Checked=$checked;			
	}

	public function getState(){
		if(!isset($_POST[$this->Name]) || $_POST[$this->Name]!=$this->Value){
			$this->Checked=false;
		}else{
			$this->Checked=true;
		}
		return $this->Checked;
	}
		
	public function Build(){
		switch($this->trigerEvent){
			case "click";
				$jsEvent=" onclick=\"".$this->cliFunction."()\"";
			break;
			default:
				$jsEvent="";						
			break;
		}
		$this->objStructure="<input type=\"checkbox\" name=\"".$this->Name."\" id=\"".$this->Name."\" value=\"".$this->Value."\" ".$jsEvent." ";
		if($this->Checked==true){
			$this->objStructure.="checked";			
		}
		$this->objStructure.=">";
		return $this->objStructure;
	}
}

?>