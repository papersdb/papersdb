<?php ;

// $Id: pdDbAccessor.php,v 1.5 2007/11/02 16:36:29 loyola Exp $

/**
 * A base class for objects that access the database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

class pdDbAccessor {
    public function __construct($mixed) {
        if (isset($mixed))
            $this->load($mixed);
    }

    /**
     * Loads publication data from the object or array passed in
     */
    public function load($mixed) {
        if (is_object($mixed)) {
            foreach (array_keys(get_class_vars(get_class($this))) as $member) {
                if (isset($mixed->$member))
                    $this->$member = $mixed->$member;
            }
        }
        else if (is_array($mixed)) {
            foreach (array_keys(get_class_vars(get_class($this))) as $member) {
                if (isset($mixed[$member]))
                    $this->$member = $mixed[$member];
            }
        }
    }

    public function membersAsArray() {
        $result = array();

        foreach (array_keys(get_class_vars(get_class($this))) as $member) {
            if (isset($this->$member))
                $result[$member] = $this->$member;
        }

        return $result;
    }
}

?>