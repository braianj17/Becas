<?php
session_start();
if ($_SESSION['acceso']=='no'){
    header('Location: ../index.php');
    exit;
}



if ($_SESSION['i']==-1){

$cadena="tablatec";

}

else
{

  $e0=$_SESSION['e0'];
   $e1=$_SESSION['e1'];
   $e2=$_SESSION['e2'];
   $e3=$_SESSION['e3'];
$e4=$_SESSION['e4'];

}


?>

<html>
<head>
   <link rel='stylesheet' href='../css/inicial.css' type='text/css' media='all'>


<style type="text/css">
<!--
.Estilo1 {
        font-size: 26px;
        font-family: Arial, Helvetica, sans-serif;
        color: #0066FF;
}

.Estilo2 {
        font-size: 18px;
        font-family: Arial, Helvetica, sans-serif;
        color: #0066FF;
}


-->
</style>
<script>
function ocultar(idFila,form1){


var oFila = document.getElementById(idFila);

if (oFila){
oFila.style.display = (oFila.style.display != 'none') ? 'none' : 'block';
}//if


cambiar_m(form1);

}//funcion


function ocultar1(idFila,form1){


var oFila = document.getElementById(idFila);

if (oFila){
oFila.style.display = (oFila.style.display != 'none') ? 'none' : 'block';
}//if




}//funcion



    function cambiar_m(form){

   var estudios=form["estudios[]"];
   var estudios0=estudios[0].checked;
   var estudios1=estudios[1].checked;
   var estudios2=estudios[2].checked;
   var estudios3=estudios[3].checked;
   var estudios4=estudios[4].checked;





   var pagina="var_p.php?estudios0="+estudios0+"&estudios1="+estudios1+"&estudios2="+estudios2+"&estudios3="+estudios3+"&estudios4="+estudios4;
window.location = pagina;
}



</script>


</head>





<form name="form">
<?
echo "<center>";
echo "<table>";
echo "<tr>";
echo "<td><h1 align='center' class='Estilo1'>DOCUMENTOS ACADÉMICOS</h1></td></tr></table>
</center>";



echo "<center>";
?>



<td><table border="0">


<tr>
<td ><font  size='3'>1.   <a href='reglamentospdf/BME1.pdf'> Boletín Modelo Educativo I</a> </td>
</tr>
<tr>
<td ><font  size='3'>2.   <a href='reglamentospdf/BME2.pdf'> Boletín Modelo Educativo II</a> </td>
</tr>
<tr>
<td ><font  size='3'>3.   <a href='reglamentospdf/BME3.pdf'>  Boletín Modelo Educativo III </a> </td>
</tr>



</table>














</form>
</body>
</html>
