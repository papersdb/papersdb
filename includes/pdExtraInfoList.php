<?php ;

// $Id: pdExtraInfoList.php,v 1.8 2007/11/02 22:42:26 loyola Exp $

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
    public static function create($db) {
        assert('is_object($db)');

        $q = $db->select('extra_info', array('DISTINCT name'), '',
                         "pdExtraInfoList::dbLoad");
        
        if ($q === false) return null;

        $list = array();
        $r = $db->fetchObject($q);
        while ($r) {
            $list[$r->name] = $r->name;
            $r = $db->fetchObject($q);
        }
        sort($this->list);
        return $list;
    }
}

?>
