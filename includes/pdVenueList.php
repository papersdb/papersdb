<?php ;

// $Id: pdVenueList.php,v 1.6 2006/09/13 16:36:40 aicmltec Exp $

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
    function pdVenueList(&$db, $type = null) {
        if ($type == null)
            $q = $db->select('venue', array('venue_id', 'title', 'name'), '',
                             "pdVenueList::dbLoad");
        else
            $q = $db->select('venue', array('venue_id', 'title', 'name'),
                             array('type' => $type),
                             "pdVenueList::dbLoad");

        if ($q === false) return;
        $r = $db->fetchObject($q);
        while ($r) {
            if ($r->title != '')
                $this->list[$r->venue_id] = $r->title;
            else if (($r->name != '') && (strpos($r->name, 'href') === false)) {
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
