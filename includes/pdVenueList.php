<?php ;

// $Id: pdVenueList.php,v 1.18 2007/10/29 16:26:45 aicmltec Exp $

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
     *
     * By default venues with URLs in the name are not part of the list. Set
     * $all to true to get venues with URLs in the name also.
     */
    public function __construct($db, $options = null) {
        if (isset($options['starting_with'])) {
            $this->loadStartingWith($db, $options['starting_with']);
            return;
        }
        else if (isset($options['cat_id']))
            $q = $db->select('venue', array('venue_id', 'title', 'name'),
                             array('cat_id'    => $options['cat_id']),
                             "pdVenueList::dbLoad");
        else
            $q = $db->select('venue', array('venue_id', 'title', 'name'), null,
                             "pdVenueList::dbLoad");

        if ($q === false) return;
        $r = $db->fetchObject($q);
        while ($r) {
            if (isset($options['concat'])) {
                $title = '';
                $name = '';

                if ($r->title != '') {
                    if (strlen($r->title) < 15) {
                        $title =& $r->title;
                        $name = $r->name;
                    }
                    else {
                        // title longer than 15 chars, dont show name
                        if (strlen($r->title) > 70) {
                            $title = substr($r->title, 0, 70) . '...';
                        }
                        else
                            $title =& $r->title;
                        $name = '';
                    }
                }
                else
                    $name = $r->name;

                if (($name != '') && (strlen($name) > 70))
                    $name = substr($name, 0, 70) . '...';

                if (($title != '') && ($name != '')) {
                    $this->list[$r->venue_id] = $title . ' - ' . $name;
                }
                else if (($title == '') && ($name != '')) {
                    $this->list[$r->venue_id] = $name;
                }
                else {
                    $this->list[$r->venue_id] = $title;
                }
            }
            else if ($r->title != '') {
                $this->list[$r->venue_id] = $r->title;
            }
            else if (($r->name != '')
                     && (isset($options['all'])
                         || (strpos($r->name, 'href') === false))) {
                if (strlen($r->name) > 70)
                    $this->list[$r->venue_id] = substr($r->name, 0, 70) . '...';
                else
                    $this->list[$r->venue_id] = $r->name;
            }
            $r = $db->fetchObject($q);
        }
        if (is_array($this->list))
            uasort($this->list, array(get_class($this), 'sortVenues'));
    }

    private function loadStartingWith($db, $letter) {
        assert('strlen($letter) == 1');

        $letter .= '%';
        $fields = array('title', 'name');

        foreach ($fields as $field) {
            if ($field == 'title')
                $q = $db->select('venue', '*',
                                 array('title LIKE ' . $db->addQuotes($letter)),
                                 "pdVenueList::loadStartingWith");
            else
                $q = $db->select('venue', '*',
                                 array('name LIKE ' . $db->addQuotes($letter),
                                       'LENGTH(title)' => '0'),
                                 "pdVenueList::loadStartingWith");

            if ($q === false) return;

            $r = $db->fetchObject($q);
            while ($r) {
                $this->list[] = new pdVenue($r);
                $r = $db->fetchObject($q);
            }
        }

        if (is_array($this->list))
            uasort($this->list, array(get_class($this), 'sortVenuesObjs'));
    }

    private function sortVenues($a, $b) {
        return (strtolower($a) > strtolower($b));
    }

    private function sortVenuesObjs($a, $b) {
        assert('is_object($a)');
        assert('is_object($b)');

        if (isset($a->title) && isset($b->title)) {
            $sa = $a->title;
            $sb = $b->title;
        }
        else if (!isset($a->title) && isset($b->title)) {
            $sa = $a->name;
            $sb = $b->title;
        }
        else if (isset($a->title) && !isset($b->title)) {
            $sa = $a->title;
            $sb = $b->name;
        }
        else {
            $sa = $a->name;
            $sb = $b->name;
        }

        return (strtolower($sa) > strtolower($sb));
    }
}

?>
