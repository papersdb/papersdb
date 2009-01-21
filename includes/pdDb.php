<?php

/*-----------------------------------------------------------------------------
 *
 * The information contained herein is proprietary and confidential to Alberta
 * Ingenuity Centre For Machine Learning (AICML) and describes aspects of AICML
 * products and services that must not be used or implemented by a third party
 * without first obtaining a license to use or implement.
 *
 * Copyright 2008 Alberta Ingenuity Centre For Machine Learning.
 *
 *-----------------------------------------------------------------------------
 */

require_once 'defines.php';

/**
 * Singleton wrapper class for database access.
 */

class pdDb {
    private $mysqli;
    private static $db_server = 'kingman.cs.ualberta.ca';
    private static $db_user = 'papersdb';
    private static $db_name = 'pubDB';
    private static $db_passwd = '';
    private static $_debug = false;
    
    private $current_db_name;

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
    
    
    const LIST_COMMA = 0;
    const LIST_AND   = 1;
    const LIST_SET   = 2;
    const LIST_NAMES = 3;
    const LIST_OR    = 4;
    
    // statistics
    protected $cmdcounter = 0;
    protected $rowcounter = 0;
    protected $dbtime     = 0; // time taken by commands
    protected $starttime  = 0;
    
    public function __construct($options = array()) {
        $this->starttime = $this->microtime_float();
        
        $server = isset($options['server']) ? $options['server'] : self::$db_server;  
        $this->current_db_name = isset($options['name'])   ? $options['name']   : self::$db_name;   
        $user   = isset($options['user'])   ? $options['user']   : self::$db_user;  
        $passwd = isset($options['passwd']) ? $options['passwd']   : self::$db_passwd; 

        $this->mysqli = mysqli::init();
        $this->mysqli->real_connect($server, $user, $passwd, $this->current_db_name);
        
        if (mysqli_connect_errno()) {
            die("Connect failed: " . mysqli_connect_error() . "\n");
        }
    }
    
    public function __destruct() {
        $this->close();
		
        if (self::$_debug) {
            echo $this->showStatistics();
        }
    }
    
    public function getDbName() {
        return $this->current_db_name;
    }
    
    // explicitly terminate object/connection
    private function close() {
        if ($this->mysqli) {
            $this->mysqli->close();
        }
        $this->mysqli = false;
    }
    
