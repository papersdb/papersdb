<?php ;

// $Id: pdInfoList.php,v 1.1 2006/06/06 23:12:46 aicmltec Exp $

/**
 * \file
 *
 * \brief
 *
 *
 */

/**
 *
 * \brief
 */
class pdInfoList {
    var $list;

    /**
     * Constructor.
     */
    function pdInfoList($obj = NULL) {
        if (!is_null($obj))
            $this->load($obj);
    }

    /**
     *
     */
    function dbLoad(&$db, $flags = 0) {
        $q = $db->select('info', '*', '', "pdInfoList::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = $r;
            $r = $db->fetchObject($q);
        }
    }
}

?>
