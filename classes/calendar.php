<?
class Calendar{
	public function finddow($fvalue){
		list($ano,$mes,$dia)=explode("-",$fvalue);
		$initials=strftime("%a", mktime(0,0,0,$mes,$dia,$ano));
		switch($initials){
			case "Sun":
				$dow="Domingo";
			break;
			case "Mon":
				$dow="Lunes";
			break;
			case "Tue":
				$dow="Martes";
			break;
			case "Wed":
				$dow="Miércoles";
			break;
			case "Thu":
				$dow="Jueves";
			break;
			case "Fri":
				$dow="Viernes";
			break;
			case "Sat":
				$dow="Sábado";	
			break;
		}
		return $dow;	
	}
	
	
	public function calcWeeks($Month,$Year){
		$result=array();
		$last_day="";
		$week=1;
		$weeks=array();
		$DAYS_MONTH=cal_days_in_month(CAL_GREGORIAN,$Month,$Year);
		for($i=1; $i<=$DAYS_MONTH; $i++){
			$Day=$i;
			if($i<10){
				$Day="0".intval($i);
			}
				
			if($last_day==""){
				if(!isset($weeks[$week])){
					$weeks[$week]=array();
				}
			}else{
				if($this->finddow($Year."-".$Month."-".$Day)=="Lunes"){
					$week++;
					if(!isset($weeks[$week])){
						$weeks[$week]=array();
					}
				}		
			}
			array_push($weeks[$week],$i);
			$last_day=$i;
		}
		foreach($weeks as $weekNo=>$weekLst){
			$result[$weekNo]=implode(",",$weekLst);
		}
		return $result;
	}

	public function calcWeeksNames($Month,$Year){
		$result=array();
		$last_day="";
		$week=1;
		$weeks=array();
		$DAYS_MONTH=cal_days_in_month(CAL_GREGORIAN,$Month,$Year);
		for($i=1; $i<=$DAYS_MONTH; $i++){
			$Day=$i;
			if($i<10){
				$Day="0".intval($i);
			}
				
			if($last_day==""){
				if(!isset($weeks[$week])){
					$weeks[$week]=array();
				}
			}else{
				if($this->finddow($Year."-".$Month."-".$Day)=="Lunes"){
					$week++;
					if(!isset($weeks[$week])){
						$weeks[$week]=array();
					}
				}		
			}
			$weeks[$week][$i]=$this->finddow($Year."-".$Month."-".$Day);
			$last_day=$i;
		}
		foreach($weeks as $weekNo=>$weekLst){
			$result[$weekNo]=implode(",",$weekLst);
		}
		return $result;
	}

	public function getNumDay($days_arr=array(),$strDay,$Month,$Year){
		$numDay="";
		foreach($days_arr as $key=>$val){
			$day=$val;
			if($val<10){
				$day="0".intval($val);
			}
			if($this->finddow($Year."-".$Month."-".$day)==$strDay){
				$numDay=$val;
				break;
			}
		}
		return $numDay;
	}
	
	
	public function getDiffStatus($time_difference,$type,$tolerance_before,$tolerance_retard,$lack_minute,$tolerance_after){

		$message="";
		if($type=="E"){		
			//$tolerance_before=10; //Minutes before
			//$tolerance_retard=1; //Minutes after check time
			//$lack_minute=20; //Minute after check time to lose class
			if(substr_count($time_difference,"-")>0){ //Check is  negative
				/// Is Negative
				$temptime=str_replace("-","",$time_difference);
				$diff_check=explode(":",$temptime);
				
				if(intval($diff_check[0])>0){ /// Analazing hours
					//Falta
					$message="Falta";
				}else{
					if(intval($diff_check[1])>$tolerance_before){ /// Analazing minutes
						///Falta
						$message="Falta";
					}else{
						if(intval($diff_check[1])<$tolerance_before){
							/// Checada a tiempo
							$message="Normal";								
						}else{
							if(intval($diff_check[2])>0){ /// Analazing minutes
								///Falta
								$message="Falta";
							}else{
								/// Checada a tiempo
								$message="Normal";
							}
						}
					}
				}
			}else{
				$diff_check=explode(":",$time_difference);
				/// Real difference
				if(intval($diff_check[0])>0){ /// Analazing hours
					//Falta
					$message="Falta";
				}else{
					if(intval($diff_check[1])>=$tolerance_retard){ /// Analazing minutes
						//Check if it's a half hour
						if(intval($diff_check[1])>=$lack_minute){
							//Falta
							$message="Falta";
						}else{
							$message="Retardo";
						}
					}else{
						$message="Normal";
					}
				}
			}
		}else{
			//$tolerance_after=20; //Minutes after check time
			if(substr_count($time_difference,"-")>0){ //Check is  negative
				$message="Checada antes de tiempo";
			}else{
				$diff_check=explode(":",$time_difference);
				/// Real difference
				if(intval($diff_check[0])>0){ /// Analazing hours
					//Falta
					$message="Checada faltante";
				}else{
					if(intval($diff_check[1])>=$tolerance_after){ /// Analazing minutes
						$message="Checada faltante";
					}else{
						//Checada a tiempo
						$message="Normal";
					}
				}
			}		
		}
		return $message;
	}
	
	
	
	
	
}
?>