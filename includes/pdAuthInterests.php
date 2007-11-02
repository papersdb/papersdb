<?php ;

// $Id: pdAuthInterests.php,v 1.11 2007/11/02 16:36:29 loyola Exp $

/**
 * Storage and retrieval of author interests to / from the
 * database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * Class for storage and retrieval of author interests to / from the database.
 *
 * @package PapersDB
 */
class pdAuthInterests {
    public $list;

    /**
     * Constructor.
     */
    public function __construct($db) {
        $q = $db->select('interest', '*', '', "pdAuthInterests::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->interest_id] = $r->interest;
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
    }

    public function interestExists($interest) {
        assert('isset($this->list)');
        return in_array($interest, $this->list);
    }

    /**
     * \param $interest_id mixed.
     */
    public function dbDelete($db, $interest_id) {
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
