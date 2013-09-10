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
class pdAuthorList {
	private function __construct() {}

    public static function &create($db, $firstname = null, $lastname = null,
    							  $as_fist_last = false) {
        assert('is_object($db)');

        if (($firstname == null) && ($lastname == null)) {
            $q = $db->select('author', array('author_id', 'name'), '',
                             "pdAuthorList::create",
                             array('ORDER BY' => 'name ASC'));
        }
        else {
            if (($lastname != null) && ($firstname == null))
                $name = $lastname . '%';
            else if (($lastname == null) && ($firstname != null))
                $name = '%' . $firstname . '%';
            else
                $name = $lastname . ', ' . $firstname[0] . '%';

            $q = $db->select('author', '*',
                             array('name LIKE ' . $db->addQuotes($name)),
                             "pdAuthorList::create",
                             array('ORDER BY' => 'name ASC'));
        }
        return self::getSelectResults($q, $as_fist_last);
    }

    public static function createFromAuthorIds($db, $author_ids, $as_fist_last = false) {
        assert('is_array($author_ids)');

        $q = $db->select('author', array('author_id', 'name'),
                         array('author_id' => $author_ids),
                         '', array('ORDER BY' => 'name ASC'));
        return self::getSelectResults($q, $as_fist_last);
    }

    private static function &getSelectResults($q, $as_fist_last = false) {
        $list = array();
        foreach ($q as $r) {
        	$name = $r->name;

        	if ($as_fist_last) {
	            $names = explode(',', $name);

	            if (count($names) == 2)
                	$name = trim($names[1]) . ' ' . trim($names[0]);
        	}

            $list[$r->author_id] = $name;
        }
        return $list;
    }
}

?>
