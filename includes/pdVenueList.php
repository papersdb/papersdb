<?php ;

// $Id: pdVenueList.php,v 1.8 2006/09/25 19:59:09 aicmltec Exp $

/**
 * Contains class to retrieve a list of venues.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * Class that build a list of venues.
 *
 * @package PapersDB
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
