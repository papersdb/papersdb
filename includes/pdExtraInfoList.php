<?php ;

// $Id: pdExtraInfoList.php,v 1.5 2007/03/19 22:04:39 aicmltec Exp $

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
    var $list;

    /**
     * Constructor.
     */
    function pdExtraInfoList($db) {
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
