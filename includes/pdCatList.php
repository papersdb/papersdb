<?php ;

// $Id: pdCatList.php,v 1.11 2006/09/24 21:21:42 aicmltec Exp $

/**
 * Storage and retrieval of publication categories to / from the
 * database.
 *
 * @package PapersDB
 */

/**
 * Class for storage and retrieval of publication categories to / from
 * the database.
 *
 * @package PapersDB
 */
class pdCatList {
    var $list;

    /**
     * Constructor.
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
