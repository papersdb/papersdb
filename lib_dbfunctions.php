<?php

  // $Id: lib_dbfunctions.php,v 1.5 2006/05/11 22:32:31 aicmltec Exp $

  /**
   * \file
   *
   * \brief DB connection is only hard coded here and in lib_functions.
   *
   * \note IMPORTANT
   *
   * At present, the files are stored at:
   *	$FS_PATH/uploaded_files/{pub_id}
   *
   * The naming scheme is:
   *	{paper/additional}_{name_of_file}.file_extension
   */

include_once("defines.php");
require_once('includes/Database.php');

$relative_files_path = "uploaded_files/";
$absolute_files_path = FS_PATH . $relative_files_path;

$wgSiteName = "PapersDB";


/* Useful functions dealing with the database */
function &connect_db() {
    $db = Database::newFromParams(DB_SERVER, DB_USER, DB_PASSWD, DB_NAME);
    return $db;
}

function wfDebug( $text, $logonly = false ) {
    print $text;
}

function wfLogDBError( $text ) {}
function wfGetSiteNotice() {}
function wfErrorExit() {}
function wfSetBit( &$dest, $bit, $state = true ) {}
function wfSuppressWarnings( $end = false ) {}
function wfRestoreWarnings() {}
function wfDebugDieBacktrace( $msg = '' ) {}
function wfSetVar( &$dest, $source ) {}

?>
