<?php ;

// $Id: pdAuthInterests.php,v 1.1 2006/05/25 16:44:55 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of author interests to / from the
 * database.
 *
 *
 */

/**
 *
 * \brief Class for storage and retrieval of author interests to / from
 * the database.
 */
class pdAuthInterests {
    var $list;

    /**
     * Constructor.
     */
    function pdAuthInterests($obj = NULL) {
        if (!is_null($obj))
            $this->objLoad($obj);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad(&$db) {
        $q = $db->select('interest', '*', '', "pdAuthInterests::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = $r;
            $r = $db->fetchObject($q);
        }
    }
}

?>
