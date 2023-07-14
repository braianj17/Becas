<?php
function compararFechas($primera, $segunda)
{
  $valoresPrimera = explode ("/", $primera);   
  $valoresSegunda = explode ("/", $segunda); 
  $diaPrimera    = $valoresPrimera[0];  
  $mesPrimera  = $valoresPrimera[1];  
  $anyoPrimera   = $valoresPrimera[2]; 
  $diaSegunda   = $valoresSegunda[0];  
  $mesSegunda = $valoresSegunda[1];  
  $anyoSegunda  = $valoresSegunda[2];
  $diasPrimeraJuliano = gregoriantojd($mesPrimera, $diaPrimera, $anyoPrimera);  
  $diasSegundaJuliano = gregoriantojd($mesSegunda, $diaSegunda, $anyoSegunda);     
  if(!checkdate($mesPrimera, $diaPrimera, $anyoPrimera)){
    // "La fecha ".$primera." no es válida";
    return 0;
  }elseif(!checkdate($mesSegunda, $diaSegunda, $anyoSegunda)){
    // "La fecha ".$segunda." no es válida";
    return 0;
  }else{
    return  $diasPrimeraJuliano - $diasSegundaJuliano;
  } 
}
#####Fechas de inicio y fin de la solicitud de Becas#############
##$primera1 =  date("d/m/Y");##actual, fecha actual
$fecha_inicio =  '27/01/2018';##actual, fecha actual

##Reingreso
$fecha_cierre1 = "26/01/2018";##Fecha de cierre Alumnos Reinscritos
$diast = compararFechas ($fecha_inicio,$fecha_cierre1);

if($diast > 0){
    $cerrar1 = 'si'; ##Cerrar el Sistema para los alumnos inscritos
}else{
    $cerrar1 = 'no'; ##
}

##Nuevo ingreso
$fecha_cierre2 = '26/01/2018'; ##Fecha de cierre Alumnos Nuevo Ingreso
$diast2 = compararFechas ($fecha_inicio,$fecha_cierre2);
if($diast2 > 0){
    $cerrar2 = 'si'; ##Cerrar el Sistema para los alumnos de Nuevo Ingreso
}else{
    $cerrar2 = 'no'; ##
}

?>