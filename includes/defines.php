<?php ;

// $Id: defines.php,v 1.28 2008/02/20 21:10:27 loyola Exp $

/**
 * Project Constants
 *
 * Defines contants to connect to the PapersDB database.
 *
 * @package PapersDB
 */
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

ini_set("include_path", ini_get("include_path") . ":./pear:../pear");

$wgSitename = "PapersDB";
$wgServer = "www.cs.ualberta.ca";

define('SITE_NAME', 'papersdb');

/** The server hosting the database. */
if (isset($_ENV['HOSTNAME']) && ($_ENV['HOSTNAME'] == 'levante'))
    define('DB_SERVER', 'levante:3306');
else
    define('DB_SERVER', 'kingman.cs.ualberta.ca:3306');

/** The user id accessing the database. */
define('DB_USER', 'papersdb');

/** The user id accessing the database. */
define('DB_PASSWD', '');

define('DB_ADMIN', 'papersdb@cs.ualberta.ca');

/**
 * The name of the database and the path on the fileserver where documents are 
 * stored.
 */
if (strpos($_SERVER['PHP_SELF'], '~papersdb')) {
    define('DB_NAME',   'pubDB');
    define('FS_PATH', '/usr/abee/cshome/papersdb/web_docs');
}
else {
    define('DB_NAME',   'pubDBdev');
    if (isset($_ENV['HOSTNAME']) && ($_ENV['HOSTNAME'] == 'levante'))
       define('FS_PATH', '/home/nelson/public_html/papersdb');
    else
        define('FS_PATH', '/usr/abee4/cshome/loyola/web_docs/papersdb');
}

define('FS_PATH_UPLOAD', FS_PATH . '/uploaded_files/');

$relative_files_path = "uploaded_files/";
$absolute_files_path = FS_PATH . $relative_files_path;

define('MAINTENANCE', 0);

// required for Database.php
define( 'DBO_DEBUG', 1 );
define( 'DBO_NOBUFFER', 2 );
define( 'DBO_IGNORE', 4 );
define( 'DBO_TRX', 8 );
define( 'DBO_DEFAULT', 16 );
define( 'DBO_PERSISTENT', 32 );

?>
