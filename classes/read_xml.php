<?
class xmlreader1 extends xmlreader{
    var $_data; 
    var $_white; 
    var $_xml_url; 
    function xmlreader1 ($xml_url="") 
    { 
      $this->_white = 1; 
      if (trim($xml_url) != "") $this->set_xml_url ($xml_url); 
    } 
    function set_xml_url ($url) 
    { 
      $this->_xml_url = $url; 
    } 
    function read () 
    { 
      if (!$this->_xml_url) $this->error ("XML File is not assigned."); 
      $fp = fopen ($this->_xml_url, "r"); 
      while (!feof ($fp)) $this->_data .= fgets($fp, 4096); 
      fclose ($fp); 
      $this->_data = trim ($this->_data); 
    } 
    function parse () 
    { 
      $this->read(); 
      if (trim ($this->_data) == "") $this->error ("Data not ready."); 
      $vals = $index = $array = array(); 
      $parser = xml_parser_create(); 
      xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
      xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, $this->_white); 
      xml_parse_into_struct($parser, $this->_data, $vals, $index); 
      xml_parser_free($parser); 
      $i = 0; 
      $tagname = $vals[$i]['tag']; 
      if ( isset ($vals[$i]['attributes'] ) ) 
      { 
        $array[$tagname]['@'] = $vals[$i]['attributes']; 
      } 
      else 
      { 
        $array[$tagname]['@'] = array(); 
      } 
      $array[$tagname]["#"] = $this->xml_depth($vals, $i); 
      return $array; 
    } 
    function xml_depth($vals, &$i) { 
      $children = array(); 
      if ( isset($vals[$i]['value']) ) 
      { 
        array_push($children, $vals[$i]['value']); 
      } 
      while (++$i < count($vals)) { 
        switch ($vals[$i]['type']) { 
          case 'open': 
            if ( isset ( $vals[$i]['tag'] ) ) 
            { 
              $tagname = $vals[$i]['tag']; 
            } 
            else 
            { 
              $tagname = ''; 
            } 
            if ( isset ( $children[$tagname] ) ) 
            { 
              $size = sizeof($children[$tagname]); 
            } 
            else 
            { 
              $size = 0; 
            } 
            if ( isset ( $vals[$i]['attributes'] ) ) 
            { 
              $children[$tagname][$size]['@'] = $vals[$i]["attributes"]; 
            } 
            $children[$tagname][$size]['#'] = $this->xml_depth($vals, $i); 
          break; 
          case 'cdata': 
            array_push($children, $vals[$i]['value']); 
          break; 
          case 'complete': 
            $tagname = $vals[$i]['tag']; 
            if( isset ($children[$tagname]) ) 
            { 
              $size = sizeof($children[$tagname]); 
            } 
            else 
            { 
              $size = 0; 
            } 
            if( isset ( $vals[$i]['value'] ) ) 
            { 
              $children[$tagname][$size]["#"] = $vals[$i]['value']; 
            } 
            else 
            { 
              $children[$tagname][$size]["#"] = ''; 
            } 
            if ( isset ($vals[$i]['attributes']) ) 
            { 
              $children[$tagname][$size]['@'] = $vals[$i]['attributes']; 
            }             
          break; 
          case 'close': 
            return $children; 
          break; 
        } 
      } 
      return $children; 
    } 
    function traverse_xmlize($array, $arrName = "array", $level = 0) { 
      foreach($array as $key=>$val) 
      { 
        if ( is_array($val) ) 
        { 
          traverse_xmlize($val, $arrName . "[" . $key . "]", $level + 1); 
        } 
        else 
        { 
          $GLOBALS['traverse_array'][] = '$' . $arrName . '[' . $key . '] = "' . $val . "\"\n"; 
        } 
      } 
      return 1; 
    } 
    function error ($str) 
    { 
      print get_class ($this)." ".$this->version()." => $str"; 
      exit(); 
    } 
    function version () 
    { 
      return "1.0"; 
	}
}; 
?>