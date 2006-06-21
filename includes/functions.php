<?php ;

/**
 * \file
 *
 * \brief Common functions used by all pages.
 *
 * These functions are used throughout the pages and are here to save on time
 * and complexity. Each function is pretty straight forward.
 */

require_once 'lib_dbfunctions.php';
require_once 'HTML/QuickForm.php';
require_once "HTML/QuickForm/Renderer/QuickHtml.php";
require_once 'HTML/Table.php';

$relative_files_path = "uploaded_files/";
$absolute_files_path = FS_PATH . $relative_files_path;


/**
 *  Checks to see if the given string is nothing but letters or numbers and is
 *  shorter then a certain length.
 */
function isValid($string){
	for($a = 0; $a < strlen($string); $a++){
		$char = substr($string,$a,1);
		$isValid = false;
		// Numbers 0-9
		for($b = 48; $b <= 57; $b++)
			if($char == chr($b))
				$isValid = true;
		//Uppercase A to Z
		if(!$isValid)
			for($b = 65; $b <= 90; $b++)
				if($char == chr($b))
					$isValid = true;
		//Lowercase a to z
		if(!$isValid)
			for($b = 97; $b <= 122; $b++)
				if($char == chr($b))
					$isValid = true;
		if(!$isValid)
			return errorMessage();
	}
	return "";
}

function quote_smart($value) {
	// Stripslashes
	if (get_magic_quotes_gpc()) {
		$value = stripslashes($value);
	}
	// Quote if not a number or a numeric string
	if (!is_numeric($value) || $value[0] == '0') {
		$value = "'" . mysql_real_escape_string($value) . "'";
	}
	return $value;
}

// Handy back button usually used at the end of pages.
function back_button()
{
	echo "<form> \n";
	echo "<input type=\"button\" value=\"Back\" onclick=\"history.back()\"> \n";
	echo "</form> \n";
}

function quickForm($title, $style, $name, $value){
	$value = stripslashes($value);
	echo "<tr> \n";
	echo "<td width=\"25%\" class=\"".$style."\">".$title.": </td> \n";
	echo "<td width=\"75%\" class=\"".$style."\"> \n";
	echo "<input type=\"text\" name=\"" . $name
        . "\" size=\"60\" maxlength=\"250\" value=\"".$$value."\"> \n";
	echo "</td></tr> \n";
}

function generate_select_body ($start, $end, $compare) {
    for ($i = $start; $i <= $end; $i++) {
	echo "  <option value='$i' ";
	if ($compare == $i) echo "selected";
	echo "> $i </option> \n";
    }
}

function generate_select($name, $start, $end, $compare) {
    echo "<select name='$name'> \n";
    generate_select_body ($start, $end, $compare);
    echo "</select> \n";
}

function generate_select_date($name, $start, $end, $compare = NULL) {
    echo "<select name='$name'> \n";
	echo " <option value='--'";
	if($compare == NULL) echo "selected";
	echo "> -- </option>";
	if($name == "year"){
	for ($i = $end; $i >= $start; $i--) {
		if($compare == $i)
			echo "  <option value='$i' selected> $i </option> \n";
		else
			echo "  <option value='$i'> $i </option> \n";
		}
	}
	else {
	for ($i = $start; $i <= $end; $i++) {
		if($compare == $i)
			echo "  <option value='$i' selected> $i </option> \n";
		else
			echo "  <option value='$i'> $i </option> \n";
		}
	}
    echo "</select> \n";
}

function generate_select_month($name, $start, $end, $compare = NULL) {
    echo "<select name='$name'> \n";
	echo " <option value='--'";
	if($compare == NULL) echo "selected";
	echo "> -- </option>";
    for ($i = $start; $i <= $end; $i++) {
	echo "  <option value='$i' ";
	if ($compare == $i) echo "selected";
	echo "> " . date ("F", mktime (0,0,0,$i)) . " </option> \n";
    }
    echo "</select> \n";
}


function get_num_db_materials ($pubID) {
    global $db;

    if ($db == NULL) {
	return;
    }

    $rval = NULL;
    $count = 0;

	$sql = "select * from pub_add where pub_id = $pubID";
    $result = query_db($sql);
	while(mysql_fetch_array($result, MYSQL_ASSOC))
    	$count++;

    return $count;
}


function get_publication_info ($pubID) {
    global $db;


    $sql = "select * from publication where pub_id = $pubID";
    $result = query_db($sql);
	$line = mysql_fetch_array($result, MYSQL_ASSOC);

    // Make sure there's one and only one publication with this ID
    if ($line == NULL)
		echo "Error: There is no publication with this ID!";

	return $line;
}

function get_category ($pubID) {
    global $db;
    //if ($db == NULL) {
	//	return;
    //}

    $sql = "select B.category, B.cat_id from pub_cat A, category B
		WHERE pub_id = $pubID
		AND A.cat_id = B.cat_id";
    $result = query_db($sql);
	$line = mysql_fetch_array($result, MYSQL_ASSOC);

    if ($line == NULL)
		echo "Error: Couldn't locate category for paper.";

    return $line;
}


