<?php 
class TextArea{
	private $Name;
	public $value;
	public $txtStyle;
	public $enabled;
	
	public function __construct($objName){
		$this->Name=$objName;
		$this->txtStyle="";
		$this->readonly="";		
		
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
		$this->objStructure="<textarea name=\"".$this->Name."\" id=\"".$this->Name."\" onKeyPress=\"return filterInput(3, event, false)\" ".$this->readonly." ".$this->txtStyle.">".$this->value."</textarea>";
		return $this->objStructure;
	}
}

?>