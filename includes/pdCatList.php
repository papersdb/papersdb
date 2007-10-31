<?php ;

// $Id: pdCatList.php,v 1.15 2007/10/31 23:17:34 loyola Exp $

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
    var $list;

    /**
     * Retrieves the cat_id for all categories in the database.
     */
    public function __construct($db) {
        assert('is_object($db)');
        $q = $db->select('category', array('cat_id', 'category'), '',
                         "pdCatList::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->cat_id] = $r->category;
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
    }

    function catNumPubs($db, $cat_id) {
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
