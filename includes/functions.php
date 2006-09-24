<?php ;

/**
 * Common functions used by all pages.
 *
 * These functions are used throughout the pages and are here to save on time
 * and complexity.
 *
 * @package PapersDB
 */

/** Requires DB functions and Table classes. */
require_once 'lib_dbfunctions.php';
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

/**
 * Strips slashes and adds quotes if the value passed in is numeric or null.
 */
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
 * Converts an array into an object.
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

/**
 * Highlights each row of a table using the 'even' and 'odd' CSS classes.
 * The table passed in must be a Pear HTML_Table.
 */
function tableHighlightRows(&$table) {
    assert('is_object($table)');

    for ($i = 0; $i < $table->getRowCount(); $i++) {
        if ($i & 1)
            $table->updateRowAttributes($i, array('class' => 'even'), true);
        else
            $table->updateRowAttributes($i, array('class' => 'odd'), true);
    }
}

/**
 * format text into multiple lines not exceeding 80 characters
 */
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

/**
 * Initializes a publication add / edit session.
 */
function pubSessionInit() {
    unset($_SESSION['state']);
    unset($_SESSION['pub']);
    unset($_SESSION['paper']);
    unset($_SESSION['attachments']);
    unset($_SESSION['att_types']);
    unset($_SESSION['removed_atts']);
}

/**
 * Initializes a search session.
 */
function searchSessionInit() {
    unset($_SESSION['search_results']);
    unset($_SESSION['search_url']);
    unset($_SESSION['search_params']);
}

/**
 * Use our own error handling function.
 */
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
