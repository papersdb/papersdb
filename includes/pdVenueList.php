<?php ;

// $Id: pdVenueList.php,v 1.2 2006/06/06 21:11:12 aicmltec Exp $

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
            $this->load($obj);
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
