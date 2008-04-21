<?php

/**
 * Retrieves the extra information items from the database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * Class to retrieve the extra information items from the database.
 *
 * @package PapersDB
 */
class pdExtraInfoList {
	private function __construct() {}
	
	public static function create($db) {
        assert('is_object($db)');

        $q = $db->select('extra_info', array('DISTINCT name'), '',
                         "pdExtraInfoList::dbLoad");
        $list = array();
        foreach ($q as $r) {
            $list[$r->name] = $r->name;
        }
        sort($this->list);
        return $list;
    }
}

?>
