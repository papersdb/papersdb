<?php ;

// $Id: pdPublication.php,v 1.109 2007/04/30 17:09:40 aicmltec Exp $

/**
 * Implements a class that accesses, from the database, some or all the
 * information related to a publication.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/** Requires author, category and venue classes. */
require_once 'includes/pdDbAccessor.php';
require_once 'includes/pdAuthor.php';
require_once 'includes/pdCategory.php';
require_once 'includes/pdVenue.php';
require_once 'includes/pdPubList.php';

define('PD_PUB_DB_LOAD_BASIC',           0);
define('PD_PUB_DB_LOAD_CATEGORY',        1);
define('PD_PUB_DB_LOAD_CATEGORY_INFO',   2);
define('PD_PUB_DB_LOAD_ADDITIONAL_INFO', 4);
define('PD_PUB_DB_LOAD_AUTHOR_MIN',      8);
define('PD_PUB_DB_LOAD_AUTHOR_FULL',     0x10);
define('PD_PUB_DB_LOAD_POINTER',         0x20);
define('PD_PUB_DB_LOAD_VENUE',           0x40);
define('PD_PUB_DB_LOAD_ALL',             0x77);

define('NEW_VENUE', 1);

/**
 * Class that accesses, from the database, some or all the information related
 * to a publication.
 *
 * @package PapersDB
 */
class pdPublication extends pdDbAccessor {
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
    var $pub_links;
    var $web_links;
    var $dbLoadFlags;
    var $additional_info; // these are the additional attached files
    var $user;
    var $rank_id;
    var $ranking;
    var $collaborations;

