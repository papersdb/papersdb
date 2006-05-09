<?php

/* DB connection is only hard coded here and in lib_functions. */

/* ***IMPORTANT***
	At present, the files are stored at:
		$FS_PATH/uploaded_files/{pub_id}
	The naming scheme is:
		{paper/additional}_{name_of_file}.file_extension
*/

require "constants.php";

$relative_files_path = "uploaded_files/";
$absolute_files_path = FS_PATH . $relative_files_path;


/* Useful functions dealing with the database */
function connect_db() {

	$link = mysql_connect(DB_SERVER, DB_USER, DB_PASSWD)
		or die("Could not connect : " . mysql_error());
	mysql_select_db(DB_NAME) or die("Could not select database");

	return $link;
}

function query_db($query) {
	//$result = mysql_query($query) or die(back_button());
	// Use this line for debugging
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	return $result;
}

function disconnect_db($link) {
	mysql_close($link);
}


?>
