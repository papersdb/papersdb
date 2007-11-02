<?php ;

// $Id: pdAuthorList.php,v 1.19 2007/11/02 16:36:29 loyola Exp $

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
    public $list;

    /**
     * Constructor.
     */
    public function __construct($db, $firstname = null, $lastname = null) {
        assert('is_object($db)');

        if (($firstname == null) && ($lastname == null)) {
            $q = $db->select('author', array('author_id', 'name'), '',
                             "pdAuthorList::dbLoad",
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
                             "pdAuthorList::pdAuthorList",
                             array('ORDER BY' => 'name ASC'));
            if ($q === false) return false;
        }

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->author_id] = $r->name;
            $r = $db->fetchObject($q);
        }
    }

    /**
     * Converts the list to firstname lastname list.
     */
    public function asFirstLast() {
        assert('count($this->list) > 0');
        $fl_list = array();
        foreach ($this->list as $auth_id => $name) {
            $names = split(',', $name);
            if (count($names) == 2)
                $fl_list[$auth_id] = trim($names[1]) . ' ' . trim($names[0]);
            else
                $fl_list[$auth_id] = $name;
        }
        return $fl_list;
    }
}

?>
