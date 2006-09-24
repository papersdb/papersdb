<?php ;

// $Id: pdInfoList.php,v 1.4 2006/09/24 21:21:42 aicmltec Exp $

/**
 * Class to retrieve information table data.
 *
 * @package PapersDB
 */

/**
 * The class retrieves data from the information table.
 *
 * @package PapersDB
 */
class pdInfoList {
    var $list;

    /**
     * Constructor.
     */
    function pdInfoList(&$db) {
        assert('is_object($db)');
        $q = $db->select('info', '*', '', "pdInfoList::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->info_id] = $r->name;
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
    }

    function infoExists($name) {
        assert('isset($this->list)');
        return in_array($name, $this->list);
    }
}

?>
