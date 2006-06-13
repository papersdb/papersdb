<?php ;

// $Id: pdVenueList.php,v 1.3 2006/06/13 19:00:22 aicmltec Exp $

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
    function pdVenueList(&$db) {
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
