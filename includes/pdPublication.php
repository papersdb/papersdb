<?php ;

// $Id: pdPublication.php,v 1.61 2006/09/13 22:40:55 aicmltec Exp $

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
define('PD_PUB_DB_LOAD_AUTHOR_MIN',      8);
define('PD_PUB_DB_LOAD_AUTHOR_FULL',     0x10);
define('PD_PUB_DB_LOAD_POINTER',         0x20);
define('PD_PUB_DB_LOAD_VENUE',           0x40);
define('PD_PUB_DB_LOAD_ALL',             0x77);

define('NEW_VENUE', 1);

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
    var $pub_links;
    var $web_links;
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

        return true;
    }

    function dbLoadVenue(&$db) {
        assert("($this->dbLoadFlags & PD_PUB_DB_LOAD_VENUE)");

        if (($this->venue_id == null) || ($this->venue_id == '')
            || ($this->venue_id == '0')) return;

        $this->venue = new pdVenue();
        $this->venue->dbload($db, $this->venue_id);
    }

    function dbDelete(&$db) {
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

        $tables = array('pub_cat_info', 'pub_cat', 'pub_add', 'publication');
        foreach($tables as $table) {
            $db->delete($table, array('pub_id' => $this->pub_id),
                        'pdPublication::dbDelete');
        }
        $this->deleteFiles();
    }

    function dbSave(&$db) {
        assert('is_object($db)');

        $arr = array('title'      => $this->title,
                     'paper'      => $this->paper,
                     'abstract'   => $this->abstract,
                     'keywords'   => $this->keywords,
                     'published'  => $this->published,
                     'extra_info' => $this->extra_info,
                     'updated'    => date("Y-m-d"),
                     'venue_id'   => $this->venue->venue_id,
                     'submit'     => $this->submit);

        if (isset($this->pub_id)) {
            $db->update('publication', $arr, array('pub_id' => $this->pub_id),
                        'pdPublication::dbSave');
        }
        else {

            $db->insert('publication', $arr, 'pdPublication::dbSave');
            $this->pub_id = $db->insertId();
        }

        $db->delete('pointer', array('pub_id' => $this->pub_id),
                    'pdPublication::dbDelete');
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

    function dbAttUpdate(&$db, $filename, $type) {
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

    function dbAttRemove(&$db, $filename) {
        assert('$this->pub_id != null');
        assert('count($this->additional_info) > 0');

        foreach ($this->additional_info as $k => $o) {
            if (basename($o->location) == basename($filename)) {
                $dbfilename = $o->location;
                unset($pub->additional_info[$k]);
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
     * removes all extra_info items of length 0
     */
    function extraInfoGet() {
        if (!isset($this->extra_info)) return '';

        $extra_info = explode(";", $this->extra_info);

        foreach ($extra_info as $key => $value) {
            if ($value == "")
                unset($extra_info[$key]);
        }
        return implode(",", $extra_info);
    }

    /**
     * removes all keywords of length 0
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

    function keywordsSet($keywords) {
        assert('is_array($keywords)');

        if (count($keywords) == 0) return;

        $words = implode('; ', $keywords);
        $words = preg_replace("/;\s*;/", ';', $words);
        $this->keywords = $words;
    }

    function extraInfoSet($info) {
        assert('is_array($info)');

        if (count($info) == 0) return;

        $words = implode('; ', $info);
        $words = preg_replace("/;\s*;/", ';', $words);
        $this->extra_info = $words;
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

    function addCategory(&$db, $mixed) {
        if (is_object($mixed)) {
            $this->category = $mixed;
        }
        else if (is_numeric($mixed)) {
            if (($this->category != null)
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
        if ($this->authors != null) {
            foreach ($this->authors as $author) {
                if ($author->author_id == $mixed)
                    return;
            }
        }

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

    function paperDbUpdate(&$db, $paper) {
        $this->paper = $paper;
        $db->update('publication', array('paper' => $paper),
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

    function deleteFiles() {
        assert('$this->pub_id != null');

        $pub_path = FS_PATH_UPLOAD . $this->pub_id . '/';
        $filepath = $pub_path . basename($this->paper);

        if (file_exists($filepath))
            unlink($filepath);

        if (count($this->additional_info) > 0) {
            foreach ($this->additional_info as $att) {
                $filepath = $pub_path . basename($att->location);

                if (file_exists($filepath))
                    unlink($filepath);
            }
        }

        if (file_exists($pub_path))
            rmdir($pub_path);
    }

    function paperAttGetUrl() {
        if($this->paper == 'No paper') return '';

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
                    $citation .= ''
                        . '<a href="' . $urlPrefix . '/view_author.php?'
                        . 'author_id=' . $auth->author_id . '">';
                $citation .= $auth->firstname[0] . '. ' . $auth->lastname;

                if ($author_links)
                    $citation .= '</a>';
                $first = false;
            }
            $citation .= '. ';
        }

        // Title
        $citation .= '<span id="pub_title">&quot;' . $this->title
            . '&quot;</span>. ';

        // Additional Information - Outputs the category specific information
        // if it exists
        $info_arr = array();
        if (count($this->info) > 0) {
            foreach ($this->info as $i)
                if ($i != '')
                    $info_arr[] = $i;
            $info = implode(', ', $info_arr);
        }

        $pub_date = split('-', $this->published);

        //  Venue
        $v = '';
        if (is_object($this->venue)) {
            if (($this->venue->url != '') && ($this->venue->url != 'http://'))
                $v .= ' <a href="' . $this->venue->url . '" target="_blank">';

            if ($this->venue->name != '')
                $v .= $this->venue->name;
            else
                $v .= $this->venue->title;

            if ((($this->venue->url != '') && ($this->venue->url != 'http://'))
                || ((strpos($this->venue->name, '<a href=') >= 0)
                    && (strpos($this->venue->name, '</a>') === false))) {
                // some venue names don't close the <a href> tag
                $v .= '</a>';
            }

            if ($this->venue->type == 'Conference') {
                if (isset($this->venue->occurrence[$pub_date[0]])
                    && ($this->venue->occurrence[$pub_date[0]] != ''))
                    $v .= ', ' . $this->venue->occurrence[$pub_date[0]];
            }
            else if ($this->venue->data != '')
                $v .= ', ' . $this->venue->data;
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
        $bibtex = '';

        if ($this->category->category == 'In Conference') {
            $bibtex .= '@inconference{';
        }
        else if ($this->category->category == 'In Journal') {
            $bibtex .= '@article{';
        }
        else if ($this->category->category == 'In Book') {
            $bibtex .= '@book{';
        }
        else
            return false;

        $pub_date = split('-', $this->published);
        if ($this->venue->title != '')
            $venue_short = preg_replace("/['-]\d+/", '', $this->venue->title);
        else
            $venue_short = '';

        $venue_name = $this->venue->name;

        $auth_count = count($this->authors);
        if ($auth_count > 0) {
            $bibtex .= $this->authors[0]->lastname;
            if ($auth_count == 2)
                $bibtex .= '+' . $this->authors[1]->lastname;
            else if ($auth_count > 2)
                $bibtex .= '+al';

            if ($venue_short != '')
                $bibtex .= ':' . $venue_short;

            $bibtex .= substr($pub_date[0], 2) . ",\n" . '  author = {';

            $arr = array();
            foreach ($this->authors as $auth) {
                $arr[] = $auth->firstname . ' ' . $auth->lastname;
            }
            $bibtex .= implode(' and ', $arr) . "},\n";
        }

        $bibtex .= '  title = {' . $this->title . "},\n";

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

        if ($venue_name != '') {
            if ($this->category->category == 'In Conference') {
                $bibtex .= '  booktitle = {' . $venue_name . "},\n";
            }
            else if ($this->category->category == 'In Journal') {
                $bibtex .= '  journal = {' . $venue_name . "},\n";
            }
        }

        $bibtex .= '  year = ' . $pub_date[0] . ",\n";

        $bibtex .= '}';

        return format80($bibtex);
    }

    function paperFilenameGet() {
        if (($this->pub_id == '') || ($this->paper == 'No Paper')
            || ($this->paper == '')) return null;

        return FS_PATH_UPLOAD . $this->pub_id . '/' . basename($this->paper);
    }

    function paperSave($db, $papername) {
        assert('is_object($db)');
        assert('$this->pub_id != ""');

        if (!isset($papername) || (strpos($papername, 'paper_') !== false))
            return;

        $basename = 'paper_' . basename($papername, '.' . $user->login);

        if ($basename == basename($pub->paper))  return;

        $user =& $_SESSION['user'];

        $pub_path = FS_PATH_UPLOAD . $this->pub_id . '/';

        $basename = 'paper_' . basename($papername, '.' . $user->login);
        $filename = $pub_path . $basename;

        // create the publication's path if it does not exist
        if (!file_exists($pub_path)) {
            mkdir($pub_path, 0777);
            // mkdir permissions with 0777 does not seem to work
            chmod($pub_path, 0777);
        }

        if (rename($papername, $filename)) {
            chmod($filename, 0777);
            $this->paperDbUpdate($db, $basename);
        }
    }

    function attFilenameGet($num) {
        if ($this->pub_id == '') return null;

        assert('$num < count($this->additional_info)');

        return FS_PATH_UPLOAD . $this->pub_id . '/'
            . basename($this->additional_info[$num]->location);
    }

    function attSave($db, $att_name, $att_type) {
        assert('is_object($db)');
        assert('$this->pub_id != ""');

        if (($att_name == '') || ($att_type == '')
            || (strpos($att_name, 'additional_') !== false)) return;

        // make sure this attachment is not already in the list
        $basename = 'additional_' . basename($att_name, '.' . $user->login);

        if (count($this->additional_info) > 0)
            foreach ($this->additional_info as $att)
                if ($att_name == basename($att->location))
                    return;

        $user =& $_SESSION['user'];

        $pub_path = FS_PATH_UPLOAD . $this->pub_id . '/';

        $basename = 'additional_' . basename($att_name, '.' . $user->login);
        $filename = $pub_path . $basename;

        // create the publication's path if it does not exist
        if (!file_exists($pub_path)) {
            mkdir($pub_path, 0777);
            // mkdir permissions with 0777 does not seem to work
            chmod($pub_path, 0777);
        }

        if (rename($att_name, $filename)) {
            chmod($filename, 0777);
            $this->dbAttUpdate($db, $basename, $att_type);
        }
    }

    /**
     * Loads publication data from the object or array passed in
     */
    function load(&$mixed) {
        $members = array('pub_id', 'title', 'paper', 'abstract', 'keywords',
                         'published', 'venue_id', 'venue', 'extra_info',
                         'submit', 'updated', 'additional_info', 'category');

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

}

?>
