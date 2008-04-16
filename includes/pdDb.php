<?php

/**
 * Singleton wrapper class for database access.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/Database.php';

class pdDb {
    private static $_db = null;
    private static $_db_name;
    private static $_debug = false;

    private static $_db_tables = array(
        'additional_info',
    	'aicml_positions',
    	'aicml_staff',
        'attachment_types',
        'author',
        'author_interest',
        'category',
    	'cat_info',
	    'cat_vopts',
        'collaboration',
        'extra_info',
        'help_fields',
        'info',
        'interest',
        'pointer',
        'pub_add',
        'pub_author',
        'pub_cat',
        'pub_cat_info',
        'pub_col',
        'publication',
        'pub_pending',
        'pub_rankings',
        'pub_valid',
    	'tag_ml_history',
    	'user',
        'user_author',
        'venue',
        'venue_occur',
        'venue_rankings',
        'venue_vopts',
        'vopts'
        );

    public static function newFromParams($server = DB_SERVER, $user = DB_USER,
                                         $passwd = DB_PASSWD, $name = DB_NAME) {
        self::$_db = Database::newFromParams($server, $user, $passwd, $name);
        self::$_db_name = $name;

        if (!self::$_db->isOpen()) {
            switch (mysql_errno()) {
                case 1045:
                case 2000:
                    echo 'failed due to authentication errors. ',
                    	'Check database username and password<br>/';
                    break;

                case 2002:
                case 2003:
                default:
                    // General connection problem
                    echo 'failed with error [', $errno, '] ',
                    	htmlspecialchars(mysql_error()), '.<br>';
                    break;
            }
            die();
        }

        self::dbIntegrityCheck();
        return self::$_db;
    }

    private static function dbIntegrityCheck() {
        if (isset($_SESSION['dbcheck'])) return;
        
        assert('is_object(self::$_db)');
        
        foreach (self::$_db_tables as $table) {
        	if (! self::$_db->tableExists($table)) {
	            echo "Database error encountered: table", $table, 
		            "is missing from database";
    	        die();
        	}
        }
        self::venueTableUpgradedCheck();
        $_SESSION['dbcheck'] = true;
    }

    private static function venueTableUpgradedCheck() {
        if (isset($_SESSION['venue_table_upgraded'])) return;

        $q = self::$_db->query('show fields from venue');

        if (self::$_db->numRows($q) == 0) {
            echo "Database error encountered: problem with venue table";
            die();
        }

        $has_data_field = 0;
        $r = self::$_db->fetchObject($q);
        while ($r) {
            if (strcmp("data", $r->Field) == 0)
                $has_data_field = 1;
            $r = self::$_db->fetchObject($q);
        }

        $_SESSION['venue_table_upgraded'] = 1 - $has_data_field;
    }

    public static function venueTableUpgraded() {
        return $_SESSION['venue_table_upgraded'];
    }

    public static function debug() {
        return self::$_debug;
    }

    public static function debugOn() {
        self::$_debug = true;
    }
}

function wfDebug( $text, $logonly = false ) {
    echo $text;
}

function wfDie($txt) { echo $txt, "<br/>\n"; }

$wgProfiling = 0;
$wgDBmysql5 = false;  // sets UTF-8 character set

function wfProfileIn($str) {}
function wfProfileOut($str) { echo $str, "<br/>\n"; }
function wfLogDBError( $text ) { echo $text, "<br/>\n"; }
function wfGetSiteNotice() {}
function wfErrorExit() {
    //echo papersdb_backtrace();
    die();
}
function wfSetBit( &$dest, $bit, $state = true ) {
    return pdDb::debug();
}

function wfSuppressWarnings( $end = false ) {}
function wfRestoreWarnings() {}
function wfDebugDieBacktrace( $msg = '' ) {
    echo papersdb_backtrace();
    die($msg);
}
function wfSetVar( &$dest, $source ) {}