function get_authors ($pubID) {
    global $db;
    if ($db == NULL) {
	return;
    }
	$rval = NULL;
    $sql = "select B.name from pub_author A, author B
		WHERE pub_id = $pubID
		AND A.author_id = B.author_id";
    $result = query_db($sql);

    // Use a hash to return the existence of a author with a paper.
    // This makes checking in the form very easy.
    while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	$rval[$row['name']] = 1;
    }
    if ($rval == NULL)
		echo "Error: Couldn't locate authors for paper.";
    return $rval;
}


function get_info_field_value ($pubID, $catID, $infoID) {
	global $db;
    if ($db == NULL) {
	return;
    }

    $rval = NULL;

    $sql = "SELECT value FROM pub_cat_info
		WHERE pub_id = $pubID
		AND cat_id = $catID
		AND info_id = $infoID";
    $result = query_db($sql);
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	return $line['value'];
}


function get_info_id ($catID, $infoName) {
    global $db;

    if ($db == NULL) {
	return;
    }

    $rval = NULL;

    $info_id_get = "SELECT info_id FROM info WHERE name = \"$infoName\"";
    $info_id_result = query_db($info_id_get);
	$info_id_line = mysql_fetch_array($info_id_result, MYSQL_ASSOC);

	return $info_id_line['info_id'];
}

function removematerial ($pubID, $i) {
    global $db;

 	if ($db == NULL) {

	return "Did not delete succesfully, DB is NULL";
    }

    $rval = NULL;

    $sql = "select B.location, B.add_id from pub_add A, additional_info B
		WHERE A.pub_id = $pubID
		AND A.add_id = B.add_id
		ORDER BY B.add_id";
    $result = query_db($sql);
	$row = mysql_fetch_array($result, MYSQL_ASSOC);

    if ($row == NULL) {
	echo "Error: Couldn't locate additional material for pub $pubID and item number $i.";
    }

	$location = $row['location'];

	$sql = "SELECT * FROM additional_info WHERE location = \"$location\"";
    $result = query_db($sql);
	$value = mysql_fetch_array($result, MYSQL_ASSOC);

	$add_id = $value['add_id'];


	$pub_query = "SELECT * FROM publication WHERE pub_id=$pubID";
	$pub_result = query_db($pub_query);

	$query = "DELETE FROM additional_info WHERE add_id = $add_id";
	$result = query_db($query);

	$query = "DELETE FROM pub_add WHERE add_id = $add_id AND pub_id = $pubID";
	$result = query_db($query);

	system("rm -rf " . FS_PATH . $location);
	$location = split("/",$location);
	$name = $location[3];
	return "Deleted $name Succesfully";

}
function get_additional_material ($pubID, $i) {
    global $db;
    if ($db == NULL) {
	return "Error";
    }

    $rval = NULL;

    $sql = "SELECT B.location, B.add_id, B.type FROM pub_add A, additional_info B <br>
		WHERE A.pub_id = $pubID
		AND A.add_id = B.add_id
		ORDER BY B.add_id";
    $res = query_db($sql);

	$b = 0;
	while($row = mysql_fetch_array($res, MYSQL_ASSOC))
	{
		if($b == $i){
			$temp_string = $row['location'];
			$temp_string2 = split("/additional_",$temp_string);
			$temparray[0] = $temp_string2[1];
			$temparray[1] = $row['type'];
			}
	$b++;
	}
    return $temparray;
}

function get_venue_info($venue) {
 $output = "";
 $temp_array = split("venue_id:<", $venue);
	 if($temp_array[1] != ""){
		$temp_array = split(">", $temp_array[1]);
		$venue_id = $temp_array[0];
		$venue_query = "SELECT * FROM venue WHERE venue_id=$venue_id";
		$venue_result = query_db($venue_query);
		$venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC);
		$venue_name = $venue_line[name];
		$venue_url = $venue_line[url];
		$venue_type = $venue_line[type];
		$venue_data = $venue_line[data];
		$output .= "<b>".$venue_type.":&nbsp;</b>";
		if($venue_url != "")
			$output .= " <a href=\"".$venue_url."\" target=\"_blank\">";
		$output .= $venue_name;
		if($venue_url != "")
			$output .= "</a>";
		if($venue_data != ""){
			$output .= "</td></tr><tr><td>";
			if($venue_type == "Conference")
				$output .= "<b>Location:&nbsp;</b>";
			else if($venue_type == "Journal")
				$output .= "<b>Publisher:&nbsp;</b>";
			else if($venue_type == "Workshop")
				$output .= "<b>Associated Conference:&nbsp;</b>";
			$output .= $venue_data;
		}
	}
	else $output .= "<b>Publication Venue:</b>".$venue;
	return $output;
}

