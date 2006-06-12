<?php ;

// $Id: pdAuthInterests.php,v 1.3 2006/06/12 23:34:38 aicmltec Exp $

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
    var $names;

    /**
     * Constructor.
     */
    function pdAuthInterests(&$db) {
        $q = $db->select('interest', '*', '', "pdAuthInterests::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[] = $r;
            $this->interests[] = $r->interest;
            $r = $db->fetchObject($q);
        }
    }

    function interestExists($interest) {
        assert('isset($this->interests)');
        return in_array($interest, $this->interests);
    }
}

?>
