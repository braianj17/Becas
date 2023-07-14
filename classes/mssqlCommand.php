<?php 
class mssqlCommand{
	private $cmdText;
	private $connection;
	public $result=array();
	public $numResults;
	public $error_message;
	
	public function __construct($cmdText,$connection){
		$this->cmdText=$cmdText;
		$this->connection=$connection;
	}	
	
	//El parámetro que recibe es para leer los datos sin importat si tiene o no caracteres especiales
	//Debe recibir true cuando se desea leer todos los caracteres
	public function ExecuteReader($htmlfilter = false){

		$params = array();
		// $options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
		// $query = sqlsrv_query($this->connection,$this->cmdText, $params, $options );	
		$query= mssql_query( $this->cmdText, $this->connection );
		$this->result = null;
		while ($row = mssql_fetch_array( $query ) ) {
			$row = array_map( 'utf8_decode', $row );
			$this->result[] = $row;
		}
		
		if ($query) {
			$this->numResults = mssql_num_rows( $query );
		}

		/*if($query){
			$this->numResults = sqlsrv_num_rows($query);
			
            for($i = 0; $i < $this->numResults; $i++)
            {
				$r=sqlsrv_fetch_array( $query, SQLSRV_FETCH_ASSOC);			
                $key = array_keys($r);
                for($x = 0; $x < count($key); $x++)
                {
                    // Sanitizes keys so only alphavalues are allowed
					
                    if(!is_int($key[$x]))
                    {
						if(!$htmlfilter)
							if(sqlsrv_num_rows($query) >= 1)
								$this->result[$i][$key[$x]] = utf8_encode($r[$key[$x]]);
							else
								$this->result = null;
						else
							if(sqlsrv_num_rows($query) >= 1)
								$this->result[$i][$key[$x]] = $r[$key[$x]];
							else
								$this->result = null;
                    }
                }
            }
		}*/

		return $this->result;	
	}


	
	public function ExecuteNonEscalar(){
		$tempResult;
		$query = sqlsrv_query($this->connection,$this->cmdText);		
        if($query){
			$this->numResults = sqlsrv_num_rows($query);
			
            for($i = 0; $i < $this->numResults; $i++)
            {
				$r=sqlsrv_fetch_array( $query, SQLSRV_FETCH_ASSOC);			
                $key = array_keys($r);
                for($x = 0; $x < count($key); $x++)
                {
                    // Sanitizes keys so only alphavalues are allowed
                    if(!is_int($key[$x]))
                    {
                        if(sqlsrv_num_rows($query) >= 1)
                             $tempResult[$i][$key[$x]] = $r[$key[$x]];
                        else
                            $tempResult = null;
                    }
                }
            }
			
			
			### prepare result
			if($this->numResults>1){
				$this->result=$tempResult;
			}else{
				if($this->numResults>0){
					array_push($this->result,$tempResult);
				}
			}	
		}
		return $this->result;	
	}	
	
	
	public function ExecuteFetchRow(){
		$tempResult;
		$query = sqlsrv_query($this->connection,$this->cmdText);				
        if($query){
			$this->numResults = sqlsrv_num_rows($query);
			
            for($i = 0; $i < $this->numResults; $i++)
            {
				$r=sqlsrv_fetch_array( $query, SQLSRV_FETCH_ASSOC);			
                $key = array_keys($r);
                for($x = 0; $x < count($key); $x++)
                {
                    // Sanitizes keys so only alphavalues are allowed
                    if(!is_int($key[$x]))
                    {
                        if(sqlsrv_num_rows($query) >= 1)
                             $tempResult[$i][$x] = $r[$key[$x]];
                        else
                            $tempResult = null;
                    }
                }
            }
			
			
			### prepare result
			if($this->numResults>1){
				$this->result=$tempResult;
			}else{
				if($this->numResults>0){
					array_push($this->result,$tempResult);
				}
			}	
		}

		return $this->result;	
	}		
	
	
	public function ExecuteNonQuery(){
		$affected_rows=0;
		try{				
			// $query = sqlsrv_query($this->connection,$this->cmdText);
			$query = mssql_query( $this->cmdText, $this->connection );		
	        if($query){
				// $affected_rows = sqlsrv_rows_affected($this->connection);
				$affected_rows = mssql_rows_affected( $this->connection );
			}else{
				//echo @mysql_error();
			}
		}catch(Exception $ex){
			throw new Exception("Error en la instrucción");
		}

		return $affected_rows;	
	}

}
?>