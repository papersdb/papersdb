<?php ;

// $Id: pdAuthor.php,v 1.1 2006/05/18 20:45:36 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of publication data to / from the database.
 *
 *
 */

define('PD_AUTHOR_DB_LOAD_BASIC',     0);
define('PD_AUTHOR_DB_LOAD_INTERESTS', 1);
define('PD_AUTHOR_DB_LOAD_PUBS_MIN',  2);
define('PD_AUTHOR_DB_LOAD_PUBS_ALL',  4);
define('PD_AUTHOR_DB_LOAD_ALL',       0x7);

require_once('pdPublication.php');

/**
 *
 * \brief Class for storage and retrieval of publications to / from the
 * database.
 */
class pdAuthor {
    var $author_id;
    var $title;
    var $webpage;
    var $name;
    var $email;
    var $organization;
    var $dbLoadFlags;
    var $publications;
    var $totalPublications;

    /**
     * Constructor.
     */
    function pdAuthor($obj = NULL) {
        if (!is_null($obj))
            $this->objLoad($obj);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad(&$db, $id, $flags = PD_AUTHOR_DB_LOAD_ALL) {
        assert('is_object($db)');

        $this->dbLoadFlags = $flags;

        $q = $db->selectRow('author', '*', array('author_id' => $id),
                            "pdAuthor::dbLoad");

        if ($q === false) return;
        $this->objLoad($q);

        if ($flags & PD_AUTHOR_DB_LOAD_INTERESTS)
            $this->interestsDbLoad($db);

        if ($flags & (PD_AUTHOR_DB_LOAD_PUBS_MIN
                      | PD_AUTHOR_DB_LOAD_PUBS_ALL))
            $this->publicationsDbLoad($db);

        //print_r($this);
    }

    function interestsDbLoad(&$db) {
        assert('is_object($db)');
        assert('isset($this->author_id)');

        $q = $db->select(array('interest', 'author_interest'),
                         'interest.interest',
                         array('interest.interest_id=author_interest.interest_id',
                               'author_interest.author_id' => $this->author_id),
                         "pdAuthor::interestsDbLoad");

        if ($q === false) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $this->interest[] = $r->interest;
            $r = $db->fetchObject($q);
        }
    }

    function publicationsDbLoad(&$db) {
        assert('is_object($db)');
        assert('isset($this->author_id)');
        assert('$this->dbLoadFlags & (PD_AUTHOR_DB_LOAD_PUBS_MIN | PD_AUTHOR_DB_LOAD_PUBS_ALL)');

        $q = $db->select(array('publication', 'pub_author'),
                         array('publication.pub_id', 'publication.title'),
                         array('publication.pub_id=pub_author.pub_id',
                               'pub_author.author_id' => $this->author_id),
                         "pdAuthor::publicationsDbLoad",
                         array( 'ORDER BY' => 'publication.title ASC'));
        if ($q === false) return;

        $this->totalPublications = $db->numRows($q);

        // if PD_AUTHOR_DB_LOAD_PUBS_MIN and the author has published
        // more than 6 papers, then load nothing
        $numToLoad = 0;
        if (($this->dbLoadFlags & PD_AUTHOR_DB_LOAD_PUBS_MIN)
            && ($this->totalPublications <= 6)) {
            $numToLoad = $this->totalPublications;
        }

        if ($this->dbLoadFlags & PD_AUTHOR_DB_LOAD_PUBS_ALL) {
            $numToLoad = $this->totalPublications;
        }

        if ($numToLoad > 0) {
            $r = $db->fetchObject($q);
            while ($r && ($numToLoad > 0)) {
                $this->publications[$r->pub_id] = new pdPublication();
                $this->publications[$r->pub_id]
                    ->dbLoad($db, $r->pub_id, PD_PUB_DB_LOAD_BASIC);
                $r = $db->fetchObject($q);
                $numToLoad--;
            }
        }
    }

    /**
     * Loads author data from the object passed in
     */
    function objLoad($obj) {
        assert('is_object($obj)');

        if (isset($obj->author_id))
            $this->author_id = $obj->author_id;
        if (isset($obj->title))
            $this->title = $obj->title;
        if (isset($obj->webpage))
            $this->webpage = $obj->webpage;
        if (isset($obj->name))
            $this->name = $obj->name;
        if (isset($obj->email))
            $this->email = $obj->email;
        if (isset($obj->organization))
            $this->organization = $obj->organization;
    }
}

?>
