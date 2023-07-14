<?
function write_titles($TITLES,$max_length,$pdf,$start_x,$start_y){
	$or_x=$start_x;
	$or_y=$start_y;
	$max_rows=0;
	foreach($TITLES as $index=>$value){
		$toEval=$index;
		############################################################
		$initial_font_size=5;
		$pdf->SetFont('Arial','b',$initial_font_size);		
		$pieces=explode(" ",$toEval);
		$ROWS=array();
		array_push($ROWS," ");
		$processed=array();
		foreach($pieces as $key=>$val){
			if(!in_array($key,$processed)){
				$towrite="";
				$length=$pdf->GetStringWidth($val);
				$length+=1;
				if($length<=$max_length){
					$towrite.=$val;
					$chars_left=$max_length-$length;
					for($i=$key+1; $i<count($pieces); $i++){
						if(isset($pieces[$i])){
							if(($pdf->GetStringWidth($pieces[$i])+1)<=$chars_left){
								$towrite.=" ".$pieces[$i];
								$chars_left-=$pdf->GetStringWidth(" ".$pieces[$i]);
								array_push($processed,$i);
							}else{
								$i=count($pieces);
							}
						}
					}
				}else{
					for($j=$initial_font_size; $j>1; $j--){
						$pdf->SetFont('Arial','I',$j);
						if($pdf->GetStringWidth($val)<=$max_length){
							$final_size=$j;
							$towrite.=$val;
							$j=1;
						}
					}
				}
				array_push($ROWS,$towrite);
			}
				
		}
		array_push($ROWS," ");		
		$ROWS_ACS=array_reverse($ROWS);
		foreach($ROWS_ACS as $k=>$v){
			$pdf->SetX($start_x);
			if($value=="auto"){			
				$pdf->Cell($max_length,$final_size-2,$v,0,0,"C",0);
			}else{
				$pdf->Cell($value,$final_size-2,$v,0,0,"C",0);			
			}
			$pdf->Ln();	
		}
		if(count($ROWS)>$max_rows){
			$max_rows=count($ROWS);
		}
		if($value=="auto"){
			$start_x+=$max_length;		
		}else{
			$start_x+=$value;				
		}
		$pdf->SetY($start_y);
		$pdf->SetX($start_x);		
		
	}
	$border_cell_height=(($max_rows)*($final_size-2));
	$pdf->SetX($or_x);
	$pdf->SetY($or_y);	
	foreach($TITLES as $i=>$c){
		if($c=="auto"){
			$pdf->Cell($max_length,$border_cell_height,"",1,0,"C",0);		
		}else{
			$pdf->Cell($c,$border_cell_height,"",1,0,"C",0);				
		}
	}
	$pdf->SetY($or_y);
	$pdf->Ln(-7);		
}
?>