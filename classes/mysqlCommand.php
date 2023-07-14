<?php 
class mysqlCommand{
	private $cmdText;
	private $connection;
	public $result=array();
	public $numResults;public $error_message;
	
	public function __construct($cmdText,$connection){
		$this->cmdText=$cmdText;
		$this->connection=$connection;
	}

	//El parámetro que recibe es para leer los datos sin importat si tiene o no caracteres especiales
	//Debe recibir true cuando se desea leer todos los caracteres
	public function ExecuteReader($htmlfilter){
	
		if($htmlfilter==null)
			$htmlfilter = false;

		$query = mysql_query($this->cmdText,$this->connection) or $this->error_message=mysql_error();
        if($query){
			$this->numResults = mysql_num_rows($query);
			
            for($i = 0; $i < $this->numResults; $i++)
            {
                $r = mysql_fetch_array($query);
                $key = array_keys($r);
                for($x = 0; $x < count($key); $x++)
                {
                    // Sanitizes keys so only alphavalues are allowed
                    if(!is_int($key[$x]))
                    {
						
							if(mysql_num_rows($query) >= 1)
								$this->result[$i][$key[$x]] = ($r[$key[$x]]);
							else
								$this->result = null;
						
                    }
                }
            }
			if(is_resource($query))
				mysql_free_result($query);
		}

		return $this->result;	
	}
	
	
	public function ExecuteDictionary(){
		$query = mysql_query($this->cmdText,$this->connection);
        if($query){
			$this->numResults = mysql_num_rows($query);
            for($i = 0; $i <= $this->numResults; $i++)
            {
				//echo 'valor '.$i.'-'.$r["value_field"];
                $r = mysql_fetch_array($query);
                $this->result[$r["key_field"]] = $r["value_field"];
            }
			if(is_resource($query))
				mysql_free_result($query);
		}
		return $this->result;	
	}
	
	
	
	public function ExecuteNonQuery(){
		$affected_rows=0;
		try{
			$query = mysql_query($this->cmdText,$this->connection) or $this->error_message=mysql_error();
	        if($query){
				$affected_rows = mysql_affected_rows($this->connection);
				if(is_resource($query))
					mysql_free_result($query);
				if($affected_rows<=0){
					$this->error_message=@mysql_error();
				}
					
			}else{
				$this->error_message=@mysql_error();
			}
		}catch(Exception $ex){
			throw new Exception("Error en la instrucción");
		}

		return $affected_rows;	
	}
	
	
	
	
}

?>