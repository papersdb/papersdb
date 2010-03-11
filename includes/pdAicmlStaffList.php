<?php

/**
 * Implements a class that retrieves from the database all the authors with a
 * common last name and first initial.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * Class that retrieves all authors from the database all, or the authors with
 * a common last name and first initial.
 *
 * @package PapersDB
 */
class pdAicmlStaffList {
    private function __construct() {}

    public static function &create($db) {
        assert('is_object($db)');

        $q = $db->select('aicml_staff', array('staff_id', 'author_id'), '',
            "pdAicmlStaffList::create",
             array('ORDER BY' => 'start_date ASC'));
        return self::getSelectResults($q);
    }

    private static function &getSelectResults($q) {
        $list = array();
        foreach ($q as $r) {
            $list[$r->staff_id] = $r->author_id;
        }
        return $list;
    }
}

?>
