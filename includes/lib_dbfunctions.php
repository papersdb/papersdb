<?php ;



/**
 * DB connection is only hard coded here and in lib_functions.
 *
 * \note IMPORTANT
 *
 * At present, the files are stored at:
 *	$FS_PATH/uploaded_files/{pub_id}
 *
 * The naming scheme is:
 *	{paper/additional}_{name_of_file}.file_extension
 *
 * @package PapersDB
 */

/** Requires the global defines and the class that accesses the databse. */
require_once "defines.php";

/**
 * \todo this function should not be used anymore.
 *
 * \see pdDB.php
 */
function connect_db() {

	$link = mysql_connect(DB_SERVER, DB_USER, DB_PASSWD)
		or die("Could not connect : " . mysql_error());
	mysql_select_db(DB_NAME) or die("Could not select database");

	return $link;
}

/**
 * \todo this function should not be used anymore.
 *
 * \see pdDB.php
 */
function disconnect_db($link) {
	mysql_close($link);
}

/**
 * \todo this function should not be used anymore.
 *
 * \see pdDB.php
 */
function query_db($query) {
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	return $result;
}

?>
