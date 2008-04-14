<?php

/**
 * Takes info from either advanced_search.php or the navigation menu.
 *
 * This takes the search query input and then searches the database and then
 * displays the results.
 *
 * \note register_globals is assumed to be turned off.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/functions.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';
require_once 'includes/pdSearchParams.php';

#include "includes/debug.php";

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class search_publication_db extends pdHtmlPage {
    protected $debug = 0;
    protected $sp;
    protected $result_pubs;
    protected $parse_search_add_word_or_next = false;
    protected $db_authors;
    protected static $common_words = array(
    	"a", "all", "am", "an", "and","any","are","as", "at", "be","but","can",
    	"did","do","does","for", "from", "had", "has","have","here","how","i",                             
    	"if","in","is", "it","no", "not","of","on","or", "so","that","the", 
    	"then","there", "this","to", "too","up","use", "what","when","where", 
    	"who", "why","you");

    public function __construct() {
        parent::__construct('search_results');

        if ($this->loginError) return;

        $this->optionsGet();

        if ($this->debug) {
            debugVar('_SESSION', $_SESSION);
        }
        
        $this->db_authors = pdAuthorList::create($this->db, null, null, true);
        
        $sel_author_names = explode(', ', preg_replace('/\s\s+/', ' ',
                                                       $this->sp->authors));
        $this->sp->authors = implode(', ', cleanArray($sel_author_names));        

        $link = connect_db();
        $pub_id_count = 0;

        // We start as the result being every pub_id
        $search_query = "SELECT DISTINCT pub_id FROM publication";
        $this->add_to_array($search_query, $this->result_pubs);

        $s = (empty($_SERVER["HTTPS"])
              ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "");

        $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0,
                                      strpos($_SERVER["SERVER_PROTOCOL"], "/"))).$s;
        $port = ($_SERVER["SERVER_PORT"] == "80")
            ? "" : (":".$_SERVER["SERVER_PORT"]);
        $position = strpos($_SERVER["REQUEST_URI"], "?");

        if ($position === false)
            $location = $_SERVER["REQUEST_URI"];
        else
            $location = substr($_SERVER['REQUEST_URI'], 0,  $position);

        $search_url = $protocol . '://' . $_SERVER['SERVER_NAME'] . $port
            . $location . '?' . $this->sp->paramsToHtmlQueryStr();

        if($this->sp->search != "") {
            $this->quickSearch($this->result_pubs);
        }
        else {
            $this->advancedSearch();
        }

        $_SESSION['search_results'] = $this->result_pubs;
        $_SESSION['search_url'] = $search_url;

        if ($this->debug) {
            return;
        }

        header('Location: search_results.php');
    }

    /**
     * Retrieves the allowed options from an array. Note that this function
     * should only be called with $_POST or $_GET as the array.
     *
     * This code deals with advanced_search.php's form naming the 'startdate'
     * and 'enddate' fields in an array named 'datesGroup.'
     */
    private function optionsGet() {
        if (($_SERVER['REQUEST_METHOD'] == 'POST') && (count($_POST) > 0))
            $arr =& $_POST;
        else if ($_SERVER['REQUEST_METHOD'] == 'GET')
            $arr =& $_GET;

        $this->sp = new pdSearchParams($arr);
        $_SESSION['search_params'] =& $this->sp;

        if ($this->debug) {
            debugVar('options', $arr);
            debugVar('$this->sp', $this->sp);
        }
    }

    /**
     * Simple function to check to see if the string is a common word or not
     */
    private static function is_common_word($word){
        return in_array($word, self::$common_words);
    }

    /**
     * Adds word to the array except for special tokens, keeps track of ORs,
     * doesn't keep track of quotes.
     */
    private function &parse_search_add_word($word, &$array) {
        if ($word == '')
            return $array;
        if (strcasecmp($word, "and") == 0)
            return $array;
        if (strcasecmp($word, "or") == 0) {
            $this->parse_search_add_word_or_next = true;
            return $array;
        }
        else if ($this->parse_search_add_word_or_next) {
            $array[count($array) - 1][] = $word;
            $this->parse_search_add_word_or_next = false;
            return $array;
        }
        else {
            $array[] = array($word);
            return $array;
        }
    }

    /**
     * Chunk the search into an array of and-ed array of or-ed terms.
     */
    private function parse_search($search) {
        $search_terms = array();
        $word = "";
        $quote_mode = false;
        $len = strlen($search);
        for ($index = 0; $index < $len; $index++) {
            if ($search[$index] == "\"") {
                if ($quote_mode) {
                    $search_terms = $this->parse_search_add_word($word, $search_terms);
                    $quote_mode = false;
                    $word = "";
                }
                else {
                    $search_terms = $this->parse_search_add_word($word, $search_terms);
                    $quote_mode = true;
                    $word = "";
                }
            }
            else if (($search[$index] == " ") || ($search[$index] == ",")
                     || ($search[$index] == "\t")) {
                if ($quote_mode) {
                    $word .= $search[$index];
                }
                else {
                    $search_terms
                        = $this->parse_search_add_word($word, $search_terms);
                    $word = "";
                }
            }
            else {
                $word .= $search[$index];
            }
        }
        $search_terms = $this->parse_search_add_word($word, $search_terms);
        return $search_terms;
    }

    /**
     * adds the queried pub_ids to the array, checking for repeats as well
     */
    private function add_to_array($query, &$thearray) {
        if ($thearray == null)
            $thearray = array();

        $search_result = query_db($query);
        $result = array();

        while ($row = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
            if (!in_array($row['pub_id'], $thearray))
                array_push($thearray, $row['pub_id']);
        }
        mysql_free_result($search_result);
    }

    /**
     * Performs a quick search.
     */
    private function quickSearch() {
        $quick_search_array
            = $this->parse_search(stripslashes($this->sp->search));

        if ($this->debug) {
            debugVar('$quick_search_array', $quick_search_array);
        }
        foreach ($quick_search_array as $and_terms) {
            $union_array = array();
            foreach ($and_terms as $or_terms) {
                foreach (explode(' ', $or_terms) as $term) {
                	debugVar('$term', $term);
                    //Search through the publication table
                    $fields = array('title', 'paper', 'abstract', 'keywords',
                                    'extra_info');

                    foreach ($fields as $field) {
                        $this->add_to_array(
                            'SELECT DISTINCT pub_id from publication WHERE ' . $field
                            . ' RLIKE '
                            . quote_smart('[[:<:]]'.$term.'[[:>:]]'),
                            $union_array);
                    }

                    // search venues - title
                    $this->venuesSearch('title', $term, $union_array);

                    // search venues - name
                    $this->venuesSearch('name', $term, $union_array);

                    //Search Categories
                    $search_result = query_db(
                        'SELECT cat_id from category WHERE category RLIKE '
                        . quote_smart('[[:<:]]'.$term.'[[:>:]]'));

                    while ($search_array
                           = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                        $cat_id = $search_array['cat_id'];
                        if($cat_id != null) {
                            $this->add_to_array(
                                'SELECT DISTINCT pub_id from pub_cat WHERE cat_id='
                                . quote_smart($cat_id),
                                $union_array);
                        }
                    }

                    //Search category specific fields
                    $this->add_to_array(
                        'SELECT DISTINCT pub_id from pub_cat_info WHERE value '
                        . 'RLIKE ' . quote_smart('[[:<:]]'.$term.'[[:>:]]'),
                        $union_array);

                    //Search Authors
                    $search_result = query_db(
                        'SELECT author_id from author WHERE name RLIKE '
                        . quote_smart('[[:<:]]'.$term.'[[:>:]]'));

                    while ($search_array
                           = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                        if ($search_array !== false) {
                            $author_id = $search_array['author_id'];
                            if($author_id != null) {
                                $this->add_to_array(
                                    'SELECT DISTINCT pub_id from pub_author '
                                    . 'WHERE author_id=' . quote_smart($author_id),
                                    $union_array);
                            }
                        }
                    }

                    // search pub_ranking
                    $search_result = query_db(
                        'SELECT rank_id from pub_rankings '
                        . 'WHERE description RLIKE '
                        . quote_smart('[[:<:]]'.$term.'[[:>:]]'));

                    while ($search_array
                           = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                        $rank_id = $search_array['rank_id'];

                        if (is_numeric($rank_id)) {
                            $this->add_to_array(
                                'SELECT DISTINCT pub_id from publication '
                                . 'WHERE rank_id=' . quote_smart($rank_id),
                                $union_array);
                        }
                    }

                    // search venue_ranking
                    $search_result = query_db(
                        'SELECT venue_id from venue_rankings '
                        . 'WHERE description RLIKE '
                        . quote_smart('[[:<:]]'.$term.'[[:>:]]'));

                    while ($search_array
                           = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                        $venue_id = $search_array['venue_id'];

                        if (is_numeric($rank_id)) {
                            $this->add_to_array(
                                'SELECT DISTINCT pub_id from publication '
                                . 'WHERE venue_id=' . quote_smart($venue_id),
                                $union_array);
                        }
                    }

                    // search collaborations
                    $search_result = query_db(
                        'SELECT col_id from collaboration '
                        . 'WHERE description RLIKE '
                        . quote_smart('[[:<:]]'.$term.'[[:>:]]'));

                    while ($search_array
                           = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                        $col_id = $search_array['col_id'];
                        if($col_id != null) {
                            $this->add_to_array(
                                'SELECT DISTINCT pub_id from pub_col '
                                . 'WHERE col_id=' . quote_smart($col_id),
                                $union_array);
                        }
                    }
                }
            }
            $this->result_pubs = array_intersect($this->result_pubs, $union_array);
        }
        // All results from quick search are in $this->result_pubs
        return $this->result_pubs;
    }

    /**
     * Performs and advanced search.
     */
    private function advancedSearch() {
        // VENUE SEARCH ------------------------------------------
        if ($this->sp->venue != '') {
            $the_search_array
                = $this->parse_search($this->sp->venue);
            foreach ($the_search_array as $and_terms) {
                $union_array = null;
                foreach ($and_terms as $or_term) {
                    $this->venuesSearch('title', $or_term, $union_array);
                    $this->venuesSearch('name', $or_term, $union_array);
                }
                $this->result_pubs = array_intersect($this->result_pubs,
                                                     $union_array);
            }
        }

        // CATEGORY SEARCH ----------------------------------------------------
        //
        // if category search found, pass on only the ids found with that match
        // with category
        if($this->sp->cat_id != '') {
            $temporary_array = NULL;
            $cat_id = $this->sp->cat_id;

            $search_query = "SELECT DISTINCT pub_id FROM pub_cat WHERE cat_id="
                . quote_smart($cat_id);
            //we then add these matching id's to a temp array
            $this->add_to_array($search_query, $temporary_array);

            //then we only keep the common ids between both arrays
            $this->result_pubs
                = array_intersect($this->result_pubs, $temporary_array);

            // Search category related fields
            $info_query = "SELECT DISTINCT info.info_id, info.name "
                . "FROM info, cat_info, pub_cat "
                . "WHERE info.info_id=cat_info.info_id AND cat_info.cat_id="
                . quote_smart($cat_id);
            $info_result = query_db($info_query);
            while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)) {
                $temporary_array = NULL;
                $info_id = $info_line['info_id'];
                $info_name = strtolower($info_line['name']);
                if($$info_name != "") {
                    $search_query = "SELECT DISTINCT pub_id "
                        . "FROM pub_cat_info WHERE cat_id=" . quote_smart($cat_id)
                        . " AND info_id=" . quote_smart($info_id)
                        . " AND value REGEXP "
                        . quote_smart('[[:<:]]'.$term.'[[:>:]]');
                    $this->add_to_array($search_query, $temporary_array);

                    $this->result_pubs
                        = array_intersect($this->result_pubs, $temporary_array);
                }
            }
        }

        // PUBLICATION FIELDS SEARCH ------------------------------------------
        $fields = array ("title",  "paper", "abstract", "keywords",
                             "extra_info");
        //same thing happening as category, just with each of these fields
        foreach ($fields as $field) {
            if (isset($this->sp->$field)
                && ($this->sp->$field != '')) {
                $the_search_array
                    = $this->parse_search($this->sp->$field);
                foreach ($the_search_array as $and_terms) {
                    $union_array = null;
                    foreach ($and_terms as $or_term) {
                        $this->add_to_array(
                            'SELECT DISTINCT pub_id from publication WHERE '
                            . $field . ' LIKE '
                            . quote_smart('%'.$or_term.'%'),
                            $union_array);
                    }
                    $this->result_pubs = array_intersect($this->result_pubs,
                                                          $union_array);
                }
            }
        }

        // MYSELF or AUTHOR SELECTED SEARCH -----------------------------------
        $authors = array();
        
        if (!empty($this->sp->authors)) {
            // need to retrieve author_ids for the selected authors
            $sel_author_names = explode(', ', preg_replace('/\s\s+/', ' ',
                                                           $this->sp->authors));
                                                     
        	$author_ids = array();
            foreach ($sel_author_names as $author_name) {
                if (empty($author_name)) continue;
                
                $author_id = array_search($author_name, $this->db_authors);
                if ($author_id !== false)
	                $author_ids[] = $author_id;
            }

            if (count($author_ids) > 0) {
                $authors = array_merge ($authors, $author_ids);
            }
        }

        if (($this->sp->author_myself != '')
            && ($_SESSION['user']->author_id != ''))
            array_push($authors, $_SESSION['user']->author_id);

        if (count($authors) > 0) {
            foreach ($authors as $auth_id) {
                $author_pubs = array();
                $search_query = "SELECT DISTINCT pub_id from pub_author "
                    . "WHERE author_id=" . quote_smart($auth_id);
                $this->add_to_array($search_query, $author_pubs);

                $this->result_pubs = array_intersect($this->result_pubs,
                                                     $author_pubs);
            }
        }

        // ranking
        $union_array = array();
        foreach ($this->sp->paper_rank as $rank_id => $value) {
            if ($value != 'yes') continue;

            $search_result = query_db(
                'SELECT venue_id from venue_rankings '
                . 'WHERE rank_id=' . quote_smart($rank_id));

            while ($search_array
                   = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                $venue_id = $search_array['venue_id'];

                if (!empty($venue_id))
                    $this->add_to_array(
                        'SELECT DISTINCT pub_id from publication '
                        . 'WHERE venue_id=' . quote_smart($venue_id),
                        $union_array);
            }
        }

        foreach ($this->sp->paper_rank as $rank_id => $value) {
            if ($value != 'yes') continue;

            $this->add_to_array('SELECT DISTINCT pub_id from publication '
                                . 'WHERE rank_id=' . quote_smart($rank_id),
                                $union_array);
        }

        if (!empty($this->sp->paper_rank_other)) {
            $this->add_to_array('SELECT DISTINCT pub_id from rankings '
                                . 'WHERE description LIKE '
                                . quote_smart(
                                    "%" . $this->sp->paper_rank_other . "%"),
                                $union_array);
        }

        if (count($union_array) > 0)
            $this->result_pubs = array_intersect($this->result_pubs,
                                                 $union_array);

        // collaboration
        $union_array = array();
        foreach ($this->sp->paper_col as $col_id => $value) {
            if ($value != 'yes') continue;

            $this->add_to_array('SELECT DISTINCT pub_id from pub_col '
                                . 'WHERE col_id=' . quote_smart($col_id),
                                $union_array);
        }

        if (count($union_array) > 0)
            $this->result_pubs = array_intersect($this->result_pubs,
                                                 $union_array);


        // DATES SEARCH --------------------------------------
        if (isset($this->sp->startdate)) {
            $startdate =& $this->sp->startdate;
            $stime = strtotime(implode('-', $startdate) . '-1');
        }

        if (isset($this->sp->enddate)) {
            $enddate =& $this->sp->enddate;
            $etime = strtotime(implode('-', $enddate) . '-1');
        }

        if (isset($stime) && isset($etime)) {
            if ($stime > $etime) {
                // the user did not enter an end date, default it to now
                $enddate['Y'] = date('Y');
                $enddate['M'] = date('m');
                $etime = strtotime(implode('-', $enddate) . '-1');
            }

            if ($etime > $stime) {

                $startdate_str
                    = date('Y-m-d', mktime(0, 0, 0, $startdate['M'], 1,
                                           $startdate['Y']));
                $enddate_str
                    = date('Y-m-d', mktime(0, 0, 0, $enddate['M'] + 1, 0,
                                           $enddate['Y']));

                $temporary_array = NULL;

                $search_query = "SELECT DISTINCT pub_id from publication "
                    . "WHERE published BETWEEN " . quote_smart($startdate_str)
                    . " AND " . quote_smart($enddate_str);
                $this->add_to_array($search_query, $temporary_array);
                $this->result_pubs = array_intersect($this->result_pubs,
                                                     $temporary_array);
            }
        }

        if ($this->debug) {
            debugVar('result', $this->result_pubs);
        }

        return $this->result_pubs;
    }

    /**
     *
     */
    private function cvFormCreate() {
        if ($this->result_pubs == null) return;

        $form = new HTML_QuickForm('cvForm', 'post', 'cv.php', '_blank',
                                   'multipart/form-data');
        $form->addElement('hidden', 'pub_ids', implode(",", $this->result_pubs));
        $form->addElement('submit', 'submit', 'Output these results to CV format');

        return $form;
    }

    private function venuesSearch($field, $value, &$union_array) {
        assert('($field == "name") || ($field == "title")');

        $search_result = query_db('SELECT venue_id from venue WHERE ' . $field
                                  . ' RLIKE '
                                  . quote_smart('[[:<:]]'. $value . '[[:>:]]'));
        while ($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
            $venue_id = $search_array['venue_id'];
            if ($venue_id != null) {
                $search_query
                    = "SELECT DISTINCT pub_id from publication WHERE venue_id="
                    . quote_smart($venue_id);
                $this->add_to_array($search_query, $union_array);
            }
        }
    }
}

$page = new search_publication_db();
echo $page->toHtml();

?>
