<?php ;

// $Id: defines.php,v 1.8 2006/06/11 20:42:27 aicmltec Exp $

/**
 * \file
 *
 * \brief Project Constants
 *
 * Defines contants to connect to the PapersDB database.
 */

/** The server hosting the database. */
if ($_ENV['HOSTNAME'] == 'levante')
    define('DB_SERVER', 'levante:3306');
else
    define('DB_SERVER', 'abee.cs.ualberta.ca:3306');

/** The user id accessing the database. */
define('DB_USER', 'papersdb');

/** The user id accessing the database. */
define('DB_PASSWD', '');

/**
 * The name of the database.
 *
 * @todo needs to be changed to real database when SW is released
 */
define('DB_NAME',   'pubDBdev');

/** The path on the fileserver where documents are stored. */
//define('FS_PATH', '/usr/abee/cshome/papersdb/web_docs/');
define('FS_PATH', '/usr/abee4/cshome/loyola');

// required for Database.php
define( 'DBO_DEBUG', 1 );
define( 'DBO_NOBUFFER', 2 );
define( 'DBO_IGNORE', 4 );
define( 'DBO_TRX', 8 );
define( 'DBO_DEFAULT', 16 );
define( 'DBO_PERSISTENT', 32 );


?>
