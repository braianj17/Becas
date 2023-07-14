<?php 
class TTextBox{
	private $Name;
	public $value;
	public $txtStyle;
	public $enabled;
	private $trigerEvent;
	private $cliFunction;
	private $cliParam;
	public $jsEvent;
	public function __construct($objName,$trigerEvent,$cliFunction,$cliParam){
		$this->Name=$objName;
		$this->txtStyle="";
		$this->readonly="";		
		
		$this->trigerEvent=$trigerEvent;				
		$this->cliFunction=$cliFunction;				
		$this->cliParam=$cliParam;						
		switch($this->trigerEvent){
			case "change";
				$this->jsEvent=" onchange=\"".$this->cliFunction."('".$this->cliParam."')\"";
			break;
			case "blur":
				$this->jsEvent=" onblur=\"".$this->cliFunction."('".$this->cliParam."')\"";
			break;
			case "focus":
				$this->jsEvent=" onfocus=\"".$this->cliFunction."('".$this->cliParam."')\"";			
			break;
			default:
				$this->jsEvent="seeee";						
			break;
		}
	}



	public function getValue($defaultValue){
		if(isset($_POST[$this->Name]) && $_POST[$this->Name]!=$defaultValue){
			$value=$_POST[$this->Name];
		}else{
			$value=$defaultValue;
		}
		return $value;
	}
	
	public function setValue($value){
		$this->value=$value;	
	}
	
	public function setClass($param){
		$this->txtStyle=" class=\"".$param."\" ";
	}
	
	public function setReadonly(){
		$this->readonly=" readonly ";
	}


	public function Dispose(){
		$this->objStructure="<input type=\"text\" name=\"".$this->Name."\" id=\"".$this->Name."\" value=\"".$this->value."\" ".$this->txtStyle." onKeyPress=\"return filterInput(3, event, false)\" ".$this->readonly." ".$this->jsEvent.">";
		return $this->objStructure;
	}
}

?>