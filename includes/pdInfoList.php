<?php

/**
 * Class to retrieve information table data.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * The class retrieves data from the information table.
 *
 * @package PapersDB
 */
class pdInfoList {
	private function __construct() {}
	
	public static function create($db) {
        assert('is_object($db)');
        $q = $db->select('info', '*', '', "pdInfoList::dbLoad");
        
        foreach ($q as $r) {
            $list[$r->info_id] = $r->name;
        }
        return $list;
    }
}

?>
