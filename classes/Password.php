<?php 
class Password{
	private $Name;
	public $value;
	public $txtStyle;	

	
	public function __construct($objName){
		$this->Name=$objName;
		$this->txtStyle="";			
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
	
	
	public function Dispose(){
		$this->objStructure="<input type=\"password\" name=\"".$this->Name."\" id=\"".$this->Name."\" value=\"".$this->value."\" ".$this->txtStyle.">";
		return $this->objStructure;
	}
}

?>