<?php ;

// $Id: pdExtraInfoList.php,v 1.7 2007/11/02 16:36:29 loyola Exp $

/**
 * Retrieves the extra information items from the database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * Class to retrieve the extra information items from the database.
 *
 * @package PapersDB
 */
class pdExtraInfoList {
    public $list;

    /**
     * Constructor.
     */
    public function __construct($db) {
        assert('is_object($db)');

        $this->list = array();

        $q = $db->select('extra_info', array('DISTINCT name'), '',
                         "pdExtraInfoList::dbLoad");
        if ($q === false) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->name] = $r->name;
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
        sort($this->list);
    }
}

?>
