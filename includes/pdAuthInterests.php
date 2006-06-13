<?php ;

// $Id: pdAuthInterests.php,v 1.4 2006/06/13 20:04:37 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of author interests to / from the
 * database.
 *
 *
 */

/**
 *
 * \brief Class for storage and retrieval of author interests to / from
 * the database.
 */
class pdAuthInterests {
    var $list;

    /**
     * Constructor.
     */
    function pdAuthInterests(&$db) {
        $q = $db->select('interest', '*', '', "pdAuthInterests::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->interest_id] = $r->interest;
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
    }

    function interestExists($interest) {
        assert('isset($this->interests)');
        return in_array($interest, $this->list);
    }
}

?>
