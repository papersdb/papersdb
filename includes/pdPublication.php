<?php

/**
 * $Id: pdPublication.php,v 1.135 2008/02/11 22:20:58 loyola Exp $
 *
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

/**
 * Accesses from the database some or all the information related to a
 * publication.
 *
 * @package PapersDB
 */
class pdPublication extends pdDbAccessor {
   /** publication's id in the database */
   public $pub_id;
   public $title;
   public $paper;
   public $abstract;
   public $keywords;
   public $published;   // this is the published date
   public $venue;
   public $venue_id;
   public $authors;
   public $extra_info;
   public $submit;
   public $updated;
   public $info;
   public $category;
   public $related_pubs;
   private $web_links;
   public $dbLoadFlags;
   public $additional_info; // these are the additional attached files
   public $user;
   public $rank_id;
   public $ranking;
   public $collaborations;

   const DB_LOAD_BASIC           = 0;
   const DB_LOAD_CATEGORY        = 1;
   const DB_LOAD_CATEGORY_INFO   = 2;
   const DB_LOAD_ADDITIONAL_INFO = 4;
   const DB_LOAD_AUTHOR_MIN      = 8;
   const DB_LOAD_AUTHOR_FULL     = 0x10;
   const DB_LOAD_POINTER         = 0x20;
   const DB_LOAD_VENUE           = 0x40;
   const DB_LOAD_ALL             = 0x77;

   const MIN_YEAR = 1970;
   const MAX_YEAR = 2030;

   private static $db_table_fields = array(
      'pub_id', 'title', 'paper', 'abstract', 'keywords', 'published',
      'venue_id', 'extra_info', 'submit', 'user', 'rank_id', 'updated');

   public function __construct($mixed = NULL) {
      $this->paper = 'no paper';
      $this->web_links = array();

      parent::__construct($mixed);
   }

   public static function &newFromDb(&$db, $pub_id, $flags = self::DB_LOAD_ALL) {
      assert('is_numeric($pub_id)');
      $pub = new pdPublication();
      $pub->dbLoad($db, $pub_id, $flags);
      return $pub;
   }

