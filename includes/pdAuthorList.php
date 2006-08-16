<?php ;

// $Id: pdAuthorList.php,v 1.9 2006/08/16 17:47:32 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of publication authors to / from the database.
 */

/**
 * \brief Class for storage and retrieval of publication authors to / from the
 * database.
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
            $q = $db->select('author', '*', array('name LIKE ' .
                                                  $db->addQuotes($name)),
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
