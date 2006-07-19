<?php ;

// $Id: pdPublication.php,v 1.26 2006/07/19 23:49:12 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of publication data to / from the database.
 *
 *
 */

require_once 'includes/pdAuthor.php';
require_once 'includes/pdCategory.php';
require_once 'includes/pdVenue.php';

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
    var $published;   // this is the published date
    var $venue;
    var $venue_id;
    var $authors;
    var $extra_info;
    var $submit;
    var $updated;
    var $info;
    var $category;
    var $intPointer;
    var $extPointer;
    var $dbLoadFlags;
    var $additional_info; // these are the additional attached files

    /**
     * Constructor.
     */
    function pdPublication($obj = NULL) {
        $this->paper = 'No Paper';

        if (isset($obj))
            $this->load($obj);
    }

    function makeNull() {
        $this->pub_id = null;
        $this->title = null;
        $this->paper = null;
        $this->abstract = null;
        $this->keywords = null;
        $this->published = null;
        $this->venue = null;
        $this->venue_id = null;
        $this->authors = null;
        $this->extra_info = null;
        $this->submit = null;
        $this->updated = null;
        $this->info = null;
        $this->category = null;
        $this->intPointer = null;
        $this->extPointer = null;
        $this->dbLoadFlags = null;
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
        if ($q === false) return false;
        $this->load($q);

        if ($flags & PD_PUB_DB_LOAD_CATEGORY) {
            $q = $db->selectRow('pub_cat', '*', array('pub_id' => $id),
                             "pdPublication::dbLoad");
            $this->category = new pdCategory();
            $this->category->dbLoad($db, $q->cat_id, null,
                                    PD_CATEGORY_DB_LOAD_BASIC);
        }

        // some categories are not defined
        if (($flags & PD_PUB_DB_LOAD_CATEGORY_INFO)
            && isset($this->category->cat_id)) {
            $this->category->dbLoadCategoryInfo($db);

            if ($this->category->info != null) {
                foreach ($this->category->info as $info_id => $name) {
                    $r = $db->selectRow('pub_cat_info', array('value'),
                                        array('pub_id' => $id,
                                              'cat_id' => quote_smart($this->category->cat_id),
                                              'info_id' => quote_smart($info_id)),
                                        "pdPublication::dbLoad");
                    if ($r !== false)
                        $this->info[$name] = $r->value;
                    else
                        $this->info[$name] = '';
                }
            }
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
                    $this->extPointer[$r->name] = $r->value;
                    $r = $db->fetchObject($q);
                }
            }
        }

        if ($flags & PD_PUB_DB_LOAD_VENUE) {
            $this->dbLoadVenue($db);
        }

        return true;
    }

    function dbLoadVenue(&$db) {
        assert("($this->dbLoadFlags & PD_PUB_DB_LOAD_VENUE)");

        if (($this->venue == null) || ($this->venue == '')) return;

        if (preg_match("/venue_id:<([0-9]+)>/", $this->venue, $venue_id) == 0)
            return;

        if ($venue_id[1] == "") return;

        $this->venue_id = $venue_id[1];
        $this->venue = new pdVenue();
        $this->venue->dbload($db, $this->venue_id);
    }

    function authorsToHtml($urlPrefix = null) {
        if (!isset($this->authors)) return null;

        if ($urlPrefix == null) $urlPrefix = '.';

        $authorsStr = '';
        foreach ($this->authors as $author) {
            $authorsStr .= '<a href="' . $urlPrefix
                . '/view_author.php?author_id='
                . $author->author_id . '" target="_self">'
                . $author->name . "</a><br/>";
        }
        return $authorsStr;
    }

    /**
     * remove all keywords of length 0
     */
    function keywordsGet() {
        if (!isset($this->keywords)) return '';

        $keywords = explode(";", $this->keywords);

        foreach ($keywords as $key => $value) {
            if ($value == "")
                unset($keywords[$key]);
        }
        return implode(",", $keywords);
    }

    function dbDelete(&$db) {
        assert('is_object($db)');
        assert('isset($this->pub_id)');

        $tables = array('pub_cat_info', 'pub_cat', 'pub_add', 'publication');
        foreach($tables as $table) {
            $db->delete($table, array('pub_id' => $this->pub_id),
                        'pdPublication::dbDelete');
        }
        $this->makeNull();
    }

    function dbSave(&$db) {
        assert('is_object($db)');

        $arr = array('title'      => $this->title,
                     'abstract'   => $this->abstract,
                     'keywords'   => $this->keywords,
                     'published'  => $this->published,
                     'extra_info' => $this->extra_info,
                     'updated'    => date("Y-m-d"));

        if (is_object($this->venue))
            $arr['venue'] = 'venue_id:<' . $this->venue->venue_id . '>';
        else
            $arr['venue'] = $this->venue;

        if (isset($this->pub_id)) {
            $db->update('publication', $arr, array('pub_id' => $this->pub_id),
                        'pdPublication::dbSave');
        }
        else {
            if ($this->paper == null)
                $arr['paper'] = 'No Paper';
            else
                $arr['paper'] = $this->paper;

            $arr['submit'] = $this->submit;
            $db->insert('publication', $arr, 'pdPublication::dbSave');
            $this->pub_id = $db->insertId();
        }

        $db->delete('pointer', array('pub_id' => $this->pub_id),
                    'pdPublication::dbDelete');
        $arr = array();
        if (count($this->extPointer) > 0)
            foreach ($this->extPointer as $text => $link) {
                if (strpos($link, 'http://') === false)
                    $link = 'http://' . $link;

                array_push($arr, array('pub_id' => $this->pub_id,
                                       'type'   => 'ext',
                                       'name'   => $text,
                                       'value'  => $link));
            }

        if (count($this->intPointer ) > 0)
            foreach ($this->intPointer as $value) {
                array_push($arr, array('pub_id' => $this->pub_id,
                                       'type'   => 'int',
                                       'name'   => '-',
                                       'value'  => $value));
            }

        $db->insert('pointer', $arr, 'pdPublication::dbSave');

        if (count($this->additional_info) > 0) {
            $db->delete('pub_add', array('pub_id' => $this->pub_id),
                        'pdPublication::dbSave');

            $arr = array();
            foreach ($this->additional_info as $info) {
                $r = $db->selectRow('additional_info', 'add_id',
                                    array('location' => $info->location,
                                          'type'     => $info->type),
                                    'pdPublication::dbSave');
                if ($r === false)
                    array_push($arr, array('location' => $info->location,
                                           'type'     => $info->type));
            }
            $db->insert('additional_info', $arr, 'pdPublication::dbSave');

            $arr = array();
            foreach ($this->additional_info as $info) {
                $r = $db->selectRow('additional_info', 'add_id',
                                    array('location' => $info->location,
                                          'type'     => $info->type),
                                    'pdPublication::dbSave');
                assert('$r !== false');
                array_push($arr, array('pub_id' => $this->pub_id,
                                       'add_id' => $r->add_id));
            }
            $db->insert('pub_add', $arr, 'pdPublication::dbSave');
        }

        $db->delete('pub_author', array('pub_id' => $this->pub_id),
                    'pdPublication::dbSave');
        $arr = array();
        $count = 0;
        foreach ($this->authors as $author) {
            array_push($arr, array('pub_id'    => $this->pub_id,
                                   'author_id' => $author->author_id,
                                   'rank'      => $count));
            $count++;
        }
        $db->insert('pub_author', $arr, 'pdPublication::dbSave');

        $db->delete('pub_cat', array('pub_id' => $this->pub_id),
                    'pdPublication::dbSave');

        if (is_object($this->category) && ($this->category->cat_id > 0)) {
            $db->insert('pub_cat', array('cat_id' => $this->category->cat_id,
                                         'pub_id' => $this->pub_id),
                        'pdPublication::dbSave');

            $db->delete('pub_cat_info', array('pub_id' => $this->pub_id),
                        'pdPublication::dbSave');
            if (($this->category->info != null) &&
                (count($this->category->info) > 0)) {
                $arr = array();
                foreach ($this->category->info as $info_id => $name) {
                    array_push($arr,
                               array('pub_id'  => $this->pub_id,
                                     'cat_id'  => $this->category->cat_id,
                                     'info_id' => $info_id,
                                     'value'   => $this->info[$name]));
                }
                $db->insert('pub_cat_info', $arr, 'pdPublication::dbSave');
            }
        }
    }

    /**
     * Loads publication data from the object or array passed in
     */
    function load(&$mixed) {
        $members = array('pub_id', 'title', 'paper', 'abstract', 'keywords',
                         'published', 'venue', 'extra_info', 'submit',
                         'updated', 'additional_info', 'category');

        if (is_object($mixed)) {
            foreach ($members as $member) {
                if (isset($mixed->$member))
                    $this->$member = $mixed->$member;
            }
        }
        else if (is_array($mixed)) {
            foreach ($members as $member) {
                if (isset($mixed[$member]))
                    $this->$member = $mixed[$member];
            }
        }
    }

    function addVenue(&$db, $mixed) {
        if (is_object($mixed)) {
            $this->venue = $mixed;
            $this->venue_id = $this->venue->venue_id;
            return;
        }

        if (is_numeric($mixed)) {
            if (($this->venue != null)
                && ($this->venue->venue_id == $mixed)) return;

            $this->venue = new pdVenue();
            $result = $this->venue->dbLoad($db, $mixed);
            assert('$result');
            $this->venue_id = $this->venue->venue_id;
            return;
        }

        if (!is_string($mixed))  return;
        $this->venue = $mixed;
    }

    function addCategory(&$db, $mixed) {
        if (is_object($mixed)) {
            $this->category = $mixed;
        }
        else if (is_string($mixed)) {
            if (($this->category != null)
                && ($this->category->cat_id == $mixed)) return;

            $this->category = new pdCategory();
            $result = $this->category->dbLoad($db, $mixed);
            assert('$result');
        }
        else
            return;

        if (is_array($this->category->info)) {
            foreach ($this->category->info as $info_id => $name) {
                $this->info[$name] = '';
            }
        }
    }

    function addAuthor(&$db, $mixed) {
        if (is_object($mixed)) {
            // check if publication already has this author
            if ($this->authors != null)
                foreach ($this->authors as $author) {
                    if ($author->author_id == $mixed->author_id)
                        return;
                }

            $this->authors[] = $mixed;
            return;
        }

        // check if publication already has this author
        if ($this->authors != null)
            foreach ($this->authors as $author) {
                if ($author->author_id == $mixed)
                    return;
            }

        $author = new pdAuthor();
        $result = $author->dbLoad($db, $mixed, PD_AUTHOR_DB_LOAD_BASIC);
        assert('$result');
        $this->authors[] = $author;
    }

    function addExtPointer(&$db, $name, $url) {
        $this->extPointer[$name] = $url;
    }

    function addIntPointer(&$db, $pub_id) {
        $this->intPointer[] = $pub_id;
    }

    function dbUpdatePaper(&$db, $paper) {
        $db->update('publication', array('paper' => $paper),
                    array('pub_id' => $this->pub_id),
                    'pdPublication::updatePaper');
    }

    function attachmentsUpdate(&$db, $filename) {
        assert('$this->pub_id != null');

        $pub->additional_info[] = $filename;

        // check if already in database
        $r = $db->selectRow('additional_info', 'add_id',
                            array('location' => $filename),
                            'pdPublication::dbSave');
        if ($r !== false) return;

        $db->insert('additional_info', array('location' => $filename),
                    'pdPublication::attachmentsUpdate');

        $add_id = $db->insertId();

        $db->insert('pub_add', array('pub_id' => $this->pub_id,
                                     'add_id' => $add_id),
                    'pdPublication::attachmentsUpdate');
    }

    function attachmentRemove($filename) {
        assert('$this->pub_id != null');

        foreach ($pub->additional_info as $k => $o) {
            if ($o->location == $filename)
                unset($pub->additional_info[$k]);
        }

        $r = $db->selectRow('additional_info', 'add_id',
                            array('location' => $filename),
                            'pdPublication::dbSave');
        if ($r === false) return;

        $db->delete('pub_add', array('add_id' => $r->add_id,
                                     'pub_id' => $this->pub_id),
                    'pdPublication::dbSave');

        $db->delete('additional_info', array('add_id' => $r->add_id),
                    'pdPublication::attachmentRemove');

    }

    function webLinkRemove($text, $link) {
        if (count($this->extPointer) == 0) return;

        unset($this->extPointer[$text]);
    }

    function pubLinkRemove($pub_id) {
        if (count($this->intPointer) == 0) return;

        foreach ($this->intPointer as $key => $obj) {
            if ($obj->value == $pub_id)
                unset($this->intPointer[$key]);
        }
    }
}

?>