function quickSearchFormCreate() {
    $form = new HTML_QuickForm('quickPubForm', 'post',
                               'search_publication_db.php', '_self',
                               'multipart/form-data');

    $options = array('titlecheck', 'authorcheck', 'halfabstractcheck',
                     'datecheck');

    foreach ($options as $name) {
        $form->addElement('hidden', $name, 'yes');
    }
    $form->addElement('text', 'search', null,
                      array('size' => 12, 'maxlength' => 80));
    $form->addElement('submit', 'Quick', 'Search');

    return $form;
}

/**
 *
 */
function arr2obj($arg_array) {
    $tmp = new stdClass; // start off a new (empty) object
    foreach ($arg_array as $key => $value) {
        if (is_array($value)) { // if its multi-dimentional, keep going :)
            $tmp->$key = arr2obj($value);
        } else {
            if (is_numeric($key)) { // can't do it with numbers :(
                die("Cannot turn numeric arrays into objects!");
            }
            $tmp->$key = $value;
        }
    }
    return $tmp; // return the object!
}

function tableHighlightRows(&$table) {
    assert('is_object($table)');

    for ($i = 0; $i < $table->getRowCount(); $i++) {
        if ($i & 1) {
            $table->updateRowAttributes($i, array('class' => 'even'), true);
        }
        else {
            $table->updateRowAttributes($i, array('class' => 'odd'), true);
        }
    }
}

function backtrace() {
    $s = '';
    $MAXSTRLEN = 64;

    $s = '<pre align=left>';
    $traceArr = debug_backtrace();

    //print_r($traceArr);

    array_shift($traceArr);
    $tabs = sizeof($traceArr)-1;
    foreach($traceArr as $arr) {
        for ($i=0; $i < $tabs; $i++) $s .= ' &nbsp; ';
        $tabs -= 1;
        $s .= '<font face="Courier New,Courier">';
        if (isset($arr['class'])) $s .= $arr['class'].'.';
        $args = array();
        if(!empty($arr['args'])) foreach($arr['args'] as $v)
        {
            if (is_null($v)) $args[] = 'null';
            else if (is_array($v)) $args[] = 'Array['.sizeof($v).']';
            else if (is_object($v)) $args[] = 'Object:'.get_class($v);
            else if (is_bool($v)) $args[] = $v ? 'true' : 'false';
            else
            {
                $v = (string) @$v;
                $str = htmlspecialchars(substr($v,0,$MAXSTRLEN));
                if (strlen($v) > $MAXSTRLEN) $str .= '...';
                $args[] = "\"".$str."\"";
            }
        }
        $s .= $arr['function'].'('.implode(', ',$args).')</font>';
        $Line = (isset($arr['line'])? $arr['line'] : "unknown");
        $File = (isset($arr['file'])? $arr['file'] : "unknown");
        $s .= sprintf("<font color=#808080 size=-1> # line %4d, file: <a href=\"file:/%s\">%s</a></font>",
                      $Line, $File, $File);
        $s .= "\n";
    }
    $s .= '</pre>';
    return $s;
}

// user defined error handling function
function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
    if ($errno >= E_STRICT) return;

    // timestamp for the error entry
    $dt = date("Y-m-d H:i:s (T)");

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    // E_USER_WARNING and E_USER_NOTICE
    $errortype = array (
        E_ERROR          => "Error",
        E_WARNING        => "Warning",
        E_PARSE          => "Parsing Error",
        E_NOTICE          => "Notice",
        E_CORE_ERROR      => "Core Error",
        E_CORE_WARNING    => "Core Warning",
        E_COMPILE_ERROR  => "Compile Error",
        E_COMPILE_WARNING => "Compile Warning",
        E_USER_ERROR      => "User Error",
        E_USER_WARNING    => "User Warning",
        E_USER_NOTICE    => "User Notice",
        E_STRICT          => "Runtime Notice"
        );
    // set of errors for which a var trace will be saved
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

    $err = "<errorentry>\n";
    $err .= "\t<datetime>" . $dt . "</datetime>\n";
    $err .= "\t<errornum>" . $errno . "</errornum>\n";
    $err .= "\t<errortype>" . $errortype[$errno] . "</errortype>\n";
    $err .= "\t<errormsg>" . $errmsg . "</errormsg>\n";
    $err .= "\t<scriptname>" . $filename . "</scriptname>\n";
    $err .= "\t<scriptlinenum>" . $linenum . "</scriptlinenum>\n";

    if (in_array($errno, $user_errors)) {
        $err .= "\t<vartrace>" . wddx_serialize_value($vars, "Variables") . "</vartrace>\n";
    }
    $err .= "</errorentry>\n\n";

    // for testing
    echo $err . '<br/>';
    backtrace();
}

$old_error_handler = set_error_handler("userErrorHandler");

?>
