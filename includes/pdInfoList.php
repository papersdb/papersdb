<?php ;

// $Id: pdInfoList.php,v 1.9 2007/11/02 22:42:26 loyola Exp $

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
