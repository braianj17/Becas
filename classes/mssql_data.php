<?php 
include_once "classes/mssql_connection.php";
include_once "classes/mssqlCommand.php";

class mssDataCollection{
	public $msSql,$con;

	public function __construct($host,$user,$passwd,$initial_catalog){
		$this->msSql = new mssqlCnx($host,$user,$passwd,$initial_catalog);
		$this->con=$this->msSql->Open();		
	}
	
	public function dictionary($table,$key_field,$value_field,$filter=array(),$order){
		$result=array();
		$query_filter="";

		if(count($filter)>0){
			$filter_string=implode(" AND ",$filter);
			$query_filter=" WHERE ".$filter_string;
		}
		
		if(isset($order) && $order!="" && $order!=NULL)
			$strorder=" ORDER BY ".$order;
		else
			$strorder="";			
			
		
		$query_string="SELECT ".$key_field." as key_field,".$value_field." as value_field FROM ".$table.$query_filter.$strorder;
		$cmd= new mssqlCommand($query_string,$this->con);
		$result=$cmd->executeReader();
		if($cmd->numResults>0){
		if($cmd->numResults>1){
			return $result;	
		}else{
			$result_full=array();
			array_push($result_full,$result);
			return $result_full;
		}
		}else{
			return null;
		}
	}

	public function odictionary($table,$key_field,$value_field,$filter=array(),$order){
		$result=array();
		$query_filter="";	
		if(count($filter)>0){
			$filter_string=implode(" AND ",$filter);
			$query_filter=" WHERE ".$filter_string;
		}
		$query_string="SELECT ".$key_field." as key_field,".$value_field." as value_field FROM ".$table.$query_filter." order by ".$order;
		$cmd= new mssqlCommand($query_string,$this->con);
		$result=$cmd->executeReader();
		
		if($cmd->numResults>1){
			return $result;	
		}else{
			$result_full=array();
			array_push($result_full,$result);
			return $result_full;
		}
	}


	public function genericDictionary($table,$key_field,$value_field,$filter=array()){
		$result=array();
		$query_filter="";	
		if(count($filter)>0){
			$filter_string=implode(" AND ",$filter);
			$query_filter=" WHERE ".$filter_string;
		}
		$query_string="SELECT ".$key_field." as key_field,".$value_field." as value_field FROM ".$table.$query_filter;
		$cmd= new mssqlCommand($query_string,$this->con);
		$result=$cmd->executeDictionary();
		return $result;	
	}
	
	
}

?>