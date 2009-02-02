<?php

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
	private function __construct() {}
	
    /**
     * By default venues with URLs in the name are not part of the list. Set
     * $all to true to get venues with URLs in the name also.
     */
    public static function create($db, $options = null) {
        if (isset($options['starting_with']))
            return self::loadStartingWith($db, $options['starting_with'], $options['cat_id']);
        else if (isset($options['cat_id']))
            $q = $db->select('venue', array('venue_id', 'title', 'name'),
                             array('cat_id' => $options['cat_id']),
                             "pdVenueList::dbLoad");
        else
            $q = $db->select('venue', array('venue_id', 'title', 'name'),
                              null, "pdVenueList::dbLoad");

        $list = array();
        foreach ($q as $r) {
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
                    $list[$r->venue_id] = $title . ' - ' . $name;
                }
                else if (($title == '') && ($name != '')) {
                    $list[$r->venue_id] = $name;
                }
                else {
                    $list[$r->venue_id] = $title;
                }
            }
            else if ($r->title != '') {
                $list[$r->venue_id] = $r->title;
            }
            else if (($r->name != '')
                     && (isset($options['all'])
                         || (strpos($r->name, 'href') === false))) {
                if (strlen($r->name) > 70)
                    $list[$r->venue_id] = substr($r->name, 0, 70) . '...';
                else
                    $list[$r->venue_id] = $r->name;
            }
        }
        
        uasort($list, array('pdVenueList', 'sortVenues'));
        return $list;
    }

    private static function loadStartingWith($db, $letter, $cat_id = null) {
        assert('strlen($letter) == 1');

        $list = array();
        $letter .= '%';
        $fields = array('title', 'name');
        
        $conds = array('(title LIKE ' . $db->addQuotes($letter) . ') OR ' 
            . '(name LIKE ' . $db->addQuotes($letter) . ')');
        
        if ($cat_id != null) {
        	$conds[] = "cat_id = $cat_id";
        }

        $q = $db->select('venue', '*', $conds, "pdVenueList::loadStartingWith");

        foreach ($q as $r) {
        	$list[] = new pdVenue($r);
        }

        uasort($list, array('pdVenueList', 'sortVenuesObjs'));
        return $list;
    }

    private static function sortVenues($a, $b) {
        return (strtolower($a) > strtolower($b));
    }

    private static function sortVenuesObjs($a, $b) {
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
