<?php ;

// $Id: defines.php,v 1.3 2006/05/18 15:06:52 aicmltec Exp $

/**
 * \file
 *
 * \brief Project Constants
 *
 * Defines contants to connect to the PapersDB database.
 */

/** The server hosting the database. */
//define("DB_SERVER", "abee.cs.ualberta.ca:3306");
define("DB_SERVER", "levante:3306");

/** The user id accessing the database. */
define("DB_USER", "papersdb");

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
