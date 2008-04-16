<?php

/**
 * Implements a class that retrieves category information for all categories.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * Class that retrieves category information for all categories.
 *
 * @package PapersDB
 */
class pdCatList {
	private function __construct() {}
	
    public static function create($db) {
        assert('is_object($db)');
        $q = $db->select('category', array('cat_id', 'category'), '',
                         "pdCatList::create");
        
        
        if ($q === false) return null;
        
        $list = array();
        $r = $db->fetchObject($q);
        while ($r) {
            $list[$r->cat_id] = $r->category;
            $r = $db->fetchObject($q);
        }
        return $list;
    }
    
    public static function catNumPubs($db, $cat_id) {
        assert('is_object($db)');
        assert('$cat_id != ""');

        $q = $db->selectRow(array('publication', 'pub_cat'),
                            'COUNT(publication.pub_id) as count',
                            array('pub_cat.pub_id=publication.pub_id',
                                  'pub_cat.cat_id' => $cat_id),
                            "pdCatList::catNumPubs");

        assert($q !== false);

        return $q->count;
    }
}

?>
