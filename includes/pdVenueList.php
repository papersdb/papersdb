<?php ;

// $Id: pdVenueList.php,v 1.5 2006/09/12 19:06:19 aicmltec Exp $

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
        $q = $db->select('venue', array('venue_id', 'title', 'name'), '',
                         "pdVenueList::dbLoad",
                         array('ORDER BY' => 'title ASC'));
        if ($q === false) return;
        $r = $db->fetchObject($q);
        while ($r) {
            if ($r->title != '')
                $this->list[$r->venue_id] = $r->title;
            else if ($r->name != '') {
                if (strlen($r->name) > 70)
                    $this->list[$r->venue_id] = substr($r->name, 0, 70) . '...';
                else
                    $this->list[$r->venue_id] = $r->name;
            }
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
        asort($this->list);
    }
}

?>
