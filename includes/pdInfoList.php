<?php ;

// $Id: pdInfoList.php,v 1.2 2006/06/13 05:30:28 aicmltec Exp $

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
    var $name_list;

    /**
     * Constructor.
     */
    function pdInfoList(&$db) {
        assert('is_object($db)');
        $q = $db->select('info', '*', '', "pdInfoList::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = $r;
            $this->name_list[] = $r->name;
            $r = $db->fetchObject($q);
        }
    }

    function infoExists($name) {
        assert('isset($this->name_list)');
        return in_array($name, $this->name_list);
    }
}

?>
