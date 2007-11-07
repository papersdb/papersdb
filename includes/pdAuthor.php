<?php ;

// $Id: pdAuthor.php,v 1.30 2007/11/07 22:47:46 loyola Exp $

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

/**
 * Class that accesses author information in the database.
 *
 * @package PapersDB
 */
class pdAuthor extends pdDbAccessor{
    public $author_id;
    public $title;
    public $webpage;
    public $name;
    public $firstname;
    public $lastname;
    public $email;
    public $organization;
    public $interests;
    public $dbLoadFlags;
    public $pub_list;
    public $totalPublications;

	const DB_LOAD_BASIC     = 0;
	const DB_LOAD_MIN       = 1;
	const DB_LOAD_INTERESTS = 2;
	const DB_LOAD_PUBS_MIN  = 4;
	const DB_LOAD_PUBS_ALL  = 8;
	const DB_LOAD_ALL       = 0xF;

    /**
     * Constructor.
     */
    public function __construct($mixed = NULL) {
        parent::__construct($mixed);
    }

    public function makeNull() {
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
    public function dbLoad($db, $id, $flags = self::DB_LOAD_ALL) {
        assert('is_object($db)');

        $this->dbLoadFlags = $flags;

        if ($flags == self::DB_LOAD_MIN)
            $fields = array('author_id', 'name');
        else
            $fields = '*';

        $q = $db->selectRow('author', $fields, array('author_id' => $id),
                            "pdAuthor::dbLoad");
        if ($q === false) return false;
        $this->load($q);

        if ($flags & self::DB_LOAD_INTERESTS)
            $this->dbLoadInterests($db);

        if ($flags & (self::DB_LOAD_PUBS_MIN
                      | self::DB_LOAD_PUBS_ALL)) {
            $this->publicationsDbLoad($db);
        }

        return true;
    }

    /**
     *
     */
    public function dbLoadInterests($db) {
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
    public function publicationsDbLoad($db) {
        assert('is_object($db)');
        assert('isset($this->author_id)');
        assert('$this->dbLoadFlags & (self::DB_LOAD_PUBS_MIN | self::DB_LOAD_PUBS_ALL)');

        $this->totalPublications
            = pdPubList::authorNumPublications($db, $this->author_id);

        // if self::DB_LOAD_PUBS_MIN flag is set and the author has
        // published more than 6 papers, then load nothing
        $numToLoad = 0;
        if (($this->dbLoadFlags & self::DB_LOAD_PUBS_MIN)
            && ($this->totalPublications <= 6)) {
            $numToLoad = $this->totalPublications;
        }

        if ($this->dbLoadFlags & self::DB_LOAD_PUBS_ALL) {
            $numToLoad = $this->totalPublications;
        }

        if ($numToLoad > 0) {
            $this->pub_list = pdPubList::create(
            	$db, array('author_id' => $this->author_id,
                'num_to_load' => $numToLoad));
        }
    }

    /**
     * Adds or modifies an author in the database.
     */
    public function dbSave($db) {
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

        $this->author_id = $db->insertId();
        $this->dbSaveInterests($db);

        if (isset($this->name))
            $this->nameSet($this->name);
    }

    public function dbSaveInterests($db) {
        if (isset($this->author_id)) {
            $db->delete('author_interest',
                        array('author_id' => $this->author_id),
                        'pdAuthor::dbSaveInterests');
        }

        if (count($this->interests) > 0) {
            $db_interests = pdAuthInterests::createList($db);

            // only add interests not in the database
            $arr = array();
            foreach ($this->interests as $k => $i) {
                if (empty($i)) {
                    unset($this->interests[$k]);
                    continue;
                }

                if (!in_array($i, $db_interests)) {
                    array_push($arr, array('interest_id' => 'NULL',
                                           'interest' => $i));
                }
            }

            if (count($arr) > 0) {
                $db->insert('interest', $arr, 'pdAuthor::dbSave');
            }
        }

        // link the interest to this author
        $arr = array();
        foreach ($this->interests as $key => $i) {
            $q = $db->selectRow('interest', 'interest_id',
                                array('interest' => $i),
                                'pdAuthor::dbSaveInterests');
            assert('($q !== false)');
            array_push($arr, array('author_id' => $this->author_id,
                                   'interest_id' => $q->interest_id));

            $this->interests[$q->interest_id] = $i;
            unset($this->interests[$key]);
        }

        if (count($arr) > 0)
            $db->insert('author_interest', $arr, 'pdAuthor::dbSaveInterests');
    }

    /**
     * Deletes an author from the database.
     */
    public function dbDelete($db) {
        assert('is_object($db)');
        assert('isset($this->author_id)');

        $db->delete('author_interest', array('author_id' => $this->author_id),
                    'pdAuthor::dbDelete');
        $db->delete('pub_author', array('author_id' => $this->author_id),
                    'pdAuthor::dbDelete');
        $db->delete('author', array('author_id' => $this->author_id),
                    'pdAuthor::dbDelete');

        // check if any authors are using these interests, if not they can be
        // deleted from the database
        foreach ($this->interests as $id => $name) {
            $q = $db->selectRow('author_interest',
                                'count(author_id) as acount',
                                array('interest_id' => $id),
                                'pdAuthor::dbDelete');
            if ($q->acount == 0)
                $db->delete('interest', array('interest_id' => $id),
                            'pdAuthor::dbDelete');
        }

        $this->makeNull();
    }

    public function asArray() {
        return get_object_vars($this);
    }

    /**
     * Loads author data from the object passed in
     */
    public function load($mixed) {
        parent::load($mixed);

        if (isset($this->name))
            $this->nameSet($this->name);
    }

    /**
     * used when name is in "firstname lastname" format.
     */
    public function nameSet($name) {
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

    /*
     * Parameter $mixed can be an array or a string value.
     */
    public function addInterest($mixed) {
    	if (is_array($mixed))
    		foreach ($mixed as $interest)
    			$this->interests[] = $interest;
    	else if (is_string($mixed))
    		$this->interests[] = $mixed;
    	else
    		assert('false');  // invalid type for $mixed
    }
}

?>