   /**
    * Loads a specific publication from the database.
    *
    * Use $flags to load information from other tables.
    */
   public function dbLoad($db, $id, $flags = self::DB_LOAD_ALL) {
      assert('is_object($db)');

      $this->dbLoadFlags = $flags;

      $q = $db->selectRow('publication', '*', array('pub_id' => $id),
                          "pdPublication::dbLoad");
      if ($q === false) return false;
      $this->load($q);

      $this->collaborations = array();
      $q = $db->select('pub_col', 'col_id', array('pub_id' => $this->pub_id),
                       "pdPublication::dbLoad",
                       array('ORDER BY' => 'col_id ASC'));

      foreach ($q as $r) {
         $this->collaborations[] = $r->col_id;
      }

      if ($flags & self::DB_LOAD_CATEGORY) {
         $q = $db->selectRow('pub_cat', 'cat_id', array('pub_id' => $id),
                             "pdPublication::dbLoad");

         if ($q !== false) {
            $this->category = new pdCategory();
            $this->category->dbLoad($db, $q->cat_id, null,
                                    pdCategory::DB_LOAD_BASIC);
         }
      }

      // some categories are not defined
      if (($flags & self::DB_LOAD_CATEGORY_INFO)
          && isset($this->category->cat_id)) {
         $this->category->dbLoadCategoryInfo($db);

         if ($this->category->info != null) {
            foreach ($this->category->info as $info_id => $name) {
               $r = $db->selectRow(
                  'pub_cat_info', 'value',
                  array('pub_id' => $id,
                        'cat_id' => $db->quote_smart($this->category->cat_id),
                        'info_id' => $db->quote_smart($info_id)),
                  "pdPublication::dbLoad");

               if (($r !== false) && ($r->value != ''))
                  $this->info[$name] = $r->value;
            }
         }
      }

      if ($flags & self::DB_LOAD_ADDITIONAL_INFO) {
         $q = $db->select(array('additional_info', 'pub_add'),
                          array('additional_info.location',
                                'additional_info.type'),
                          array('additional_info.add_id=pub_add.add_id',
                                'pub_add.pub_id' => $id),
                          "pdPublication::dbLoad");
         foreach ($q as $r) {
            $this->additional_info[] = $r;
         }
      }

      if ($flags & (self::DB_LOAD_AUTHOR_MIN
                    | self::DB_LOAD_AUTHOR_FULL)) {
         unset($this->authors);
         $this->dbLoadAuthors($db, $flags);
      }

      if ($flags & self::DB_LOAD_POINTER) {
         $q = $db->select('pointer', 'value',
                          array('pub_id' => $id, 'type' => 'int'),
                          "pdPublication::dbLoad");
         foreach ($q as $r) {
            $this->related_pubs[] = $r->value;
         }

         $q = $db->select('pointer', array('name', 'value'),
                          array('pub_id' => $id, 'type' => 'ext'),
                          "pdPublication::dbLoad");
         foreach ($q as $r) {
            $this->web_links[$r->name] = $r->value;
         }
      }

      if ($flags & self::DB_LOAD_VENUE) {
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

   public function dbLoadAuthors($db, $flags = self::DB_LOAD_AUTHOR_FULL) {
      assert('is_object($db)');

      if (isset($this->authors) && (count($this->authors) > 0)) return;

      $q = $db->select(array('author', 'pub_author'),
                       array('author.author_id', 'author.name'),
                       array('author.author_id=pub_author.author_id',
                             'pub_author.pub_id' => $this->pub_id),
                       "pdPublication::dbLoad",
                       array( 'ORDER BY' => 'pub_author.rank'));
      foreach ($q as $r) {
         if ($flags & self::DB_LOAD_AUTHOR_FULL) {
            $author = new pdAuthor();
            $author->dbLoad($db, $r->author_id, pdAuthor::DB_LOAD_BASIC);
            $this->authors[] = $author;
         }
         else {
            $this->authors[] = pdAuthor($r);
         }
      }
   }

   private function dbLoadVenue($db) {
      assert("($this->dbLoadFlags & self::DB_LOAD_VENUE)");

      if (($this->venue_id == null) || ($this->venue_id == '')
          || ($this->venue_id == '0')) return;

      $this->venue = new pdVenue();
      $this->venue->dbload($db, $this->venue_id);
   }

   public function dbDelete($db) {
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
                      'pub_rankings');
      foreach($tables as $table) {
         $db->delete($table, array('pub_id' => $this->pub_id),
                     'pdPublication::dbDelete');
      }
      $this->deleteFiles($db);
      $db->delete('pub_pending', array('pub_id' => $this->pub_id));
      $db->delete('pub_valid', array('pub_id' => $this->pub_id));
   }

   public function dbSave($db) {
      assert('is_object($db)');

      $arr = $this->membersAsArray(self::$db_table_fields);
      $arr['updated'] = date('Y-m-d');

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
      if (count($this->web_links) > 0) {
         foreach ($this->web_links as $text => $link) {
            array_push($arr, array('pub_id' => $this->pub_id,
                                   'type'   => 'ext',
                                   'name'   => $text,
                                   'value'  => $link));
         }
      }

      if (count($this->related_pubs ) > 0) {
         foreach ($this->related_pubs as $pub_id) {
            array_push($arr, array('pub_id' => $this->pub_id,
                                   'type'   => 'int',
                                   'name'   => '-',
                                   'value'  => $pub_id));
         }
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

      if (isset($this->authors) && (count($this->authors) > 0)) {
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

   public function dbAttUpdate($db, $filename, $type) {
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

   public function dbAttRemove(&$db, $filename) {
      assert('$this->pub_id != null');
      if (count($this->additional_info) == 0) return;

      foreach ($this->additional_info as $k => $o) {
         if (strpos($filename, $o->location) !== false) {
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

   public function authorsToArray() {
      if (!isset($this->authors) || (count($this->authors) == 0)) return null;

      $authors = array();
      foreach ($this->authors as $pub_auth) {
         $authors[$pub_auth->author_id]
            = $pub_auth->lastname . ', ' . $pub_auth->firstname;
      }
      return $authors;
   }

   public function authorsToString() {
      return implode('; ', $this->authorsToArray());
   }

   public function authorsToHtml($urlPrefix = null) {
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
   public function keywordsGet() {
      if (!isset($this->keywords)) return '';

      $keywords = preg_split("/;\s*/", $this->keywords);

      foreach ($keywords as $key => $value) {
         if ($value == "")
            unset($keywords[$key]);
      }
      return implode(", ", $keywords);
   }

   public function keywordsSet($keywords) {
      assert('is_array($keywords)');

      if (count($keywords) == 0) return;

      $words = implode('; ', $keywords);
      $words = preg_replace("/;\s*;/", ';', $words);
      $this->keywords = $words;
   }

   /**
    * Adds a keyword for the publication entry.
    *
    * @param string $newword the new keyword to add.
    */
   public function keywordAdd($newword) {
      if (!isset($this->keywords) || (strlen($this->keywords == 0)))
         $this->keywords = $newword;
      else
         $this->keywords .= '; ' . $newword;
   }

   /**
    * removes all extra_info items of length 0
    */
   public function extraInfoGet() {
      if (!isset($this->extra_info)) return '';

      $extra_info = preg_split('/;\s*/', $this->extra_info);

      foreach ($extra_info as $key => $value) {
         if ($value == "")
            unset($extra_info[$key]);
      }
      return implode(", ", $extra_info);
   }

   public function extraInfoSet($info) {
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

   public function addVenue($db, $mixed) {
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
         if (empty($this->venue->cat_id))
            $this->category = null;
         else
            $this->addCategory($db, $this->venue->cat_id);
         return;
      }

      if (is_numeric($mixed)) {
         if (is_object($this->venue)
             && ($this->venue->venue_id == $mixed)) return;

         assert('$mixed >= 0');
         $this->venue = new pdVenue();
         $result = $this->venue->dbLoad($db, $mixed);
         assert('$result');
         $this->venue_id = $this->venue->venue_id;

         // set this pub's category if not already set
         if (!isset($this->category)) {
            $this->category = new pdCategory();
            if ($this->venue->cat_id > 0) {
               $result
                  = $this->category->dbLoad($db, $this->venue->cat_id);
               assert('$result');
            }
         }
         return;
      }

      // should never get here since venues must now always be objects or
      // venue_ids
      assert('false');
   }

   public function addCategory($db, $mixed) {
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

   public function clearAuthors() {
      if (empty($this->authors) || (count($this->authors) == 0))
         return;
      unset($this->authors);
   }

   public function addAuthor($db, $mixed) {
      if (is_object($mixed)) {
         // check if publication already has this author
         if (isset($this->authors))
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
                                      pdAuthor::DB_LOAD_BASIC);
            assert('$result');
            $this->authors[$index] = $author;
         }
         return;
      }

      // check if publication already has this author
      if (isset($this->authors) && (count($this->authors) > 0)) {
         foreach ($this->authors as $author) {
            assert('$author->author_id != $mixed');
         }
      }

      assert('is_numeric($mixed)');

      $author = new pdAuthor();
      $result = $author->dbLoad($db, $mixed, pdAuthor::DB_LOAD_BASIC);
      assert('$result');
      $this->authors[] = $author;
   }

   public function getWebLinks() {
      $results = array();
      foreach ($this->web_links as $name => $url) {
         $results[$name] = $url;
      }
      return $results;
   }

   public function addWebLink($name, $url) {
      if (strpos($url, 'http') !== 0) {
         $url = 'http://' . $url;
      }
      $this->web_links[$name] = $url;
   }

   public function delWebLink($name) {
      if (isset($this->web_links[$name])) {
         unset($this->web_links[$name]);
      }
   }

   public function webLinkRemove($text, $link) {
      if (count($this->web_links) == 0) return;

      unset($this->web_links[$text]);
   }

   public function webLinkRemoveAll() {
      unset($this->web_links);
      $this->web_links = array();
   }

   public function paperDbUpdate($db, $paper) {
      $this->paper = $paper;
      $db->update('publication', array('paper' => $this->paper),
                  array('pub_id' => $this->pub_id),
                  'pdPublication::updatePaper');
   }

   public function relatedPubsGet() {
      return $this->related_pubs;
   }

   public function relatedPubAdd($pub_id) {
      $this->related_pubs[] = $pub_id;
   }

   public function relatedPubRemove($pub_id) {
      if (count($this->related_pubs) == 0) return;

      foreach ($this->related_pubs as $key => $link_pub_id) {
         if ($link_pub_id == $pub_id)
            unset($this->related_pubs[$key]);
      }

      // reindex
      $this->related_pubs = array_values($this->related_pubs);
   }

   public function paperExists() {
      $path = FS_PATH;
      if (strpos($this->paper, 'uploaded_files/') === false)
         $path .= '/uploaded_files/' . $this->pub_id . '/';
      $path .= $this->paper;

      return is_file($path);
   }

   public function attExists($att) {
      $path = FS_PATH;
      if (strpos($att->location, 'uploaded_files/') === false)
         $path .= '/uploaded_files/';
      $path .= $att->location;

      return is_file($path);
   }

   public function paperSave(&$db, $papername) {
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

      // if file exists then there is nothing to do
      if (($papername == $filename) || file_exists($filename)) return;

      // create the publication's path if it does not exist
      if (!is_dir($pub_path)) {
         mkdir($pub_path, 0777);
         // mkdir permissions with 0777 does not seem to work
         chmod($pub_path, 0777);
      }

      // delete the current paper
      $this->deletePaper($db);

      if (rename($papername, $filename)) {
         chmod($filename, 0777);
         $this->paperDbUpdate($db, $basename);
      }
   }

   public function attSave(&$db, $att_name, $att_type) {
      assert('is_object($db)');
      assert('$this->pub_id != ""');

      if (($att_name == '') || ($att_type == '')) return;

      $user =& $_SESSION['user'];

      if (count($this->additional_info) > 0)
         foreach ($this->additional_info as $att) {
            if (basename($att_name) == basename($att->location)) {
               return;
            }
            if ($att_type == $att->type) {
               $this->deleteAtt($db, $att);
            }
         }

      // make sure this attachment is not already in the list
      $basename = basename($att_name, '.' . $user->login);

      $pub_path = FS_PATH_UPLOAD . $this->pub_id . '/';

      $basename = basename($att_name, '.' . $user->login);
      $filename = $pub_path . $basename;

      // if file exists then there is nothing to do
      if (file_exists($filename)) return;

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

   public function deletePaper($db) {
      assert('isset($this->pub_id)');

      if (!isset($this->paper)) return;

      $pub_path = FS_PATH_UPLOAD . $this->pub_id . '/';
      $filepath = $pub_path . basename($this->paper);

      if (is_file($filepath))
         unlink($filepath);

      $this->paper = 'no paper';
      $this->paperDbUpdate($db, 'no paper');
   }

   // used by saveAtt()
   private function deleteAtt($db, $att) {
      assert('isset($this->pub_id)');

      $pub_path = FS_PATH_UPLOAD . $this->pub_id . '/';
      $filepath = $pub_path . basename($att->location);

      if (file_exists($filepath))
         unlink($filepath);
      $this->dbAttRemove($db, $att->location);
   }

   public function deleteAttByFilename(&$db, $filename) {
      if (count($this->additional_info) == 0) return;

      foreach ($this->additional_info as $k => $o) {
         if (strpos($filename, $o->location) !== false) {
            if (file_exists($filename)) {
               unlink($filename);
            }
         }
      }
      $this->dbAttRemove($db, $filename);
   }

   public function deleteFiles($db) {
      $this->deletePaper($db);

      if (count($this->additional_info) > 0) {
         foreach ($this->additional_info as $att) {
            $this->deleteAtt($db, $att);
         }
      }

      $pub_path = FS_PATH_UPLOAD . $this->pub_id;

      rm($pub_path);
   }

   public function attFilenameGet($num) {
      if ($this->pub_id == '') return null;

      assert('$num < count($this->additional_info)');

      return FS_PATH_UPLOAD . $this->pub_id . '/'
         . basename($this->additional_info[$num]->location);
   }

   public function paperAttGetUrl() {
      if(strtolower($this->paper) == 'no paper') return '';

      $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
      $result = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

      if (strpos($this->paper, 'uploaded_files/') === false)
         $result .= '/uploaded_files/' . $this->pub_id . '/';
      $result .= $this->paper;

      return $result;
   }

   public function attachmentGetUrl($att_num) {
      if($att_num >= count($this->additional_info)) return '';

      $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
      $result = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

      $att = $this->additional_info[$att_num];

      if (strpos($att->location, 'uploaded_files/') === false)
         $result .= '/uploaded_files/';
      $result .= $att->location;

      return $result;
   }

   public function getCitationHtml($urlPrefix = './', $author_links = true) {
      $citation = '';

      if (isset($this->authors) && (count($this->authors) > 0)) {
         $authors = array();
         foreach ($this->authors as $auth) {
            $content = '';
            if ($author_links)
               $content .= '<a href="' . $urlPrefix . 'view_author.php?'
                  . 'author_id=' . $auth->author_id . '">';
            if (strlen($auth->firstname) > 0) {
               $content .= $auth->firstname[0] . '. ' . $auth->lastname;
            } else {
               $content .= $auth->lastname;
            }

            if ($author_links)
               $content .= '</a>';
            $authors[] = $content;
         }
         $citation .= implode(', ', $authors) . '. ';
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

      // category -> if not conference, journal, or workshop, book or in book
      if (is_object($this->category)
          && !empty($this->category->category)
          && (!in_array($this->category->category,
                        array('In Conference', 'In Journal', 'In Workshop',
                              'In Book', 'Book')))) {
         $v .= $this->category->category;
      }

      if (is_object($this->venue)) {
         if (!empty($v))
            $v .= ', ';

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

         if (!empty($this->venue->data)
             && ($this->venue->categoryGet() == 'Workshop'))
            $v .= ' (within ' . $this->venue->data. ')';

         if (isset($pub_date))
            $location = $this->venue->locationGet($pub_date[0]);
         else
            $location = $this->venue->locationGet();

         if ($location != '')
            $v .= ', ' . $location;
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

   public function getCitationText() {
      $citation = '';

      if (isset($this->authors) && (count($this->authors) > 0)) {
         foreach ($this->authors as $auth) {
            $auth_text[] = $auth->firstname[0] . '. ' . $auth->lastname;
         }

         if (count($auth_text) > 0)
            $citation .= implode(', ', $auth_text) . '. ';
      }

      // Title
      $citation .= $this->title . '. ';

      // category -> if not conference, journal, or workshop, book or in book
      if (is_object($this->category)
          && !empty($this->category->category)
          && (!in_array($this->category->category,
                        array('In Conference', 'In Journal', 'In Workshop',
                              'In Book', 'Book')))) {
         $citation .= $this->category->category . ', ';
      }

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

   public function getBibtex() {
      $bibtex = '@incollection{';

      if (is_object($this->category) && isset($this->category->category)) {
         if ($this->category->category == 'In Conference') {
            $bibtex = '@incollection{';
         } else if ($this->category->category == 'In Journal') {
            $bibtex = '@article{';
         } else if ($this->category->category == 'In Book') {
            $bibtex = '@inbook{';
         } else if ($this->category->category == 'Book') {
            $bibtex = '@book{';
         } else if ($this->category->category == 'MSc Thesis') {
            $bibtex = '@mastersthesis{';
         } else if ($this->category->category == 'PhD Thesis') {
            $bibtex = '@phdthesis{';
         } else if ($this->category->category == 'Technical Report') {
            $bibtex = '@manual{';
         } else  {
            $bibtex = '@misc{';
         }
      }

      $pub_date = explode('-', $this->published);
      $venue_short = '';
      if (is_object($this->venue)) {
         if (isset($this->venue->title))
            $venue_short = preg_replace("/['-]\d+/", '',
                                        $this->venue->title);

         $venue_name = $this->venue->nameGet();

         if (!empty($this->venue->data)
             && ($this->venue->categoryGet() == 'Workshop'))
            $venue_name .= ' (within ' . $this->venue->data. ')';
      }

      if (isset($this->authors) && (count($this->authors) > 0)) {
         $auth_count = count($this->authors);
         if ($auth_count > 0) {
            $bibtex .= $this->authors[0]->lastname;
            if ($auth_count == 2)
               $bibtex .= '+' . $this->authors[1]->lastname;
            else if ($auth_count > 2)
               $bibtex .= '+al';

            if (isset($venue_short))
               $bibtex .= ':' . $venue_short;

            $bibtex = preg_replace("/\s/", '', $bibtex);
            $bibtex .= substr($pub_date[0], 2) . ",\n" . '  author = {';

            $arr = array();
            foreach ($this->authors as $auth) {
               $arr[] = $auth->firstname . ' ' . $auth->lastname;
            }
            $bibtex .= implode(' and ', $arr) . "},\n";
         }
      } else {
         $bibtex .= $this->pub_id . ",\n";
      }

      $bibtex .= '  title = {' . $this->title . "},\n";

      // show info
      if (count($this->info) > 0) {
         foreach ($this->info as $key => $value) {
            if ($value != '') {
               $bibtex .= '  ' . $key . ' = ';

               if (($key == 'Pages') || strpos($value, ' '))
                  $bibtex .= '{' . $value . "},\n";
               else
                  $bibtex .= '"' . $value . "\",\n";
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

   public function getInfoForCitation() {
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
            $validKeys = array('Booktitle', 'Edition', 'Publisher',
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

   public function info2str($validKeys, $values) {
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
   public function paperFilenameGet() {
      $basename = basename($this->paper);
      if (($this->pub_id == '') || (strtolower($this->paper) == 'no paper')
          || ($this->paper == '') || ($basename == 'paper_')) return null;

      return FS_PATH_UPLOAD . $this->pub_id . '/' . $basename;
   }

   public function duplicateTitleCheck($db) {
      assert('is_object($db)');

      $myTitleLower = preg_replace('/\s\s+/', ' ', strtolower($this->title));
      $all_pubs = pdPubList::create($db);

      $similarPubs = array();
      if (empty($all_pubs) || (count($all_pubs) == 0)) return $similarPubs;

      foreach ($all_pubs as $pub) {
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

   public static function pubsTitleSort($a , $b) {
      if (strtolower($a->title) == strtolower($b->title)) return 0;

      return (strtolower($a->title) < strtolower($b->title)) ? -1 : 1;
   }

   public static function pubsDateSortDesc($a , $b) {
      if (strtolower($a->published) == strtolower($b->published)) {
         if (strtolower($a->title) == strtolower($b->title)) return 0;

         return (strtolower($a->title) < strtolower($b->title)) ? -1 : 1;
      }

      return (strtolower($a->published) > strtolower($b->published))
         ? -1 : 1;
   }

   public static function rankingsGlobalGet(&$db) {
      $q = $db->select('pub_rankings', '*', 'pub_id is NULL',
                       "pdPublication::dbLoad");
      assert('count($q) > 0');

      foreach ($q as $r) {
         $rankings[$r->rank_id] = $r->description;
      }

      return $rankings;
   }

   public static function rankingsAllGet(&$db) {
      $q = $db->select('pub_rankings', '*', '',
                       "pdPublication::dbLoad");
      assert('count($q) > 0');

      foreach ($q as $r) {
         $rankings[$r->rank_id] = $r->description;
      }

      return $rankings;
   }

   public static function collaborationsGet(&$db) {
      $q = $db->select('collaboration', '*', '', "pdPublication::dbLoad");
      assert('count($q) > 0');

      foreach ($q as $r) {
         $collaborations[$r->col_id] = $r->description;
      }

      return $collaborations;
   }

   /**
    * Check if this pub entry is pending.
    *
    * @param object $db Database connection object.
    * @return returns true if the publication is pending.
    */
   public function validationRequired(&$db) {
      assert('is_object($db)');
      $q = $db->selectRow('pub_pending', '*', array('pub_id' => $this->pub_id));
      return ($q !== false);
   }

   /**
    * Can only be used by users with admin privilidages. Used to mark a
    * pending publication entry as valid.
    *
    * @param object $db Database connection object.
    */
   public function markValid(&$db) {
      assert('is_object($db)');

      $user =& $_SESSION['user'];
      assert('is_object($user)');

      if (!$user->isAdministrator()) return;

      // this was a pub entry that was pending, and was just edited
      // by user with admin privilidges
      $db->delete('pub_pending', array('pub_id' => $this->pub_id));

      $db->insert('pub_valid', array('pub_id' => $this->pub_id,
                                     'login' => $user->login));
   }

   /**
    * Marks the publication entry as pending and requires validation by
    * a user with admin privilidges.
    *
    * @param object $db Database connection object.
    */
   public function markPending(&$db) {
      assert('is_object($db)');

      $user =& $_SESSION['user'];
      assert('is_object($user)');

      if ($user->isAdministrator()) return;

      $q = $db->selectRow('pub_pending', '*', array('pub_id' => $this->pub_id));
      if ($q === false) {
         // user does not have admin privilidges, tag entry as pending
         $db->insert('pub_pending', array('pub_id' => $this->pub_id,
                                          'login' => $user->login));
      }
   }
}

?>
