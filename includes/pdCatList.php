<?php ;

// $Id: pdCatList.php,v 1.9 2006/06/09 22:08:58 aicmltec Exp $

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
            $this->list[] = $r;
            $r = $db->fetchObject($q);
        }
    }
}

?>
