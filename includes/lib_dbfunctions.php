<?php ;

// $Id: lib_dbfunctions.php,v 1.12 2007/03/12 05:25:45 loyola Exp $

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
require_once 'Database.php';

/**
 * Creates a database object to operate on the database.
 */
function dbCreate() {
    return Database::newFromParams(DB_SERVER, DB_USER, DB_PASSWD, DB_NAME);
}

/**
 * \todo this function should not be used anymore.
 *
 * \see dbCreate
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
 * \see dbCreate
 */
function disconnect_db($link) {
	mysql_close($link);
}

/**
 * \todo this function should not be used anymore.
 *
 * \see dbCreate
 */
function query_db($query) {
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	return $result;
}

function wfDebug( $text, $logonly = false ) {
    echo $text;
}

$wgProfiling = 0;

function wfProfileIn($str) {}
function wfProfileOut($str) { echo $str . "<br/>\n"; }
function wfLogDBError( $text ) { echo $text . "<br/>\n"; }
function wfGetSiteNotice() {}
function wfErrorExit() {
    //echo papersdb_backtrace();
    die();
}
function wfSetBit( &$dest, $bit, $state = true ) {}
function wfSuppressWarnings( $end = false ) {}
function wfRestoreWarnings() {}
function wfDebugDieBacktrace( $msg = '' ) {
    echo papersdb_backtrace();
    die($msg);
}
function wfSetVar( &$dest, $source ) {}

?>
