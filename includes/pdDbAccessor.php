<?php ;

// $Id: pdDbAccessor.php,v 1.6 2007/11/07 22:47:46 loyola Exp $

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
            $ob_vars =& array_keys(get_object_vars($this));

            foreach (array_keys(get_object_vars($mixed)) as $member) {
                if (in_array($member, $ob_vars)) {
                    $this->$member = $mixed->$member;
                }
            }
        }
        else if (is_array($mixed)) {
            $ob_vars =& array_keys(get_object_vars($this));

            foreach (array_keys($mixed) as $key) {
                if (in_array($key, $ob_vars))
                    $this->$key = $mixed[$key];
            }
        }
        else
            assert('false');   // invalid type for $mixed
    }

    public function membersAsArray() {
        $result = array();

        foreach (array_keys(get_object_vars($this)) as $member) {
            if (isset($this->$member))
                $result[$member] = $this->$member;
        }

        return $result;
    }
}

?>