<?php ;

// $Id: pdCatList.php,v 1.10 2006/06/13 20:04:37 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of publication categories to / from the
 * database.
 *
 *
 */

/**
 *
 * \brief Class for storage and retrieval of publication categories to / from
 * the database.
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
