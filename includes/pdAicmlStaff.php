<?php

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
class pdAicmlStaff extends pdDbAccessor{
    public $staff_id;
    public $author_id;
    public $pos_id;
    public $start_date;
    public $end_date;
    public $pub_ids;
    public $publications;

    const DB_LOAD_BASIC     = 0;
    const DB_LOAD_MIN       = 1;
    const DB_LOAD_PUBS_MIN  = 2;
    const DB_LOAD_PUBS_ALL  = 4;
    const DB_LOAD_ALL       = 0x7;

    /**
     * Constructor.
     */
    public function __construct($mixed = null) {
        parent::__construct($mixed);
    }

    public function makeNull() {
        $this->author_id = null;
        $this->author_id = null;
        $this->pos_id = null;
        $this->start_date = null;
        $this->end_date = null;
        $this->pub_ids = null;
        $this->publications = null;
    }

    public static function &newFromDb(&$db, $staff_id, $flags = self::DB_LOAD_ALL) {
        assert('is_numeric($staff_id)');
        $author = new pdAicmlStaff();
        $author->dbLoad($db, $staff_id, $flags);
        return $author;
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    public function dbLoad(&$db, $id, $flags = self::DB_LOAD_ALL) {
        assert('is_object($db)');

        $this->dbLoadFlags = $flags;

        $q = $db->selectRow('aicml_staff', '*', array('staff_id' => $id),
                            "pdAicmlStaff::dbLoad");
        if ($q === false) return false;
        $this->load($q);

        if ($flags & (self::DB_LOAD_PUBS_MIN
        | self::DB_LOAD_PUBS_ALL)) {
            $this->publicationsDbLoad($db);
        }

        return true;
    }

    /**
     *
     */
    public function publicationsDbLoad(&$db) {
        assert('is_object($db)');
        assert('isset($this->staff_id)');
        assert('$this->dbLoadFlags & (self::DB_LOAD_PUBS_MIN | self::DB_LOAD_PUBS_ALL)');

        if ($this->dbLoadFlags & self::DB_LOAD_PUBS_MIN) {
            if (!empty($this->end_date)) {
                $between = '\'' . $this->start_date . '\' AND \'' . $this->end_date . '\'';

                $q = $db->select(array('publication', 'pub_author'), 
                    'publication.pub_id',
                    array('pub_author.author_id' => $this->author_id, 
                        'publication.pub_id=pub_author.pub_id',
                        "publication.published BETWEEN $between"),
                    "pdPubList::datePubsDBLoad",
                    array('ORDER BY' => 'published DESC'));
            }
            else {
                $q = $db->select(array('publication', 'pub_author'), 
                    'publication.pub_id',
                    array('pub_author.author_id' => $this->author_id, 
                        'publication.pub_id=pub_author.pub_id',
                        "publication.published >= '$this->start_date'"),
                    "pdPubList::datePubsDBLoad",
                    array('ORDER BY' => 'published DESC'));
                
            }

            if (!$q) return false;
            $this->pub_ids = array();
            foreach ($q as $r) {
                $this->pub_ids[] = $r->pub_id;
            }

            if (($this->dbLoadFlags & self::DB_LOAD_PUBS_ALL) == 0) {
                // exit if user does not want to load the actual publications
                return;
            }
        }

        $this->publications = pdPubList::create($this->db,
        array('pub_ids' => $this->pub_ids, 'sort' => true));
    }

    /**
     * Adds or modifies an author in the database.
     */
    public function dbSave(&$db) {
        assert('is_object($db)');

        if (isset($this->staff_id)) {
            $db->update('aicml_staff', array('staff_id' => $this->staff_id,
                'author_id' => $this->author_id,
                'pos_id' => $this->pos_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date),
            array('staff_id' => $this->staff_id), 'pdAicmlStaff::dbSave');
            return;
        }

        foreach(array('staff_id', 'author_id', 'pos_id', 'start_date', 'end_date')
        as $item) {
            if ($this->$item != NULL)
            $settings[$item] = $this->$item;
        }

        $q = $db->insert('author', $settings, 'pdAicmlStaff::dbSave');
        assert('$q');

        $this->author_id = $db->insertId();
        $this->dbSaveInterests($db);

        if (isset($this->name))
        $this->nameSet($this->name);
    }

    /**
     * Deletes an author from the database.
     */
    public function dbDelete(&$db) {
        assert('is_object($db)');
        assert('isset($this->staff_id)');

        $db->delete('aicml_staff', array('staff_id' => $this->staff_id),
                    'pdAicmlStaff::dbDelete');

        $this->makeNull();
    }

    public function asArray() {
        return get_object_vars($this);
    }
}

?>
