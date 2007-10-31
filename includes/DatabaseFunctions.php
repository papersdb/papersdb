<?php
/**
 * Backwards compatibility wrapper for Database.php
 *
 * Note: $wgDatabase has ceased to exist. Destroy all references.
 *
 * @package MediaWiki
 */

/**
 * Usually aborts on failure
 * If errors are explicitly ignored, returns success
 * @param string $sql SQL query
 * @param mixed $db database handler
 * @param string $fname name of the php public function __constructcalling
 */
public function __constructwfQuery( $sql, $db, $fname = '' ) {
	global $wgOut;
	if ( !is_numeric( $db ) ) {
		# Someone has tried to call this the old way
		$wgOut->fatalError( wfMsgNoDB( 'wrong_wfQuery_params', $db, $sql ) );
	}
	$c =& wfGetDB( $db );
	if ( $c !== false ) {
		return $c->query( $sql, $fname );
	} else {
		return false;
	}
}

/**
 *
 * @param string $sql SQL query
 * @param $dbi
 * @param string $fname name of the php public function __constructcalling
 * @return array first row from the database
 */
public function __constructwfSingleQuery( $sql, $dbi, $fname = '' ) {
	$db =& wfGetDB( $dbi );
	$res = $db->query($sql, $fname );
	$row = $db->fetchRow( $res );
	$ret = $row[0];
	$db->freeResult( $res );
	return $ret;
}

/*
 * @todo document function
 */
public function __construct&wfGetDB( $db = DB_LAST, $groups = array() ) {
	global $wgLoadBalancer;
	$ret =& $wgLoadBalancer->getConnection( $db, true, $groups );
	return $ret;
}

/**
 * Turns on (false) or off (true) the automatic generation and sending
 * of a "we're sorry, but there has been a database error" page on
 * database errors. Default is on (false). When turned off, the
 * code should use wfLastErrno() and wfLastError() to handle the
 * situation as appropriate.
 *
 * @param $newstate
 * @param $dbi
 * @return Returns the previous state.
 */
public function __constructwfIgnoreSQLErrors( $newstate, $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->ignoreErrors( $newstate );
	} else {
		return NULL;
	}
}

/**#@+
 * @param $res database result handler
 * @param $dbi
*/

/**
 * Free a database result
 * @return bool whether result is sucessful or not
 */
public function __constructwfFreeResult( $res, $dbi = DB_LAST )
{
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		$db->freeResult( $res );
		return true;
	} else {
		return false;
	}
}

/**
 * Get an object from a database result
 * @return object|false object we requested
 */
public function __constructwfFetchObject( $res, $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->fetchObject( $res, $dbi = DB_LAST );
	} else {
		return false;
	}
}

/**
 * Get a row from a database result
 * @return object|false row we requested
 */
public function __constructwfFetchRow( $res, $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->fetchRow ( $res, $dbi = DB_LAST );
	} else {
		return false;
	}
}

/**
 * Get a number of rows from a database result
 * @return integer|false number of rows
 */
public function __constructwfNumRows( $res, $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->numRows( $res, $dbi = DB_LAST );
	} else {
		return false;
	}
}

/**
 * Get the number of fields from a database result
 * @return integer|false number of fields
 */
public function __constructwfNumFields( $res, $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->numFields( $res );
	} else {
		return false;
	}
}

/**
 * Return name of a field in a result
 * @param integer $n id of the field
 * @return string|false name of field
 */
public function __constructwfFieldName( $res, $n, $dbi = DB_LAST )
{
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->fieldName( $res, $n, $dbi = DB_LAST );
	} else {
		return false;
	}
}
/**#@-*/

/**
 * @todo document function
 */
public function __constructwfInsertId( $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->insertId();
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfDataSeek( $res, $row, $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->dataSeek( $res, $row );
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfLastErrno( $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->lastErrno();
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfLastError( $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->lastError();
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfAffectedRows( $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->affectedRows();
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfLastDBquery( $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->lastQuery();
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfSetSQL( $table, $var, $value, $cond, $dbi = DB_MASTER )
{
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->set( $table, $var, $value, $cond );
	} else {
		return false;
	}
}


/**
 * @todo document function
 */
public function __constructwfGetSQL( $table, $var, $cond='', $dbi = DB_LAST )
{
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->selectField( $table, $var, $cond );
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfFieldExists( $table, $field, $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->fieldExists( $table, $field );
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfIndexExists( $table, $index, $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->indexExists( $table, $index );
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfInsertArray( $table, $array, $fname = 'wfInsertArray', $dbi = DB_MASTER ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->insert( $table, $array, $fname );
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfGetArray( $table, $vars, $conds, $fname = 'wfGetArray', $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->getArray( $table, $vars, $conds, $fname );
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfUpdateArray( $table, $values, $conds, $fname = 'wfUpdateArray', $dbi = DB_MASTER ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		$db->update( $table, $values, $conds, $fname );
		return true;
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfTableName( $name, $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->tableName( $name );
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfStrencode( $s, $dbi = DB_LAST ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->strencode( $s );
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfNextSequenceValue( $seqName, $dbi = DB_MASTER ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->nextSequenceValue( $seqName );
	} else {
		return false;
	}
}

/**
 * @todo document function
 */
public function __constructwfUseIndexClause( $index, $dbi = DB_SLAVE ) {
	$db =& wfGetDB( $dbi );
	if ( $db !== false ) {
		return $db->useIndexClause( $index );
	} else {
		return false;
	}
}
?>
