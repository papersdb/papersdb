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

// format text into multiple lines not exceeding 80 characters
function format80($text) {
    $lines = explode("\n", $text);
    foreach($lines as $line) {
        preg_match("/^(\s+)/", $line, $m);
        if (strlen($line) > 80) {
            while (strlen($line) > 80) {
                $splt = strrpos(substr($line, 0, 80), ' ');
                $new_lines[] = substr($line, 0, $splt);
                $line = $m[1] . $m[1] . substr($line, $splt+1);
            }
            $new_lines[] = $line;
        }
        else
            $new_lines[] = $line;
    }

    return implode("\n", $new_lines);
}

function pubSessionInit() {
    $_SESSION['state'] = null;
    $_SESSION['pub'] = null;
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
    if (PHP_VERSION >= 5)
        if ($errno >= E_STRICT) return;

    if ($errno == E_NOTICE) return;

    // timestamp for the error entry
    $dt = date("Y-m-d H:i:s (T)");

    // define an assoc array of error string
    // in reality the only entries we should
    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    // E_USER_WARNING and E_USER_NOTICE
    $errortype = array (
        E_ERROR           => "Error",
        E_WARNING         => "Warning",
        E_PARSE           => "Parsing Error",
        E_NOTICE          => "Notice",
        E_CORE_ERROR      => "Core Error",
        E_CORE_WARNING    => "Core Warning",
        E_COMPILE_ERROR   => "Compile Error",
        E_COMPILE_WARNING => "Compile Warning",
        E_USER_ERROR      => "User Error",
        E_USER_WARNING    => "User Warning",
        E_USER_NOTICE     => "User Notice"
        //E_STRICT          => "Runtime Notice"
        );
    // set of errors for which a var trace will be saved
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

    $err = "<ul>\n";
    $err .= "\t<li>" . $dt . "</li>\n";
    $err .= "\t<li>Errno: " . $errno . ', ' . $errortype[$errno] . "</li>\n";
    $err .= "\t<li>" . $errmsg . "</li>\n";
    $err .= "\t<li>" . $filename . ":" . $linenum . "</li>\n";

    if (in_array($errno, $user_errors)) {
        $err .= "\t<li>" . wddx_serialize_value($vars, "Variables") . "</li>\n";
    }
    $err .= "</ul>\n\n";

    // for testing
    echo $err;
    backtrace();
}

$old_error_handler = set_error_handler("userErrorHandler");

?>
