<?php ;

// $Id: pdAuthorList.php,v 1.22 2008/01/15 22:57:14 loyola Exp $

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
	
    public static function create($db, $firstname = null, $lastname = null,
    							  $as_fist_last = false) {
        assert('is_object($db)');

        if (($firstname == null) && ($lastname == null)) {
            $q = $db->select('author', array('author_id', 'name'), '',
                             "pdAuthorList::create",
                             array('ORDER BY' => 'name ASC'));
            if ($q === false) return false;
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
            if ($q === false) return false;
        }

        $list = array();
        $r = $db->fetchObject($q);
        while ($r) {
        	$name = utf8_encode($r->name);
        	
        	if ($as_fist_last) {
	            $names = split(',', $name);

	            if (count($names) == 2)
                	$name = trim($names[1]) . ' ' . trim($names[0]);
        	}
            
            $list[$r->author_id] = $name;
            $r = $db->fetchObject($q);
        }
        return $list;
    }
}

?>
