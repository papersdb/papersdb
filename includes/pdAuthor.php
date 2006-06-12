<?php ;

// $Id: pdAuthor.php,v 1.8 2006/06/12 19:12:05 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of publication data to / from the database.
 *
 *
 */

require_once 'pdPubList.php';

define('PD_AUTHOR_DB_LOAD_BASIC',     0);
define('PD_AUTHOR_DB_LOAD_INTERESTS', 1);
define('PD_AUTHOR_DB_LOAD_PUBS_MIN',  2);
define('PD_AUTHOR_DB_LOAD_PUBS_ALL',  4);
define('PD_AUTHOR_DB_LOAD_ALL',       0x7);


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
    var $interests;
    var $dbLoadFlags;
    var $pub_list;
    var $totalPublications;

    /**
     * Constructor.
     */
    function pdAuthor($obj = NULL) {
        if (!is_null($obj))
            $this->load($obj);
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
        $this->load($q);

        if ($flags & PD_AUTHOR_DB_LOAD_INTERESTS)
            $this->interestsDbLoad($db);

        if ($flags & (PD_AUTHOR_DB_LOAD_PUBS_MIN
                      | PD_AUTHOR_DB_LOAD_PUBS_ALL)) {
            $this->publicationsDbLoad($db);
        }
    }

    /**
     *
     */
    function interestsDbLoad(&$db) {
        assert('is_object($db)');
        assert('isset($this->author_id)');

        $q = $db->select(array('interest', 'author_interest'),
                         'interest.interest',
                         array('interest.interest_id=author_interest.interest_id',
                               'author_interest.author_id' => $this->author_id),
                         "pdAuthor::interestsDbLoad");

        $r = $db->fetchObject($q);
        while ($r) {
            $this->interests[] = $r->interest;
            $r = $db->fetchObject($q);
        }
    }

    /**
     *
     */
    function publicationsDbLoad(&$db) {
        assert('is_object($db)');
        assert('isset($this->author_id)');
        assert('$this->dbLoadFlags & (PD_AUTHOR_DB_LOAD_PUBS_MIN | PD_AUTHOR_DB_LOAD_PUBS_ALL)');

        $this->totalPublications
            = pdPubList::authorNumPublications($db, $this->author_id);

        // if PD_AUTHOR_DB_LOAD_PUBS_MIN flag is set and the author has
        // published more than 6 papers, then load nothing
        $numToLoad = 0;
        if (($this->dbLoadFlags & PD_AUTHOR_DB_LOAD_PUBS_MIN)
            && ($this->totalPublications <= 6)) {
            $numToLoad = $this->totalPublications;
        }

        if ($this->dbLoadFlags & PD_AUTHOR_DB_LOAD_PUBS_ALL) {
            $numToLoad = $this->totalPublications;
        }

        if ($numToLoad > 0) {
            $this->pub_list = new pdPubList($db, $this->author_id, $numToLoad);
        }
    }

    /**
     * Adds or modifies an author in the database.
     */
    function dbSave(&$db) {
        assert('is_object($db)');

	    // add http:// to webpage address if needed
	    if(strpos($webpage, 'http') === false) {
		    $this->webpage = "http://" . $this->webpage;
        }

        if (isset($this->author_id)) {
            $db->update('author', array('name' => $this->name,
                                        'title' => $this->title,
                                        'email' => $this->email,
                                        'organization' => $this->organization,
                                        'webpage' => $this->webpage),
                        array('author_id' => $this->author_id),
                        'pdAuthor::dbSave');
            $this->dbSaveInterests($db);
            return;
        }

        $db->insert('author', array('name' => $this->name,
                                    'title' => $this->title,
                                    'email' => $this->email,
                                    'organization' => $this->organization,
                                    'webpage' => $this->webpage),
                    'pdAuthor::dbSave');
        $this->dbSaveInterests($db);
    }

    function dbSaveInterests(&$db) {
        $db->delete('author_interest',
                    array('author_id' => $this->author_id),
                    'pdUser::dbSave');

        if (isset($this->interests) && (count($this->interests) > 0)) {
            // first add the interests
            $arr = array();
            foreach ($this->interest as $i) {
                array_push($arr, array('interest_id' => 'NULL',
                                       'interest' => $i));
            }
            $db->insert('interest', $arr, 'pdAuthor::dbSave');

            // link the interest to this author
            $arr = array();
            foreach ($this->interest as $i) {
                $db->select('interest', 'interest_id',
                            'interest=' . $i, 'pdAuthor::dbSave');

                $r = $db->fetchObject($q);
                while ($r) {
                    array_push($arr,
                               array('author_id' => $this->author_id,
                                     'interest_id' => $r->interest_id));
                    $r = $db->fetchObject($q);
                }
            }
            $db->insert('author_interest', $arr, 'pdAuthor::dbSave');
        }
    }

    /**
     * Deletes an author from the database.
     */
    function dbDelete(&$db) {
        assert('is_object($db)');
        assert('isset($this->author_id)');

        $db->delete('author', array('author_id' => $this->author_id),
                    'pdAuthor::dbDelete');
        $db->delete('author_interest', array('author_id' => $this->author_id),
                    'pdAuthor::dbDelete');
        $db->delete('pub_author', array('author_id' => $this->author_id),
                    'pdAuthor::dbDelete');
    }

    /**
     * Loads author data from the object passed in
     */
    function load($obj) {
        assert('is_object($obj)');

        if (isset($obj->author_id))
            $this->author_id = $obj->author_id;
        if (isset($obj->title))
            $this->title = $obj->title;
        if (isset($obj->webpage))
            $this->webpage = $obj->webpage;
        if (isset($obj->email))
            $this->email = $obj->email;
        if (isset($obj->organization))
            $this->organization = $obj->organization;

        if (isset($obj->name)) {
            $this->name = $obj->name;
            $this->firstname
                = trim(substr($this->name, 1 + strpos($this->name, ',')));
            $this->lastname
                = substr($this->name, 0, strpos($this->name, ','));
        }
    }
}

?>
