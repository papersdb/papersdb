<?php ;

// $Id: pdAuthInterests.php,v 1.5 2006/06/13 23:56:04 aicmltec Exp $

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

    /**
     * \param $interest_id mixed.
     */
    function dbDelete(&$db, $interest_id) {
        assert('is_array($this->list)');

        if ($interest_id == null)
            return;
        if (is_array($interest_id)) {
            foreach ($interest_id as $id) {
                $db->delete('interest', array('interest_id' => $id),
                            'pdAuthInterests::dbDelete');
                $db->delete('author_interest', array('interest_id' => $id),
                            'pdAuthInterests::dbDelete');
                unset($this->list[$id]);
            }
        }
        else if (is_string($interest_id)) {
            $db->delete('interest', array('interest_id' => $interest_id),
                        'pdAuthInterests::dbDelete');
            $db->delete('author_interest', array('interest_id' => $interest_id),
                        'pdAuthInterests::dbDelete');
            unset($this->list[$interest_id]);
        }
        else {
            assert('false'); // invalid type
        }

    }
}

?>
