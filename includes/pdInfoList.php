<?php ;

// $Id: pdInfoList.php,v 1.3 2006/06/13 20:04:37 aicmltec Exp $

/**
 * \file
 *
 * \brief
 *
 */

/**
 * \brief
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