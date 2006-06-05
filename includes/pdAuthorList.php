<?php ;

// $Id: pdAuthorList.php,v 1.5 2006/06/05 23:33:42 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of publication authors to / from the database.
 */

/**
 * \brief Class for storage and retrieval of publication authors to / from the
 * database.
 */
class pdAuthorList {
    var $list;

    /**
     * Constructor.
     */
    function pdAuthorList($obj = NULL) {
        if (!is_null($obj))
            $this->objLoad($obj);
    }

    /**
     * Loads all author names from the database in ascending order.
     *
     * Use flags to load individual tables
     */
    function dbLoad(&$db, $flags = 0) {
        $q = $db->select('author', array('author_id', 'name'), '',
                         "pdAuthorList::dbLoad",
                         array('ORDER BY' => 'name ASC'));
        if ($q === false) return;
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->author_id] = $r;
            $r = $db->fetchObject($q);
        }
    }
}

?>
