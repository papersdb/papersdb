<?php ;

// $Id: pdDbAccessor.php,v 1.2 2007/03/19 22:04:39 aicmltec Exp $

/**
 * A base class for objects that access the database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

class pdDbAccessor {
    function pdDbAccessor($mixed) {
        if (isset($mixed))
            $this->load($mixed);
    }

    /**
     * Loads publication data from the object or array passed in
     */
    function load($mixed) {
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
}

?>