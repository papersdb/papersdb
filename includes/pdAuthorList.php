<?php ;

// $Id: pdAuthorList.php,v 1.12 2006/09/25 19:59:09 aicmltec Exp $

/**
 * Implements a class that retrieves from the database all the authors with a
 * common last name and first initial.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * Class that retrieves from the database all the authors with a common last
 * name and first initial.
 *
 * @package PapersDB
 */
class pdAuthorList {
    var $list;

    /**
     * Constructor.
     */
    function pdAuthorList(&$db, $firstname = null, $lastname = null) {
        assert('is_object($db)');

        if (($firstname != null) && ($lastname != null)) {
            $name = '%' . $lastname . ', ' . $firstname[0] . '%';
            $q = $db->select('author', '*',
                             array('name LIKE ' . $db->addQuotes($name)),
                             "pdAuthorList::pdAuthorList");
            if ($q === false) return false;
        }
        else {
            $q = $db->select('author', array('author_id', 'name'), '',
                             "pdAuthorList::dbLoad",
                             array('ORDER BY' => 'name ASC'));
            if ($q === false) return false;
        }

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->author_id] = $r->name;
            $r = $db->fetchObject($q);
        }
    }
}

?>