    function pdPublication($mixed = NULL) {
        $this->paper = 'no paper';

        parent::pdDbAccessor($mixed);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use $flags to load information from other tables.
     */
    function dbLoad($db, $id, $flags = PD_PUB_DB_LOAD_ALL) {
        assert('is_object($db)');

        $this->dbLoadFlags = $flags;

        $q = $db->selectRow('publication', '*', array('pub_id' => $id),
                            "pdPublication::dbLoad");
        if ($q === false) return false;
        $this->load($q);

        $q = $db->select('pub_col', 'col_id', array('pub_id' => $this->pub_id),
                         "pdPublication::dbLoad",
                         array('ORDER BY' => 'col_id ASC'));
        if ($q !== false) {
            $r = $db->fetchObject($q);
            while ($r) {
                $this->collaborations[] = $r->col_id;
                $r = $db->fetchObject($q);
            }
        }

        if ($flags & PD_PUB_DB_LOAD_CATEGORY) {
            $q = $db->selectRow('pub_cat', 'cat_id', array('pub_id' => $id),
                                "pdPublication::dbLoad");

            if ($q !== false) {
                $this->category = new pdCategory();
                $this->category->dbLoad($db, $q->cat_id, null,
                                        PD_CATEGORY_DB_LOAD_BASIC);
            }
        }

        // some categories are not defined
        if (($flags & PD_PUB_DB_LOAD_CATEGORY_INFO)
            && isset($this->category->cat_id)) {
            $this->category->dbLoadCategoryInfo($db);

            if ($this->category->info != null) {
                foreach ($this->category->info as $info_id => $name) {
                    $r = $db->selectRow(
                        'pub_cat_info', 'value',
                        array('pub_id' => $id,
                              'cat_id' => quote_smart($this->category->cat_id),
                              'info_id' => quote_smart($info_id)),
                        "pdPublication::dbLoad");

                    if (($r !== false) && ($r->value != ''))
                        $this->info[$name] = $r->value;
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

        if ($flags & (PD_PUB_DB_LOAD_AUTHOR_MIN
                      | PD_PUB_DB_LOAD_AUTHOR_FULL)) {
            $q = $db->select(array('author', 'pub_author'),
                             array('author.author_id', 'author.name'),
                             array('author.author_id=pub_author.author_id',
                                   'pub_author.pub_id' => $id),
                             "pdPublication::dbLoad",
                             array( 'ORDER BY' => 'pub_author.rank'));
            $r = $db->fetchObject($q);
            while ($r) {
                if ($flags & PD_PUB_DB_LOAD_AUTHOR_FULL) {
                    $auth_count = count($this->authors);
                    $this->authors[$auth_count] = new pdAuthor();
                    $this->authors[$auth_count]->dbLoad($db, $r->author_id,
                                                        PD_AUTHOR_DB_LOAD_BASIC);
                }
                else
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
                $this->pub_links[] = $r->value;
                $r = $db->fetchObject($q);
            }

            $q = $db->select('pointer', array('name', 'value'),
                             array('pub_id' => $id, 'type' => 'ext'),
                             "pdPublication::dbLoad");
            if ($q) {
                $r = $db->fetchObject($q);
                while ($r) {
                    $this->web_links[$r->name] = $r->value;
                    $r = $db->fetchObject($q);
                }
            }
        }

        if ($flags & PD_PUB_DB_LOAD_VENUE) {
            $this->dbLoadVenue($db);
        }

        if (isset($this->rank_id)) {
            if ($this->rank_id > 0) {
                $q = $db->selectRow('pub_rankings', 'description',
                                    array('rank_id' => $this->rank_id),
                                    "pdPublication::dbLoad");
                if ($q !== false)
                    $this->ranking = $q->description;
            }
            else if ($this->rank_id == -1) {
                $q = $db->selectRow('pub_rankings', 'description',
                                    array('pub_id'  => $this->pub_id),
                                    "pdPublication::dbLoad");
                if ($q !== false) {
                    $this->rank_id = $q->rank_id;
                    $this->ranking = $q->description;
                }
            }
            else if (is_object($this->venue)) {
                // get ranking from venue information
                $this->rank_id = $this->venue->rank_id;
                $this->ranking = $this->venue->ranking;
            }
        }

        return true;
    }

    function dbLoadVenue($db) {
        assert("($this->dbLoadFlags & PD_PUB_DB_LOAD_VENUE)");

        if (($this->venue_id == null) || ($this->venue_id == '')
            || ($this->venue_id == '0')) return;

        $this->venue = new pdVenue();
        $this->venue->dbload($db, $this->venue_id);
    }

    function dbDelete($db) {
        assert('is_object($db)');
        assert('isset($this->pub_id)');

        if (count($this->additional_info) > 0) {
            $arr = array();
            foreach ($this->additional_info as $info) {
                $r = $db->delete('additional_info',
                                 array('location' => $info->location),
                                 'pdPublication::dbDelete');
            }
        }

        if (is_object($this->venue) && ($this->venue->v_usage == 'single')) {
            $this->venue->dbDelete($db);
        }

        $tables = array('pub_cat_info', 'pub_cat', 'pub_add', 'publication',
                        'rankings');
        foreach($tables as $table) {
            $db->delete($table, array('pub_id' => $this->pub_id),
                        'pdPublication::dbDelete');
        }
        $this->deleteFiles($db);
    }

    function dbSave($db) {
        assert('is_object($db)');

        $arr = array('title'      => $this->title,
                     'paper'      => $this->paper,
                     'abstract'   => $this->abstract,
                     'user'       => $this->user,
                     'keywords'   => $this->keywords,
                     'published'  => $this->published,
                     'extra_info' => $this->extra_info,
                     'updated'    => date("Y-m-d"),
                     'submit'     => $this->submit);

        if (isset($this->rank_id))
            $arr['rank_id'] = $this->rank_id;

        if (!isset($this->venue))
            $arr['venue_id'] = null;
        else if (is_object($this->venue))
            $arr['venue_id'] = $this->venue->venue_id;

        if (isset($this->pub_id)) {
            $db->update('publication', $arr, array('pub_id' => $this->pub_id),
                        'pdPublication::dbSave');
        }
        else {
            $db->insert('publication', $arr, 'pdPublication::dbSave');
            $this->pub_id = $db->insertId();
        }

        // rank_id
        $db->delete('pub_rankings', array('pub_id' => $this->pub_id),
                    'pdPublication::dbSave');

        if ($this->rank_id == -1) {
            $db->insert('pub_rankings',
                        array('pub_id' => $this->pub_id,
                              'description' => $this->ranking),
                        'pdPublication::dbSave');
            $this->rank_id = $db->insertId();

            $db->update('publication',
                        array('rank_id' => $this->rank_id),
                        array('pub_id' => $this->pub_id),
                        'pdPublication::dbSave');
        }

        // collaborations
        if (is_array($this->collaborations)
            && (count($this->collaborations) > 0)) {
            $db->delete('pub_col', array('pub_id' => $this->pub_id),
                        'pdPublication::dbSave');
            $values = array();
            foreach ($this->collaborations as $col_id) {
                $values[] = array('pub_id' => $this->pub_id,
                                  'col_id' => $col_id);
            }
            $db->insert('pub_col', $values, 'pdPublication::dbSave');
        }

        $db->delete('pointer', array('pub_id' => $this->pub_id),
                    'pdPublication::dbSave');
        $arr = array();
        if (count($this->web_links) > 0)
            foreach ($this->web_links as $text => $link) {
                if (strpos($link, 'http://') === false)
                    $link = 'http://' . $link;

                array_push($arr, array('pub_id' => $this->pub_id,
                                       'type'   => 'ext',
                                       'name'   => $text,
                                       'value'  => $link));
            }

        if (count($this->pub_links ) > 0)
            foreach ($this->pub_links as $pub_id) {
                array_push($arr, array('pub_id' => $this->pub_id,
                                       'type'   => 'int',
                                       'name'   => '-',
                                       'value'  => $pub_id));
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

        if (count($this->authors) > 0) {
            $arr = array();
            $count = 0;
            foreach ($this->authors as $author) {
                array_push($arr, array('pub_id'    => $this->pub_id,
                                       'author_id' => $author->author_id,
                                       'rank'      => $count));
                $count++;
            }
            $db->insert('pub_author', $arr, 'pdPublication::dbSave');
        }

        $db->delete('pub_cat', array('pub_id' => $this->pub_id),
                    'pdPublication::dbSave');

        if (is_object($this->category) && ($this->category->cat_id > 0)) {
            $db->insert('pub_cat', array('cat_id' => $this->category->cat_id,
                                         'pub_id' => $this->pub_id),
                        'pdPublication::dbSave');

            $db->delete('pub_cat_info', array('pub_id' => $this->pub_id),
                        'pdPublication::dbSave');
            if (isset($this->category->info)
                && (count($this->category->info) > 0)) {
                $arr = array();
                foreach ($this->category->info as $info_id => $name) {
                    if (isset($this->info[$name])
                        && ($this->info[$name] != ''))
                        array_push($arr,
                                   array('pub_id'  => $this->pub_id,
                                         'cat_id'  => $this->category->cat_id,
                                         'info_id' => $info_id,
                                         'value'   => $this->info[$name]));
                }
                if (count($arr) > 0)
                    $db->insert('pub_cat_info', $arr, 'pdPublication::dbSave');
            }
        }
    }

    function dbAttUpdate($db, $filename, $type) {
        assert('$this->pub_id != null');

        $filename = $this->pub_id . '/' . $filename;

        $pub->additional_info[] = arr2obj(array('location' => $filename,
                                                'type'     => $type));

        // check if already in database
        $r = $db->selectRow('additional_info', 'add_id',
                            array('location' => $filename),
                            'pdPublication::dbAttUpdate');
        if ($r !== false) return;

        $db->insert('additional_info',
                    array('location' => $filename,
                          'type'     => $type),
                    'pdPublication::dbAttUpdate');

        $add_id = $db->insertId();

        $db->insert('pub_add', array('pub_id' => $this->pub_id,
                                     'add_id' => $add_id),
                    'pdPublication::dbAttUpdate');
    }

    function dbAttRemove($db, $filename) {
        assert('$this->pub_id != null');
        assert('count($this->additional_info) > 0');

        foreach ($this->additional_info as $k => $o) {
            if (basename($o->location) == basename($filename)) {
                $dbfilename = $o->location;
                unset($this->additional_info[$k]);
            }
        }

        $r = $db->selectRow('additional_info', 'add_id',
                            array('location' => $dbfilename),
                            'pdPublication::dbAttRemove');
        if ($r === false) return;

        $db->delete('pub_add', array('add_id' => $r->add_id,
                                     'pub_id' => $this->pub_id),
                    'pdPublication::dbSave');

        $db->delete('additional_info', array('add_id' => $r->add_id),
                    'pdPublication::dbAttRemove');

    }

    function authorsToHtml($urlPrefix = null) {
        if (!isset($this->authors)) return null;

        if ($urlPrefix == null) $urlPrefix = '.';

        $authorsStr = '<ul>';
        foreach ($this->authors as $author) {
            $authorsStr .= '<li><a href="' . $urlPrefix
                . '/view_author.php?author_id='
                . $author->author_id . '" target="_self">'
                . $author->firstname . ' ' . $author->lastname . '</a>';

            if ($author->organization != '')
                $authorsStr .= ', ' . $author->organization;

            $authorsStr .= '</li>';
        }
        $authorsStr .= '</ul>';
        return $authorsStr;
    }

    /**
     * removes all keywords of length 0
     */
    function keywordsGet() {
        if (!isset($this->keywords)) return '';

        $keywords = preg_split("/;\s*/", $this->keywords);

        foreach ($keywords as $key => $value) {
            if ($value == "")
                unset($keywords[$key]);
        }
        return implode(", ", $keywords);
    }

    function keywordsSet($keywords) {
        assert('is_array($keywords)');

        if (count($keywords) == 0) return;

        $words = implode('; ', $keywords);
        $words = preg_replace("/;\s*;/", ';', $words);
        $this->keywords = $words;
    }

    /**
     * removes all extra_info items of length 0
     */
    function extraInfoGet() {
        if (!isset($this->extra_info)) return '';

        $extra_info = preg_split('/;\s*/', $this->extra_info);

        foreach ($extra_info as $key => $value) {
            if ($value == "")
                unset($extra_info[$key]);
        }
        return implode(", ", $extra_info);
    }

    function extraInfoSet($info) {
        assert('is_array($info)');

        if (count($info) == 0) {
            unset($this->extra_info);
            return;
        }

        $words = implode(';', $info);
        $words = preg_replace("/;\s*;/", ';', $words);
        $words = preg_replace("/;\s*/", ';', $words);
        $this->extra_info = $words;
    }

    function addVenue($db, $mixed) {
        if (is_object($mixed)) {

            // if this publication already has a unique venue associated with
            // it, the venue must first be deleted
            if (is_object($this->venue)
                && ($this->venue_id != $mixed->venue_id)
                && ($this->venue->v_usage == 'single')) {
                $this->venue->dbDelete($db);
            }

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

            $this->category = new pdCategory();
            if ($this->venue->type == 'Conference') {
                $result = $this->category->dbLoad($db, null, 'In Conference');
                assert('$result');
            }
            else if ($this->venue->type == 'Workshop') {
                $result = $this->category->dbLoad($db, null, 'In Workshop');
                assert('$result');
            }
            else if ($this->venue->type == 'Journal') {
                $result = $this->category->dbLoad($db, null, 'In Journal');
                assert('$result');
            }
            return;
        }

        // should never get here since venues must now always be objects or
        // venue_ids
        assert('false');
    }

    function addCategory($db, $mixed) {
        if (is_object($mixed)) {
            $this->category = $mixed;
        }
        else if (is_numeric($mixed)) {
            if (is_object($this->category)
                && ($this->category->cat_id == $mixed)) return;

            $this->category = new pdCategory();
            $result = $this->category->dbLoad($db, $mixed);
            assert('$result');
        }
        else {
            // should never happen
            assert('false');
        }

        if (is_array($this->category->info)) {
            foreach ($this->category->info as $info_id => $name) {
                $this->info[$name] = '';
            }
        }
    }

    function clearAuthors() {
        if (count($this->authors) == 0) return;
        unset($this->authors);
    }

    function addAuthor($db, $mixed) {
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
        else if (is_array($mixed)) {
            // replaces all authors
            unset($this->authors);

            // assigns each author
            foreach ($mixed as $index => $author_id) {
                $author = new pdAuthor();
                $result = $author->dbLoad($db, $author_id,
                                          PD_AUTHOR_DB_LOAD_BASIC);
                assert('$result');
                $this->authors[$index] = $author;
            }
            return;
        }

        // check if publication already has this author
        if (count($this->authors) > 0) {
            foreach ($this->authors as $author) {
                assert('$author->author_id != $mixed');
            }
        }

        assert('is_numeric($mixed)');

        $author = new pdAuthor();
        $result = $author->dbLoad($db, $mixed, PD_AUTHOR_DB_LOAD_BASIC);
        assert('$result');
        $this->authors[] = $author;
    }

    function addWebLink($name, $url) {
        $this->web_links[$name] = $url;
    }

    function delWebLink($name) {
        if (isset($this->web_links[$name]))
            unset($this->web_links[$name]);
    }

    function addPubLink($pub_id) {
        $this->pub_links[] = $pub_id;
    }

    function paperDbUpdate($db, $paper) {
        $this->paper = $paper;
        $db->update('publication', array('paper' => $this->paper),
                    array('pub_id' => $this->pub_id),
                    'pdPublication::updatePaper');
    }

    function webLinkRemove($text, $link) {
        if (count($this->web_links) == 0) return;

        unset($this->web_links[$text]);
    }

    function pubLinkRemove($pub_id) {
        if (count($this->pub_links) == 0) return;

        foreach ($this->pub_links as $key => $link_pub_id) {
            if ($link_pub_id == $pub_id)
                unset($this->pub_links[$key]);
        }

        // reindex
        $this->pub_links = array_values($this->pub_links);
    }

    function paperExists() {
        $path = FS_PATH;
        if (strpos($this->paper, 'uploaded_files/') === false)
            $path .= '/uploaded_files/' . $this->pub_id . '/';
        $path .= $this->paper;

        return is_file($path);
    }

    function attExists($att) {
        $path = FS_PATH;
        if (strpos($att->location, 'uploaded_files/') === false)
            $path .= '/uploaded_files/';
        $path .= $att->location;

        return is_file($path);
    }

    function paperSave($db, $papername) {
        assert('is_object($db)');
        assert('isset($this->pub_id)');

        # 'No Paper' was used in a previous version of the software
        if (!isset($papername)
            || (strpos(strtolower($papername), 'no paper') !== false))
            return;

        $user =& $_SESSION['user'];

        $basename = basename($papername, '.' . $user->login);

        if ($basename == basename($this->paper))  return;

        $pub_path = FS_PATH_UPLOAD . $this->pub_id . '/';

        $filename = $pub_path . $basename;

        // create the publication's path if it does not exist
        if (!is_dir($pub_path)) {
            mkdir($pub_path, 0777);
            // mkdir permissions with 0777 does not seem to work
            chmod($pub_path, 0777);
        }

        if (rename($papername, $filename)) {
            chmod($filename, 0777);
            $this->paperDbUpdate($db, $basename);
        }
    }

    function attSave($db, $att_name, $att_type) {
        assert('is_object($db)');
        assert('$this->pub_id != ""');

        if (($att_name == '') || ($att_type == '')) return;

        $user =& $_SESSION['user'];

        if (count($this->additional_info) > 0)
            foreach ($this->additional_info as $att) {
                if (basename($att_name) == basename($att->location))
                    return;
            }

        // make sure this attachment is not already in the list
        $basename = basename($att_name, '.' . $user->login);

        $pub_path = FS_PATH_UPLOAD . $this->pub_id . '/';

        $basename = basename($att_name, '.' . $user->login);
        $filename = $pub_path . $basename;

        // create the publication's path if it does not exist
        if (!is_dir($pub_path)) {
            mkdir($pub_path, 0777);
            // mkdir permissions with 0777 does not seem to work
            chmod($pub_path, 0777);
        }

        if (rename($att_name, $filename)) {
            chmod($filename, 0777);
            $this->dbAttUpdate($db, $basename, $att_type);
        }
    }

    function deletePaper($db) {
        assert('isset($this->pub_id)');

        if (!isset($this->paper)) return;

        $pub_path = FS_PATH_UPLOAD . $this->pub_id . '/';
        $filepath = $pub_path . basename($this->paper);

        if (is_file($filepath))
            unlink($filepath);

        $this->paper = 'no paper';
        $this->paperDbUpdate($db, 'no paper');
    }

    function deleteAtt($db, $att) {
        assert('isset($this->pub_id)');

        $pub_path = FS_PATH_UPLOAD . $this->pub_id . '/';
        $filepath = $pub_path . basename($att->location);

        if (is_file($filepath))
            unlink($filepath);
        $this->dbAttRemove($db, $att->location);
    }

    function deleteFiles($db) {
        $this->deletePaper($db);

        if (count($this->additional_info) > 0) {
            foreach ($this->additional_info as $att) {
                $this->deleteAtt($db, $att);
            }
        }

        $pub_path = FS_PATH_UPLOAD . $this->pub_id;

        rm($pub_path);
    }

    function attFilenameGet($num) {
        if ($this->pub_id == '') return null;

        assert('$num < count($this->additional_info)');

        return FS_PATH_UPLOAD . $this->pub_id . '/'
            . basename($this->additional_info[$num]->location);
    }

    function paperAttGetUrl() {
        if(strtolower($this->paper) == 'no paper') return '';

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $result = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        if (strpos($this->paper, 'uploaded_files/') === false)
            $result .= '/uploaded_files/' . $this->pub_id . '/';
        $result .= $this->paper;

        return $result;
    }

    function attachmentGetUrl($att_num) {
        if($att_num >= count($this->additional_info)) return '';

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $result = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $att = $this->additional_info[$att_num];

        if (strpos($att->location, 'uploaded_files/') === false)
            $result .= '/uploaded_files/';
        $result .= $att->location;

        return $result;
    }

    function getCitationHtml($urlPrefix = '.', $author_links = true) {
        $citation = '';

        $first = true;
        if (count($this->authors) > 0) {
            foreach ($this->authors as $auth) {
                if (!$first)
                    $citation .= ', ';

                if ($author_links)
                    $citation .= '<a href="' . $urlPrefix . '/view_author.php?'
                        . 'author_id=' . $auth->author_id . '">';
                $citation .= $auth->firstname[0] . '. ' . $auth->lastname;

                if ($author_links)
                    $citation .= '</a>';
                $first = false;
            }
            $citation .= '. ';
        }

        // Title
        $citation .= '<span class="pub_title">&quot;' . $this->title
            . '&quot;</span>. ';

        // Additional Information - Outputs the category specific information
        // if it exists
        $info = $this->getInfoForCitation();

        if (strpos($this->published, '-') !== false)
            $pub_date = split('-', $this->published);

        //  Venue
        $v = '';
        if (is_object($this->venue)) {
            if (isset($pub_date))
                $url = $this->venue->urlGet($pub_date[0]);
            else
                $url = $this->venue->urlGet();

            if ($url != '') {
                $v .= ' <a href="' .  $url . '" target="_blank">';
            }

            $vname = $this->venue->nameGet();

            if ($vname != '')
                $v .= $vname;
            else
                $v .= $this->venue->title;

            if ($url != '') {
                $v .= '</a>';
            }

            if ($this->venue->data != '') {
                if ($this->venue->type == 'Workshop')
                    $v .= ' (within ' . $this->venue->data. ')';
            }

            if (isset($pub_date))
                $location = $this->venue->locationGet($pub_date[0]);
            else
                $location = $this->venue->locationGet();

            if ($location != '')
                $v .= ', ' . $location;
        }

        if (($v == '') && is_object($this->category)
            && ($this->category->category != 'Book')
            && ($this->category->category != 'In Book')) {
            $v = $this->category->category;
        }

        $date_str = '';

        if (isset($pub_date)) {
            if ($pub_date[1] != 0)
                $date_str .= date('F', mktime (0, 0, 0, $pub_date[1])) . ' ';
            if ($pub_date[0] != 0)
                $date_str .= $pub_date[0];
        }

        if (($v != '') && ($info != '') && ($date_str != ''))
            $citation .= $v . ', ' . $info . ', ' . $date_str . '.';
        else if (($v != '') && ($info == '') && ($date_str != ''))
            $citation .= $v . ', ' . $date_str . '.';
        else if (($v != '') && ($info == '') && ($date_str == ''))
            $citation .= $v . '.';
        else if (($v == '') && ($info != '') && ($date_str != ''))
            $citation .= $info . ', ' . $date_str . '.';
        else if (($v == '') && ($info == '') && ($date_str != ''))
            $citation .= $date_str . '.';

        return $citation;
    }

    function getCitationText() {
        $citation = '';

        if (count($this->authors) > 0) {
            foreach ($this->authors as $auth) {
                $auth_text[] = $auth->firstname[0] . '. ' . $auth->lastname;
            }

            if (count($auth_text) > 0)
                $citation .= implode(', ', $auth_text) . '. ';
        }

        // Title
        $citation .= $this->title . '. ';

        // Additional Information - Outputs the category specific information
        // if it exists
        $info = $this->getInfoForCitation();

        $pub_date = split('-', $this->published);

        //  Venue
        $v = '';
        if (is_object($this->venue)) {
            $vname = $this->venue->nameGet();
            if ($vname != '')
                $v .= $vname;
            else
                $v .= $this->venue->title;

            $location = $this->venue->locationGet($pub_date[0]);
            if ($location != '')
                $v .= ', ' . $location;
        }

        if (($v == '') && is_object($this->category)
            && ($this->category->category != 'Book')
            && ($this->category->category != 'In Book')) {
            $v = $this->category->category;
        }

        $date_str = '';
        if ($pub_date[1] != 0)
            $date_str .= date('F', mktime (0, 0, 0, $pub_date[1])) . ' ';
        if ($pub_date[0] != 0)
            $date_str .= $pub_date[0];

        if (($v != '') && ($info != '') && ($date_str != ''))
            $citation .= $v . ', ' . $info . ', ' . $date_str . '.';
        if (($v != '') && ($info == '') && ($date_str != ''))
            $citation .= $v . ', ' . $date_str . '.';
        if (($v != '') && ($info == '') && ($date_str == ''))
            $citation .= $v . '.';
        if (($v == '') && ($info == '') && ($date_str != ''))
            $citation .= $date_str . '.';

        return $citation;
    }

    function getBibtex() {
        $bibtex = '@inproceedings{';

        if (is_object($this->category) && isset($this->category->category)) {
            if ($this->category->category == 'In Conference') {
                $bibtex = '@inconference{';
            }
            else if ($this->category->category == 'In Journal') {
                $bibtex = '@article{';
            }
            else if (($this->category->category == 'In Book')
                     || ($this->category->category == 'Book')) {
                $bibtex = '@book{';
            }
        }

        $pub_date = split('-', $this->published);
        $venue_short = '';
        if (is_object($this->venue)) {
            if (isset($this->venue->title))
                $venue_short = preg_replace("/['-]\d+/", '',
                                            $this->venue->title);

            $venue_name = $this->venue->nameGet();

            if ($this->venue->data != '') {
                if ($this->venue->type == 'Workshop')
                    $venue_name .= ' (within ' . $this->venue->data. ')';
            }
        }

        if (isset($this->authors)) {
            $auth_count = count($this->authors);
            if ($auth_count > 0) {
                $bibtex .= $this->authors[0]->lastname;
                if ($auth_count == 2)
                    $bibtex .= '+' . $this->authors[1]->lastname;
                else if ($auth_count > 2)
                    $bibtex .= '+al';

                if (isset($venue_short))
                    $bibtex .= ':' . $venue_short;

                $bibtex .= substr($pub_date[0], 2) . ",\n" . '  author = {';

                $arr = array();
                foreach ($this->authors as $auth) {
                    $arr[] = $auth->firstname . ' ' . $auth->lastname;
                }
                $bibtex .= implode(' and ', $arr) . "},\n";
            }
        }
        else
            $bibtex .= $this->pub_id . ",\n";

        $bibtex .= '  title = {' . $this->title . "},\n";

        // show info
        if (count($this->info) > 0) {
            foreach ($this->info as $key => $value) {
                if ($value != '') {
                    $bibtex .= '  ' . $key . ' = ';

                    if (strpos($value, ' '))
                        $bibtex .= '{' . $value . "},\n";
                    else
                        $bibtex .= $value . ",\n";
                }
            }
        }

        if (isset($venue_name)) {
            if (is_object($this->category)) {
                if (($this->category->category == 'In Conference')
                    || ($this->category->category == 'In Workshop')) {
                    $bibtex .= '  booktitle = {' . $venue_name . "},\n";
                }
                else if ($this->category->category == 'In Journal') {
                    $bibtex .= '  journal = {' . $venue_name . "},\n";
                }
            }
            else {
                $bibtex .= '  booktitle = {' . $venue_name . "},\n";
            }
        }

        $bibtex .= '  year = ' . $pub_date[0] . ",\n";

        $bibtex .= '}';

        return format80($bibtex);
    }

    function getInfoForCitation() {
        if (count($this->info) == 0) return null;

        $info = array();

        if (!isset($this->category)) {
            return $this->info2str(array_keys($this->info), $this->info);
        }

        switch ($this->category->category) {
            case 'In Conference':
                $validKeys = array('Editor', 'Pages');
                break;

            case 'In Journal':
                $validKeys = array('Editor', 'Volume', 'Number', 'Pages');
                break;

            case 'In Workshop':
                $validKeys = array('Edition', 'Publisher', 'Editor', 'Volume',
                                   'Number', 'Pages');
                break;

            case 'In Book':
                $validKeys = array('Book Title', 'Edition', 'Publisher',
                                   'Editor', 'Volume', 'Number', 'Pages');
                break;

            case 'Book':
                $validKeys = array('Edition', 'Publisher', 'Editor', 'Volume');
                break;

            case 'Video':
                $validKeys = array('Edition', 'Publisher', 'Editor', 'Volume',
                                   'Number');
                break;

            case 'Technical Report':
                $validKeys = array('Institution', 'Number');
                break;

            case 'MSc Thesis':
                $validKeys = array('School', 'Type');
                break;

            case 'PhD Thesis':
                $validKeys = array('Type');
                break;

            case 'Application':
                $validKeys = array('Pages');
                break;

            default:
                // use whatever has been defined for this category
                $validKeys = array_keys($this->info);
                break;
        }

        return $this->info2str($validKeys, $this->info);
    }

    function info2str($validKeys, $values) {
        $info = array();

        // need to merge 'Volume' and 'Number' if they exist
        if (in_array('Volume', $validKeys) && in_array('Number', $validKeys)
            && isset($values['Volume']) && ($values['Volume'] != '')
            && isset($values['Number']) && ($values['Number'] != '')) {
            $values['Volume'] = $values['Volume']
                . '(' . $values['Number'] . ')';

            // now remove 'Number' from $validKeys
            $validKeys = array_diff($validKeys, array('Number'));
        }

        foreach ($validKeys as $key) {
            if (isset($values[$key]) && ($values[$key] != '')) {
                if ($key == 'Edition')
                    $info[] = '(Edition ' . $values[$key] . ')';
                else if ($key == 'Editor')
                    $info[] = '(ed: ' . $values[$key] . ')';
                else if ($key == 'Number')
                    $info[] = '(' . $values[$key] . ')';
                else if ($key == 'Pages')
                    $info[] = 'pp ' . $values[$key];
                else
                    $info[] = $values[$key];
            }
        }
        return implode(', ', $info);
    }

    // Previous versions of the code used 'No Paper' and '<path>/paper_' to
    // state that there was no attachment.
    function paperFilenameGet() {
        $basename = basename($this->paper);
        if (($this->pub_id == '') || (strtolower($this->paper) == 'no paper')
            || ($this->paper == '') || ($basename == 'paper_')) return null;

        return FS_PATH_UPLOAD . $this->pub_id . '/' . $basename;
    }

    function duplicateTitleCheck($db) {
        assert('is_object($db)');

        $myTitleLower = preg_replace('/\s\s+/', ' ', strtolower($this->title));
        $all_pubs = new pdPubList($db);
        $similarPubs = array();

        foreach ($all_pubs->list as $pub) {
            $pubTitleLower
                = preg_replace('/\s\s+/', ' ', strtolower($pub->title));

            if (isset($this->pub_id) && ($this->pub_id == $pub->pub_id))
                continue;

            if ($myTitleLower == $pubTitleLower) {
                $similarPubs[] = $pub->pub_id;
            }
        }
        return $similarPubs;
    }

    function pubsTitleSort($a , $b) {
        if (strtolower($a->title) == strtolower($b->title)) return 0;

        return (strtolower($a->title) < strtolower($b->title)) ? -1 : 1;
    }

    function pubsDateSortDesc($a , $b) {
        if (strtolower($a->published) == strtolower($b->published)) {
            if (strtolower($a->title) == strtolower($b->title)) return 0;

            return (strtolower($a->title) < strtolower($b->title)) ? -1 : 1;
        }

        return (strtolower($a->published) > strtolower($b->published))
            ? -1 : 1;
    }

    function rankingsGlobalGet(&$db) {
        $q = $db->select('pub_rankings', '*', 'pub_id is NULL',
                         "pdPublication::dbLoad");
        assert('$q !== false');

        $r = $db->fetchObject($q);
        while ($r) {
            $rankings[$r->rank_id] = $r->description;
            $r = $db->fetchObject($q);
        }

        return $rankings;
    }

    function collaborationsGet(&$db) {
        $q = $db->select('collaboration', '*', '', "pdPublication::dbLoad");
        assert('$q !== false');

        $r = $db->fetchObject($q);
        while ($r) {
            $collaborations[$r->col_id] = $r->description;
            $r = $db->fetchObject($q);
        }

        return $collaborations;
    }
}

?>
