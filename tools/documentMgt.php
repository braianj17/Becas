<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<script>
function loadXMLDoc(XMLname){
	var xmlDoc;
	if(window.XMLHttpRequest){
		xmlDoc=new window.XMLHttpRequest();
		xmlDoc.open("GET",XMLname,false);
		xmlDoc.send("");
		return xmlDoc.responseXML;
	}
	// IE 5 and IE 6
	else if (ActiveXObject("Microsoft.XMLDOM")){
		xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
		xmlDoc.async=false;
		xmlDoc.load(XMLname);
		return xmlDoc;
	}
	alert("Error loading document!");
	return null;
}
</script>


<script>
//This var controlls id the whole tree is open or collapsed
var collapsed=false;
//Data array
var data_structure={
	xml_content:{
		xmlRows:[]
	}
}

var num_cat=0;
xmlDoc=loadXMLDoc("../tools/documents_xml.php") // Path to the XML file;
for(obj in data_structure){
	var rootNode=xmlDoc.childNodes;
	for(j=0; j<rootNode.length; j++){
		var node=rootNode.item(j);
		if(node.tagName){
			for(k=0; k<node.childNodes.length; k++){
				var subnode=node.childNodes.item(k);
				if(subnode.tagName){
					<!--WRITING CATEGORY NAME -->
					data_structure[obj].xmlRows[num_cat]={DisplayState:false,category: subnode.getAttribute("id") ,urls:[]};
					num_items=0;
					var nodeElements=subnode.childNodes;
					for(l=0; l<nodeElements.length; l++){
						var nodeItem=nodeElements.item(l);
						if(nodeItem.tagName){
							var nodeFields=nodeItem.childNodes;							
							for(m=0; m<nodeFields.length; m++){
								var nodeField=nodeFields.item(m);
								if(nodeField.tagName){
									if( nodeField.tagName=="title"){
										var documentName=nodeField.childNodes[0].nodeValue;
									}
									if( nodeField.tagName=="url"){
										var documentUrl=nodeField.childNodes[0].nodeValue;
									}
									if( nodeField.tagName=="urltext"){
										var documentUrltext=nodeField.childNodes[0].nodeValue;
									}
									
								}
							}
							<!--WRITING CATEGORY ITEMS-->
							data_structure[obj].xmlRows[num_cat].urls[num_items]={Label:documentUrl,Link:documentUrltext};									
							num_items++;
						}
					}
					num_cat++;
				}
			}
		}
	}
}

<!--BUILING THE TREE-->
function build_tree(){
	var textoArbol = "";
	textoArbol += "<a href='javascript:collapse_tree()'><img src='../img/uncollapse.jpg' border='0'>Mostrar/Colapsar</a><br/>"; 	
	for(obj in data_structure){
		for(j=0; j<data_structure[obj].xmlRows.length; j++){ 
			if(data_structure[obj].xmlRows[j].DisplayState == false){
				textoArbol+="<p><a href='javascript:collapse_node(\"" + j + "\")'><img src='../img/folder_close.jpg' border='0'>";
				textoArbol+="&nbsp;<b>" + data_structure[obj].xmlRows[j].category  +"</b></a></p>"; 
			}else{
				textoArbol+="<p><a href='javascript:collapse_node(\"" + j + "\")'><img src='../img/folder_open.jpg' border='0'></a>";
				textoArbol+="&nbsp;<b>" + data_structure[obj].xmlRows[j].category + "</b></p>"; 
				textoArbol+="<blockquote>";
				for(i=0; i<data_structure[obj].xmlRows[j].urls.length; i++){ 
					textoArbol+="<a href='../documentos_institucionales/" + data_structure[obj].xmlRows[j].urls[i].Link + "' target='_blank'><img src='../img/document.jpg' border='0'>&nbsp;" + data_structure[obj].xmlRows[j].urls[i].Label + "</a><br>"; 			
				}
				textoArbol+="</blockquote>";				
			}
		}
	} 
	document.getElementById("tree_obj").innerHTML = textoArbol; 
}

function collapse_node(node){
	(data_structure[obj].xmlRows[node].DisplayState==true)? data_structure[obj].xmlRows[node].DisplayState=false:data_structure[obj].xmlRows[node].DisplayState=true;
	build_tree();
} 

function collapse_tree(){

	if(collapsed==false)
		collapsed=true;
	else
		collapsed=false;
	if(collapsed==true){
		for(obj in data_structure){
			for(j=0; j<data_structure[obj].xmlRows.length; j++){ 
				data_structure[obj].xmlRows[j].DisplayState=true;
			}
		}
	}else{
		for(obj in data_structure){
			for(j=0; j<data_structure[obj].xmlRows.length; j++){ 
				data_structure[obj].xmlRows[j].DisplayState=false;
			}
		}
	}
	build_tree(); 
} 

</script> 
</head>

<body onLoad="build_tree()">
<div id="tree_obj"></div> 
</body>
</html>
