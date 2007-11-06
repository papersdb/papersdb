<?php ;

// $Id: pdInfoList.php,v 1.10 2007/11/06 18:05:36 loyola Exp $

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
        
        if ($q === false) return null;
        
        $r = $db->fetchObject($q);
        while ($r) {
            $list[$r->info_id] = $r->name;
            $r = $db->fetchObject($q);
        }
        return $list;
    }
}

?>
