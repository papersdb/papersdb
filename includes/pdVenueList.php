<?php ;

// $Id: pdVenueList.php,v 1.1 2006/06/04 00:06:45 aicmltec Exp $

/**
 * \file
 *
 * \brief
 */

/**
 * \brief
 */
class pdVenueList {
    var $list;

    /**
     * Constructor.
     */
    function pdVenueList($obj = NULL) {
        if (!is_null($obj))
            $this->objLoad($obj);
    }

    /**
     * Loads all venue names from the database in ascending order.
     *
     * Use flags to load individual tables
     */
    function dbLoad(&$db, $flags = 0) {
        $q = $db->select('venue', array('venue_id', 'title'), '',
                         "pdVenueList::dbLoad",
                         array('ORDER BY' => 'title ASC'));
        if ($q === false) return;
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = $r;
            $r = $db->fetchObject($q);
        }
    }
}

?>
