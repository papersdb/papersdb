<?php ;

// $Id: pdCatList.php,v 1.16 2007/11/02 16:36:29 loyola Exp $

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
    public $list;

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

    public function catNumPubs($db, $cat_id) {
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
