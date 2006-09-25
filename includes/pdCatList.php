<?php ;

// $Id: pdCatList.php,v 1.12 2006/09/25 19:59:09 aicmltec Exp $

/**
 * Implements a class that retrieves category information for all categories.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * Class that retrieves category information for all categories.
 *
 * @package PapersDB
 */
class pdCatList {
    var $list;

    /**
     * Retrieves the cat_id for all categories in the database.
     */
    function pdCatList(&$db) {
        $q = $db->select('category', array('cat_id', 'category'), '',
                         "pdCatList::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->cat_id] = $r->category;
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
    }
}

?>
