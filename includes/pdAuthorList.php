<?php ;

// $Id: pdAuthorList.php,v 1.7 2006/06/09 06:30:54 aicmltec Exp $

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
    function pdAuthorList(&$db) {
        assert('is_object($db)');

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
