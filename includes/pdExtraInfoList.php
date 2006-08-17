<?php ;

// $Id: pdExtraInfoList.php,v 1.2 2006/08/17 20:34:40 aicmltec Exp $

/**
 * \file
 *
 * \brief
 */

/**
 * \brief
 */
class pdExtraInfoList {
    var $list;

    /**
     * Constructor.
     */
    function pdExtraInfoList(&$db) {
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
