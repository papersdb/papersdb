<?php ;

// $Id: pdVenueList.php,v 1.4 2006/06/13 20:04:37 aicmltec Exp $

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
            $this->list[$r->venue_id] = $r->title;
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
    }
}

?>
