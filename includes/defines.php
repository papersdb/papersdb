<?php ;

// $Id: defines.php,v 1.21 2006/08/09 22:57:23 aicmltec Exp $

/**
 * \file
 *
 * \brief Project Constants
 *
 * Defines contants to connect to the PapersDB database.
 */
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

ini_set("include_path", ini_get("include_path") . ":/usr/share/pear");

/** The server hosting the database. */
if ($_ENV['HOSTNAME'] == 'levante')
    define('DB_SERVER', 'levante:3306');
else
    define('DB_SERVER', 'kingman.cs.ualberta.ca:3306');

/** The user id accessing the database. */
define('DB_USER', 'papersdb');

/** The user id accessing the database. */
define('DB_PASSWD', '');

define('DB_ADMIN', 'papersdb@cs.ualberta.ca');

/**
 * The name of the database.
 */
if (strpos($_SERVER['PHP_SELF'], '~papersdb'))
    define('DB_NAME',   'pubDB');
else
    define('DB_NAME',   'pubDBdev');

/** The path on the fileserver where documents are stored. */
if (strpos($_SERVER['PHP_SELF'], '~papersdb'))
    define('FS_PATH', '/usr/abee/cshome/papersdb/web_docs');
else if ($_ENV['HOSTNAME'] == 'levante')
    define('FS_PATH', '/home/nelson/public_html/papersdb');
else
    define('FS_PATH', '/usr/abee4/cshome/loyola/web_docs/papersdb');

define('MAINTENANCE', 0);

// required for Database.php
define( 'DBO_DEBUG', 1 );
define( 'DBO_NOBUFFER', 2 );
define( 'DBO_IGNORE', 4 );
define( 'DBO_TRX', 8 );
define( 'DBO_DEFAULT', 16 );
define( 'DBO_PERSISTENT', 32 );

?>
