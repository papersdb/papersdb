<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<?php

 /**
  *@file
  *
  * Project Constants
  *
  * Defines contants to connect to the PapersDB database.
  */

 // $Id: constants.php,v 1.2 2006/05/11 00:00:56 aicmltec Exp $

 /** The server hosting the database. */
define("DB_SERVER", "abee.cs.ualberta.ca:3306");

/** The user id accessing the database. */
define("DB_USER",   "papersdb");

/** The user id accessing the database. */
define("DB_PASSWD", "");

/**
 * The name of the database.
 *
 * @todo needs to be changed to real database when SW is released
 */
define("DB_NAME",   "pubDBdev");

/** The path on the fileserver where documents are stored. */
//define("FS_PATH", "/usr/abee/cshome/papersdb/web_docs/");
define("FS_PATH", "/usr/abee4/cshome/loyola");

?>

