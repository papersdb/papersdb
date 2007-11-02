<?php ;

// $Id: pdAuthInterests.php,v 1.12 2007/11/02 22:42:26 loyola Exp $

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
    public static function createList($db) {
        assert('is_object($db)');
        
        $q = $db->select('interest', '*', '', 'pdAuthInterests::createList');
        
        // this DB table must always be populated
        assert($q !== false);
        
        $list = array();
        $r = $db->fetchObject($q);
        while ($r) {
            $list[$r->interest_id] = $r->interest;
            $r = $db->fetchObject($q);
        }
        return $list;
    }

    /**
     * \param $interest_id mixed.
     */
    public static function dbDelete($db, $interest_id) {
        assert('is_object($db)');
        
        if ($interest_id == null)
            return;
            
        if (is_array($interest_id)) {
            foreach ($interest_id as $id) {
                $db->delete('interest', array('interest_id' => $id),
                            'pdAuthInterests::dbDelete');
                $db->delete('author_interest', array('interest_id' => $id),
                            'pdAuthInterests::dbDelete');
            }
            return;
        }
        else if (is_string($interest_id)) {
            $db->delete('interest', array('interest_id' => $interest_id),
                        'pdAuthInterests::dbDelete');
            $db->delete('author_interest', array('interest_id' => $interest_id),
                        'pdAuthInterests::dbDelete');
            return;
        }

        // should never get here: invalid type for $interest_id
        assert('false');
    }
}

?>
