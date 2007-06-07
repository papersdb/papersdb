<?php ;

// $Id: pdAuthor.php,v 1.25 2007/06/07 17:36:52 aicmltec Exp $

/**
 * Storage and retrieval of author data to / from the database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/** Requries classes to access the database. */
require_once 'includes/pdDbAccessor.php';
require_once 'includes/pdPubList.php';
require_once 'includes/pdAuthInterests.php';

define('PD_AUTHOR_DB_LOAD_BASIC',     0);
define('PD_AUTHOR_DB_LOAD_MIN',       1);
define('PD_AUTHOR_DB_LOAD_INTERESTS', 2);
define('PD_AUTHOR_DB_LOAD_PUBS_MIN',  4);
define('PD_AUTHOR_DB_LOAD_PUBS_ALL',  8);
define('PD_AUTHOR_DB_LOAD_ALL',       0xF);


/**
 * Class that accesses author information in the database.
 *
 * @package PapersDB
 */
class pdAuthor extends pdDbAccessor{
    var $author_id;
    var $title;
    var $webpage;
    var $name;
    var $firstname;
    var $lastname;
    var $email;
    var $organization;
    var $interests;
    var $dbLoadFlags;
    var $pub_list;
    var $totalPublications;

    /**
     * Constructor.
     */
    function pdAuthor($mixed = NULL) {
        parent::pdDbAccessor($mixed);
    }

    function makeNull() {
        $this->author_id = null;
        $this->title = null;
        $this->webpage = null;
        $this->name = null;
        $this->email = null;
        $this->organization = null;
        $this->interests = null;
        $this->dbLoadFlags = null;
        $this->pub_list = null;
        $this->totalPublications = null;
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad($db, $id, $flags = PD_AUTHOR_DB_LOAD_ALL) {
        assert('is_object($db)');

        $this->dbLoadFlags = $flags;

        if ($flags == PD_AUTHOR_DB_LOAD_MIN)
            $fields = array('author_id', 'name');
        else
            $fields = '*';

        $q = $db->selectRow('author', $fields, array('author_id' => $id),
                            "pdAuthor::dbLoad");
        if ($q === false) return false;
        $this->load($q);

        if ($flags & PD_AUTHOR_DB_LOAD_INTERESTS)
            $this->dbLoadInterests($db);

        if ($flags & (PD_AUTHOR_DB_LOAD_PUBS_MIN
                      | PD_AUTHOR_DB_LOAD_PUBS_ALL)) {
            $this->publicationsDbLoad($db);
        }

        return true;
    }

    /**
     *
     */
    function dbLoadInterests($db) {
        assert('is_object($db)');
        assert('isset($this->author_id)');

        $q = $db->select(array('interest', 'author_interest'),
                         array('interest.interest', 'interest.interest_id'),
                         array('interest.interest_id=author_interest.interest_id',
                               'author_interest.author_id' => $this->author_id),
                         "pdAuthor::dbLoadInterests");

        // its possible that the author has no interests in the database
        // no need to assert
        $r = $db->fetchObject($q);
        while ($r) {
            $this->interests[$r->interest_id] = $r->interest;
            $r = $db->fetchObject($q);
        }
    }

    /**
     *
     */
    function publicationsDbLoad($db) {
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
            $this->pub_list
                = new pdPubList($db, array('author_id' => $this->author_id,
                                           'num_to_load' => $numToLoad));
        }
    }

    /**
     * Adds or modifies an author in the database.
     */
    function dbSave($db) {
        assert('is_object($db)');

        // add http:// to webpage address if needed
        if(strpos($this->webpage, 'http') === false) {
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

        foreach(array('name', 'title', 'email', 'organization', 'webpage')
                as $item) {
            if ($this->$item != NULL)
                $settings[$item] = $this->$item;
        }

        $q = $db->insert('author', $settings, 'pdAuthor::dbSave');
        assert('$q');

        $q = $db->selectRow('author', 'author_id', $settings,
                            "pdAuthor::dbSave");
        assert('($q !== false)');
        $this->load($q);

        $this->dbSaveInterests($db);
    }

    function dbSaveInterests($db) {
        if (isset($this->author_id)) {
            $db->delete('author_interest',
                        array('author_id' => $this->author_id),
                        'pdUser::dbSave');
        }

        if (count($this->interests) > 0) {
            $db_interests = new pdAuthInterests($db);

            // first add the interests
            $arr = array();
            foreach ($this->interests as $k => $i) {
                if (empty($i)) {
                    unset($this->interests[$k]);
                    continue;
                }

                if (!$db_interests->interestExists($i)) {
                    array_push($arr, array('interest_id' => 'NULL',
                                           'interest' => $i));
                }
            }

            if (count($arr) > 0) {
                $db->insert('interest', $arr, 'pdAuthor::dbSave');

                // link the interest to this author
                $arr = array();
                foreach ($this->interests as $i) {
                    $q = $db->selectRow('interest', 'interest_id',
                                        array('interest' => $i),
                                        'pdAuthor::dbSaveInterests');
                    assert('($q !== false)');
                    array_push($arr,
                               array('author_id' => $this->author_id,
                                     'interest_id' => $q->interest_id));
                }

                if (count($arr) > 0)
                    $db->insert('author_interest', $arr, 'pdAuthor::dbSave');
            }
        }
    }

    /**
     * Deletes an author from the database.
     */
    function dbDelete($db) {
        assert('is_object($db)');
        assert('isset($this->author_id)');

        $db->delete('author_interest', array('author_id' => $this->author_id),
                    'pdAuthor::dbDelete');
        $db->delete('pub_author', array('author_id' => $this->author_id),
                    'pdAuthor::dbDelete');
        $db->delete('author', array('author_id' => $this->author_id),
                    'pdAuthor::dbDelete');
        $this->makeNull();
    }

    function asArray() {
        return get_object_vars($this);
    }

    /**
     * Loads author data from the object passed in
     */
    function load($mixed) {
        if (is_object($mixed)) {
            foreach (array_keys(get_class_vars('pdAuthor')) as $member) {
                if (isset($mixed->$member))
                    $this->$member = $mixed->$member;
            }

            if (isset($mixed->name)) {
                $this->nameSet($this->name);
            }
        }
        else if (is_array($mixed)) {
            foreach (array_keys(get_class_vars('psAuthor')) as $member) {
                if (isset($mixed[$member]))
                    $this->$member = $mixed[$member];
            }

            if (isset($mixed['name'])) {
                $this->firstname
                    = trim(substr($this->name, 1 + strpos($this->name, ',')));
                $this->lastname
                    = substr($this->name, 0, strpos($this->name, ','));
            }

            if (isset($mixed->name)) {
                $this->nameSet($this->name);
            }
        }
    }

    /**
     * used when name is in "firstname lastname" format.
     */
    function nameSet($name) {
        $commaPos = strrpos($name, ',');
        $spacePos = strrpos($name, ',');

        if (($commaPos === false) && ($spacePos === false)) {
            $this->name = $name;
            $this->lastname = $name;
            return;
        }

        if ($commaPos !== false)
            $this->name = $name;
        else if ($spacePos !== false) {
            // put last name first
            $this->name = substr($name, $pos + 1) . ', '
                . substr($name, 0, $pos);
        }

        $this->firstname
            = trim(substr($this->name, 1 + strpos($this->name, ',')));
        $this->lastname
            = substr($this->name, 0, strpos($this->name, ','));
    }
}

?>