    public static function defaultNew() {   
    	$server = self::$db_server;
    	$db_name = self::$db_name;
    	
        if (isset($_ENV['COMPUTERNAME']) && ($_ENV['COMPUTERNAME'] == 'GETAFIX')) {
            $server = 'localhost';
            $db_name = 'pubDBdev';
            //self::$_debug = true;         
        }  
    	
        if (isset($_ENV['HOSTNAME']) && ($_ENV['HOSTNAME'] == 'levante')) {
        	$server = 'localhost';
        	$db_name = 'pubDBdev';
            //self::$_debug = true;        	
        }  
               
        if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] == 'localhost')) {
            $db_name = 'pubDBdev';
            //self::$_debug = true;
        }  
        
        return new pdDb(array('server' => $server,
                             'user'   => self::$db_user,
                             'passwd' => self::$db_passwd,
                             'name'   => $db_name));
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
    
    
    /**
     * execute SELECT return object field
     *
     * @param string $sql the MySQL command
     * @return array an array containing the rows returned by the query.
     */
    public function &query($sql) {
        $this->cmdcounter++;
        self::wfDebug($sql);
        $time1 = $this->microtime_float();
        $result = $this->mysqli->query($sql);   
        $this->dbtime += ($this->microtime_float() - $time1);     
        if ($result === false) {
            self::wfDie("Error: " . $this->mysqli->error . "\n");
        }
        
        $result_array = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_object()) {
                $result_array[] = $row;
            }
            $this->rowcounter += count($result_array);
        }
        return $result_array;
    }
    
    /**
     * execute SELECT return individual row
     *
     * @param string $sql
     * @return array the row returned by the MySQL query.
     */
    function queryRow($sql) {
        self::wfDebug($sql);
        $result = $this->mysqli->query($sql);      
        if ($result === false) {
            self::wfDie("Error: " . $this->mysqli->error . "\n");
        }
        
        $row = $result->fetch_array();
        if ($row == NULL) {
            return false;
        }
        $result->close();
        return $row[0];
    }
    
    /**
     * execute SQL command without result (INSERT, DELETE, etc.)
     *
     * @param unknown_type $sql
     * @return unknown
     */
    function execute($sql) {
        $this->cmdcounter++;
        self::wfDebug($sql);
        $result = $this->mysqli->real_query($sql);
        if (!$result) {
            self::wfDie("Error: " . $this->mysqli->error . "\n");
        }
        return true;
    }
    
    /**
     * execute SELECT return object field
     * 
     * Most of this code borrowed from MediaWiki Database class.
     *
     * @param unknown_type $sql
     * @return unknown
     */
    public function &select($tables, $fields, $conds = '', $file = '', $opts = array()) {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }
        if (!is_array($opts)) {
            $opts = array($opts);
        }
        
        if (is_array($tables)) {
            $from = 'FROM ' . implode(',', $tables);
        }        
        else if (is_string($tables)) {
            $from = 'FROM ' . $tables;
        }
        else {
            $from = '';
        }

		list($useIndex, $tailOpts) = $this->makeSelectOptions($opts);

		if (!empty($conds)) {
			if (is_array( $conds)) {
				$conds = $this->makeList($conds, self::LIST_AND);
			}
			$sql = "SELECT $fields $from $useIndex WHERE $conds $tailOpts";
		} else {
			$sql = "SELECT $fields $from $useIndex $tailOpts";
		}

		return $this->query($sql);
        
    }

	/**
	 * Single row SELECT wrapper
	 * Aborts or returns FALSE on error
	 *
	 * $vars: the selected variables
	 * $conds: a condition map, terms are ANDed together.
	 *   Items with numeric keys are taken to be literal conditions
	 * Takes an array of selected variables, and a condition map, which is ANDed
	 * e.g: selectRow( "page", array( "page_id" ), array( "page_namespace" =>
	 * NS_MAIN, "page_title" => "Astronomy" ) )   would return an object where
	 * $obj- >page_id is the ID of the Astronomy article
     * 
     * Most of this code borrowed from MediaWiki Database class.
	 */
	public function selectRow($table, $vars, $conds, $fname = '', $options = array()) {
		$options['LIMIT'] = 1;
		$res = $this->select( $table, $vars, $conds, $fname, $options );
		if (count($res) == 0) {
			return false;
		}
		return $res[0];

	}

	/**
	 * INSERT wrapper, inserts an array into a table
	 *
	 * $a may be a single associative array, or an array of these with numeric keys, for
	 * multi-row insert.
	 *
	 * Usually aborts on failure
	 * If errors are explicitly ignored, returns success
	 */
	function insert( $table, $a, $fname = '', $options = array() ) {
		# No rows to insert, easy just return now
		if ( !count( $a ) ) {
			return true;
		}

		if ( !is_array( $options ) ) {
			$options = array( $options );
		}
		if ( isset( $a[0] ) && is_array( $a[0] ) ) {
			$multi = true;
			$keys = array_keys( $a[0] );
		} else {
			$multi = false;
			$keys = array_keys( $a );
		}

		$sql = 'INSERT ' . implode( ' ', $options ) .
			" INTO $table (" . implode( ',', $keys ) . ') VALUES ';

		if ( $multi ) {
			$first = true;
			foreach ( $a as $row ) {
				if ( $first ) {
					$first = false;
				} else {
					$sql .= ',';
				}
				$sql .= '(' . $this->makeList( $row ) . ')';
			}
		} else {
			$sql .= '(' . $this->makeList( $a ) . ')';
		}
		return $this->execute($sql);
	}

	/**
	 * UPDATE wrapper, takes a condition array and a SET array
     * 
     * Code borrowed from MediaWiki Database class.
	 *
	 * @param string $table  The table to UPDATE
	 * @param array  $values An array of values to SET
	 * @param array  $conds  An array of conditions (WHERE). Use '*' to update all rows.
	 * @param string $fname  The Class::Function calling this function
	 *                       (for the log)
	 * @param array  $options An array of UPDATE options, can be one or
	 *                        more of IGNORE, LOW_PRIORITY
	 */
	function update( $table, $values, $conds, $fname = '', $options = array() ) {
		$opts = $this->makeUpdateOptions( $options );
		$sql = "UPDATE $opts $table SET " . $this->makeList( $values, self::LIST_SET );
		if ( $conds != '*' ) {
			$sql .= " WHERE " . $this->makeList( $conds, self::LIST_AND );
		}
		$this->execute($sql);
	}

	/**
	 * DELETE query wrapper
     * 
     * Code borrowed from MediaWiki Database class.
	 *
	 * Use $conds == "*" to delete all rows
	 */
	function delete( $table, $conds, $fname = 'Database::delete' ) {
		if ( !$conds ) {
			wfDebugDieBacktrace( 'Database::delete() called with no conditions' );
		}
		$sql = "DELETE FROM $table";
		if ( $conds != '*' ) {
			$sql .= ' WHERE ' . $this->makeList( $conds, self::LIST_AND );
		}
		return $this->execute($sql);
	}
    
    /**
     * return insert_id
     *
     * @return unknown
     */
    function insertId() {
        return $this->mysqli->insert_id;
    }

    public function build_constraint_string($raw, $field, $bool_op = 'AND'){
        //Okay, we accept a match with any part of the string. But we also like quotes.
        //So we break on quotes, but we don't work recursively.
        $search = $this->mysqli->real_escape_string(trim($raw));
        if (strlen($search) < 1)
        return NULL;
        $search = explode(' ', $search);
        $nsearch = "($field LIKE '%" . join("%' $bool_op $field LIKE '%", $search) .'%\')';
        return $nsearch;
    }

	/**
	 * Make UPDATE options for the Database::update function
     * 
     * Code borrowed from MediaWiki Database class.
	 *
	 * @access private
	 * @param array $options The options passed to Database::update
	 * @return string
	 */
	private function makeUpdateOptions( $options ) {
		if( !is_array( $options ) ) {
			$options = array( $options );
		}
		$opts = array();
		if ( in_array( 'LOW_PRIORITY', $options ) )
			$opts[] = $this->lowPriorityOption();
		if ( in_array( 'IGNORE', $options ) )
			$opts[] = 'IGNORE';
		return implode(' ', $opts);
	}

	/**
	 * Returns an optional USE INDEX clause to go after the table, and a
	 * string to go at the end of the query
     * 
     * Code borrowed from MediaWiki Database class.
	 *
	 * @access private
	 *
	 * @param array $options an associative array of options to be turned into
	 *              an SQL query, valid keys are listed in the function.
	 * @return array
	 */
	private function makeSelectOptions($options) {
		$tailOpts = '';

		if ( isset( $options['GROUP BY'] ) ) {
			$tailOpts .= " GROUP BY {$options['GROUP BY']}";
		}
		if ( isset( $options['ORDER BY'] ) ) {
			$tailOpts .= " ORDER BY {$options['ORDER BY']}";
		}
		if (isset($options['LIMIT'])) {
			$tailOpts .= $this->limitResult('', $options['LIMIT'],
				isset($options['OFFSET']) ? $options['OFFSET'] : false);
		}
		if ( is_numeric( array_search( 'FOR UPDATE', $options ) ) ) {
			$tailOpts .= ' FOR UPDATE';
		}

		if ( is_numeric( array_search( 'LOCK IN SHARE MODE', $options ) ) ) {
			$tailOpts .= ' LOCK IN SHARE MODE';
		}

		if ( isset( $options['USE INDEX'] ) && ! is_array( $options['USE INDEX'] ) ) {
			$useIndex = $this->useIndexClause( $options['USE INDEX'] );
		} else {
			$useIndex = '';
		}
		return array( $useIndex, $tailOpts );
	}  

    /**
	 * Makes a wfStrencoded list from an array
	 * $mode: LIST_COMMA         - comma separated, no field names
	 *        LIST_AND           - ANDed WHERE clause (without the WHERE)
	 *        LIST_SET           - comma separated with field names, like a SET clause
	 *        LIST_NAMES         - comma separated field names
     * 
     * Code borrowed from MediaWiki Database class.
     */
	private function makeList( $a, $mode = self::LIST_COMMA ) {
		assert('is_array($a)');

		$first = true;
		$list = '';
		foreach ( $a as $field => $value ) {
			if ( !$first ) {
				if ( $mode == self::LIST_AND ) {
					$list .= ' AND ';
				} elseif($mode == self::LIST_OR) {
					$list .= ' OR ';
				} else {
					$list .= ',';
				}
			} else {
				$first = false;
			}
			if ( ($mode == self::LIST_AND || $mode == self::LIST_OR) && is_numeric( $field ) ) {
				$list .= "($value)";
			} elseif ( $mode == self::LIST_AND && is_array ($value) ) {
				$list .= $field." IN (".$this->makeList($value).") ";
			} else {
				if ( $mode == self::LIST_AND || $mode == self::LIST_OR || $mode == self::LIST_SET ) {
					$list .= "$field = ";
				}
				$list .= $mode == self::LIST_NAMES ? $value : $this->addQuotes( $value );
			}
		}
		return $list;
	}

	/**
	 * Construct a LIMIT query with optional offset
	 * This is used for query pages
	 * $sql string SQL query we will append the limit too
	 * $limit integer the SQL limit
	 * $offset integer the SQL offset (default false)
     * 
     * Code borrowed from MediaWiki Database class.
	 */
	function limitResult($sql, $limit, $offset=false) {
		return " $sql LIMIT ".((is_numeric($offset) && $offset != 0)?"{$offset},":"")."{$limit} ";
	}
	function limitResultForUpdate($sql, $num) {
		return $this->limitResult($sql, $num, 0);
	}

	/**
	 * If it's a string, adds quotes and backslashes
	 * Otherwise returns as-is
     * 
     * Code borrowed from MediaWiki Database class.
	 */
	public function addQuotes( $s ) {
		if ( is_null( $s ) ) {
			return 'NULL';
		} else {
			# This will also quote numeric values. This should be harmless,
			# and protects against weird problems that occur when they really
			# _are_ strings such as article titles and string->number->string
			# conversion is not 1:1.
			return "'" . $this->strencode( $s ) . "'";
		}
	}

	/**
	 * Strips slashes and adds quotes if the value passed in is numeric or null.
	 */
	public function quote_smart($value) {
	    // Stripslashes
	    if (get_magic_quotes_gpc()) {
	        $value = stripslashes($value);
	    }
	    // Quote if not a number or a numeric string
	    if (!is_numeric($value) || $value[0] == '0') {
	        $value = "'" . $this->mysqli->real_escape_string($value) . "'";
	    }
	    return $value;
	}

	public function showStatistics() {
	    $totalTime = $this->microtime_float() - $this->starttime;
	    return "<div style=\"font-size: 0.7em; margin: 10px 10px 10px; color:#AA0000;\">"
		    . "SQL commands: $this->cmdcounter\n"
    	    . "<br />Number of returned rows: $this->rowcounter\n"
	        . "<br />total query time (MySQL): $this->dbtime\n"
	    	. "<br />Processing time (PHP): " . ($totalTime - $this->dbtime) . "\n"
	    	. "<br />Total time since MyDb creation / last reset: $totalTime</div>\n";
	}
	
	public function resetStatistics() {
	        $this->sqlcounter = 0;
	        $this->rowcounter = 0;
	        $this->dbtime = 0;
	        $this->starttime = $this->microtime_float(); 
	}
	
	private function microtime_float() {
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

	/**
	 * Wrapper for addslashes()
	 * @param string $s String to be slashed.
	 * @return string slashed string.
	 */
	private function strencode( $s ) {
		return addslashes( $s );
	}    

    public static function debug() {
        return self::$_debug;
    }

    public static function debugOn() {
        self::$_debug = true;
    }

    private static function wfDebug( $text, $logonly = false ) {
        if (!self::$_debug) return;
        
        if (PHP_SAPI != "cli") {
            echo '<pre>';
        }
        echo htmlentities(format80($text));
        if (PHP_SAPI != "cli") {
            echo '</pre>';
        }
        else {
            echo "\n";
        }
    }

    private static function wfDie($txt) {
        echo $txt, "\n";
        if (PHP_SAPI != "cli") {
            echo "<br/>\n";
        }
        papersdb_backtrace();
        exit(1);
    }
}
