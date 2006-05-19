<?php ;

// $Id: pdPublication.php,v 1.6 2006/05/19 17:42:40 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of publication data to / from the database.
 *
 *
 */

define('PD_PUB_DB_LOAD_BASIC',           0);
define('PD_PUB_DB_LOAD_CATEGORY',        1);
define('PD_PUB_DB_LOAD_CATEGORY_INFO',   2);
define('PD_PUB_DB_LOAD_ADDITIONAL_INFO', 4);
define('PD_PUB_DB_LOAD_AUTHOR',          8);
define('PD_PUB_DB_LOAD_POINTER',         0x10);
define('PD_PUB_DB_LOAD_VENUE',           0x20);
define('PD_PUB_DB_LOAD_ALL',             0x3f);

/**
 *
 * \brief Class for storage and retrieval of publications to / from the
 * database.
 */
class pdPublication {
    var $pub_id;
    var $title;
    var $paper;
    var $abstract;
    var $keywords;
    var $published;
    var $venue;
    var $venue_info;
    var $authors;
    var $extra_info;
    var $submit;
    var $updated;
    var $info;
    var $additional_info;
    var $category;
    var $intPointer;
    var $extPointer;
    var $dbLoadFlags;

    /**
     * Constructor.
     */
    function pdPublication($obj = NULL) {
        $this->paper = 'No Paper';

        if (!is_null($obj))
            $this->objLoad($obj);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad(&$db, $id, $flags = PD_PUB_DB_LOAD_ALL) {
        assert('is_object($db)');

        $this->dbLoadFlags = $flags;

        $q = $db->selectRow('publication', '*', array('pub_id' => $id),
                            "pdPublication::dbLoad");
        $this->objLoad($q);

        if ($flags & PD_PUB_DB_LOAD_CATEGORY) {
            $q = $db->select(array('category', 'pub_cat'),
                             'category.category',
                             array('category.cat_id=pub_cat.cat_id',
                                   'pub_cat.pub_id' => $id),
                             "pdPublication::dbLoad");
            $this->objLoad($db->fetchObject($q));
        }


        if ($flags & PD_PUB_DB_LOAD_ADDITIONAL_INFO) {
            $q = $db->select(array('additional_info', 'pub_add'),
                             array('additional_info.location',
                                   'additional_info.type'),
                             array('additional_info.add_id=pub_add.add_id',
                                   'pub_add.pub_id' => $id),
                             "pdPublication::dbLoad");
            $r = $db->fetchObject($q);
            while ($r) {
                $this->additional_info[] = $r;
                $r = $db->fetchObject($q);
            }
        }

        if ($flags & PD_PUB_DB_LOAD_CATEGORY_INFO) {
            $q = $db->select(array('info', 'cat_info', 'pub_cat'),
                             array('info.info_id', 'info.name'),
                             array('info.info_id=cat_info.info_id',
                                   'cat_info.cat_id=pub_cat.cat_id',
                                   'pub_cat.pub_id' => $id),
                             "pdPublication::dbLoad");
            $r = $db->fetchObject($q);
            while ($r) {
                $this->info[] = $r;
                $r = $db->fetchObject($q);
            }

            if (is_array($this->info)) {
                foreach ($this->info as $key => $value) {
                    $q = $db->select(array('pub_cat_info', 'pub_cat'),
                                     'pub_cat_info.value',
                                     array('pub_cat.pub_id' => $id,
                                           'pub_cat.cat_id=pub_cat_info.cat_id',
                                           'pub_cat_info.pub_id' => $id,
                                           'pub_cat_info.info_id'
                                           => $value->info_id),
                                     "pdPublication::dbLoad");
                    $r = $db->fetchObject($q);
                    while ($r) {
                        $this->info[$key]->value = $r->value;
                        $r = $db->fetchObject($q);
                    }
                }
            }
        }

        if ($flags & PD_PUB_DB_LOAD_AUTHOR) {
            $q = $db->select(array('author', 'pub_author'),
                             array('author.author_id', 'author.name'),
                             array('author.author_id=pub_author.author_id',
                                   'pub_author.pub_id' => $id),
                             "pdPublication::dbLoad",
                             array( 'ORDER BY' => 'pub_author.rank'));
            $r = $db->fetchObject($q);
            while ($r) {
                $this->authors[] = $r;
                $r = $db->fetchObject($q);
            }
        }

        if ($flags & PD_PUB_DB_LOAD_POINTER) {
            $q = $db->select('pointer', 'value',
                             array('pub_id' => $id, 'type' => 'int'),
                             "pdPublication::dbLoad");
            $r = $db->fetchObject($q);
            while ($r) {
                $this->intPointer[] = $r;
                $r = $db->fetchObject($q);
            }

            $q = $db->select('pointer', array('name', 'value'),
                             array('pub_id' => $id, 'type' => 'ext'),
                             "pdPublication::dbLoad");
            if ($q) {
                $r = $db->fetchObject($q);
                while ($r) {
                    $this->extPointer[] = $r;
                    $r = $db->fetchObject($q);
                }
            }
        }

        if ($flags & PD_PUB_DB_LOAD_VENUE) {
            $this->dbLoadVenue($db);
        }

        //print_r($this);
    }

    function dbLoadVenue(&$db) {
        assert("($this->dbLoadFlags & PD_PUB_DB_LOAD_VENUE)");

        if ($this->venue == '') return;

        if (preg_match("/venue_id:<([0-9]+)>/", $this->venue, $venue_id) == 0)
            return;

        if ($venue_id[1] == "") return;

        $q = $db->selectRow('venue', '*', array('venue_id' => $venue_id[1]),
                         "pdPublication::dbLoadVenue");
        if ($q === false) return;
        $this->venue_info = $q;
        $this->venue = '';
    }

    /**
     * Loads publication data from the object passed in
     */
    function objLoad($obj) {
        if ($obj == NULL) return;

        if (isset($obj->pub_id))
            $this->pub_id = $obj->pub_id;
        if (isset($obj->title))
            $this->title = $obj->title;
        if (isset($obj->paper))
            $this->paper = $obj->paper;
        if (isset($obj->abstract))
            $this->abstract = $obj->abstract;
        if (isset($obj->keywords))
            $this->keywords = $obj->keywords;
        if (isset($obj->published))
            $this->published = $obj->published;
        if (isset($obj->venue))
            $this->venue = $obj->venue;
        if (isset($obj->extra_info))
            $this->extra_info = $obj->extra_info;
        if (isset($obj->submit))
            $this->submit = $obj->submit;
        if (isset($obj->updated))
            $this->updated = $obj->updated;
        if (isset($obj->additional_info))
            $this->additional_info = $obj->additional_info;
        if (isset($obj->category))
            $this->category = $obj->category;
    }
}

?>
