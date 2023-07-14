<?
//////////////////////USAGE/////////////////////////
//// array("2009/06/05","2009/06/18"); 			////
//// makeCal("2009-06",$ARRAY);					////
////////////////////////////////////////////////////

function finddow($fvalue){
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
			$dow="Miercoles";
		break;
		case "Thu":
			$dow="Jueves";
		break;
		case "Fri":
			$dow="Viernes";
		break;
		case "Sat":
			$dow="Sabado";	
		break;
	}
	return $dow;	
}
function getMonth($param){
	switch($param){
		case 1:
			$theMonth="Enero";
		break;
		case 2:
			$theMonth="Febrero";
		break;
		case 3:
			$theMonth="Marzo";
		break;
		case 4:
			$theMonth="Abril";
		break;
		case 5:
			$theMonth="Mayo";
		break;
		case 6:
			$theMonth="Junio";
		break;
		case 7:
			$theMonth="Julio";
		break;
		case 8:
			$theMonth="Agosto";
		break;
		case 9:
			$theMonth="Septiembre";
		break;
		case 10:
			$theMonth="Octubre";
		break;
		case 11:
			$theMonth="Noviembre";
		break;
		case 12:
			$theMonth="Diciembre";
		break;
		
	}
	return $theMonth;	
}
function findposcal($dvalue){
	switch($dvalue){
	case "Domingo":
	$pos=1;
	break;
	case "Lunes":
	$pos=2;	
	break;
	case "Martes":
	$pos=3;	
	break;
	case "Miercoles":
	$pos=4;	
	break;
	case "Jueves":
	$pos=5;	
	break;
	case "Viernes":
	$pos=6;	
	break;
	case "Sabado":
	$pos=7;	
	break;
	}
	return $pos;	
}
function makeCal($year,$month,$events){
	////////////////////////////////VARIABLES////////////////////////////////////	
	$tableWidth="160";														/////
	$todayColor="#E3EAF2";													/////
	$eventColor="#990000";													/////
	/////////////////////////////////////////////////////////////////////////////
	if(!$year){
		$year=date("Y");
		$month=date("m");
	}
	if($year==date("Y") && $month==date("m")){
		$today=date("Y/m/d");
	}
	$currentDate=$year."/".$month."/";
	$DAYS_MONTH=cal_days_in_month(CAL_GREGORIAN,$month,$year);
	?>
    <style>
		.Events{color:#CC0000; font-weight:bold;}
	.style1 {font-size: x-small}
    .style2 {
	color: #999999;
	font-weight: bold;
}
    </style>
	<table width="<?=$tableWidth?>" border="1" align="center" cellpadding="3" cellspacing="0" bordercolor="#F4F4F4" bgcolor="#FFFFFF" >
		<tr >
		  <td colspan="7" bgcolor="#EFEFEF"><div align="center" style="color:#000000; font-weight:bold">
	      <?=getMonth(date("n"))?></div></td>
	  </tr>
		<tr >
		  <td bgcolor="#FFF8F0"><div align="center" class="style1">D</div></td>
		  <td bgcolor="#EAEAFF"><div align="center" class="style1">L</div></td>
		  <td bgcolor="#F2FFFF"><div align="center" class="style1">M</div></td>
		  <td bgcolor="#DFFFF4"><div align="center" class="style1">M</div></td>
		  <td bgcolor="#F1F1EB"><div align="center" class="style1">J</div></td>
		  <td bgcolor="#FFF2F2"><div align="center" class="style1">V</div></td>
		  <td bgcolor="#EDE4D6"><div align="center" class="style1">S</div></td>
		</tr>
		<? $pos=findposcal(finddow($year."-".$month."-01"));
			for($i=1; $i<=$pos-1; $i++){
				$content[$i]="&nbsp;";
			}
			$cells=($pos-1)+$DAYS_MONTH;
			for($i=$pos; $i<=$cells; $i++){
				$content[$i]=$i-($pos-1);
			}
			$rows=round($cells/7)+1;
			$currentCell=1;
			for($i=1; $i<=$rows; $i++){ ?>
				<tr>
				<? for($j=1; $j<=7; $j++){ ?>
					<? if($currentCell<=$cells){ ?>
                    	<? $currentDay=$content[$currentCell]; ?>
                    	<? if($content[$currentCell]<10){ $currentDay="0".intval($currentDay); } ?>
                    	<? if($today==$currentDate.$content[$currentCell]){ ?>
                        	<? $style="bgcolor='".$todayColor."'";?>
                        <? } ?>
						<? if(in_array($currentDate.$currentDay,$events)){ ?>
                           	<? $action="onclick=\"javascript:window.open('cal_detail.php?date=".$currentDate.$content[$currentCell]."','_self')\" onmouseover=\"style.cursor='hand'\""; $class="class=\"Events\""; ?>
						<? } ?>
						<td align="center" <? if(isset($style)){ ?><?=$style?><? } ?>>
							<div id="layer1" <? if(isset($class)){ ?><?=$class?><? } ?> <? if(isset($action)){ ?><?=$action?><? } ?> style="font-size:x-small"><?=$content[$currentCell]?></div>                        </td>
						<? $currentCell++; unset($style,$action,$class,$currentDay); ?>                        
					<? } ?>                            
				<? } ?>
				</tr>
			<? } ?>
</table>
<? } ?>
<? makeCal(date("Y"),date("m"),array()); ?>
