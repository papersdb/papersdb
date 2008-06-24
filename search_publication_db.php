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
require_once 'includes/SearchTermParser.php';


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
    protected $db_authors;

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
            $this->result_pubs = $this->quickSearch();
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
     * adds the queried pub_ids to the array, checking for repeats as well
     */
    private function add_to_array($query, &$thearray) {
        if ($thearray == null)
            $thearray = array();

        $search_result = $this->db->query($query);
        $result = array();
        foreach ($search_result as $row) {
            if (!in_array($row->pub_id, $thearray))
                array_push($thearray, $row->pub_id);
        }
    }

    /**
     * Performs a quick search.
     */
    private function &quickSearch() {    	
		$parser = new SearchTermParser($this->sp->search);
		$quick_search_array = $parser->getWordList();
		$result_pubs = $this->result_pubs;

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
                    
                    if (isset($_SESSION['user'])
                        && ($_SESSION['user']->showUserInfo())) {
                        // search on user info field also
                        $fields[] = 'user';
                    }

                    foreach ($fields as $field) {
                        $this->add_to_array(
                            'SELECT DISTINCT pub_id from publication WHERE ' . $field
                            . ' RLIKE '
                            . $this->db->quote_smart('[[:<:]]'.$term.'[[:>:]]'),
                            $union_array);
                    }

                    // search venues - title
                    $this->venuesSearch('title', $term, $union_array);

                    // search venues - name
                    $this->venuesSearch('name', $term, $union_array);

                    //Search Categories
                    $search_result = $this->db->query(
                        'SELECT cat_id from category WHERE category RLIKE '
                        . $this->db->quote_smart('[[:<:]]'.$term.'[[:>:]]'));

                    foreach ($search_result as $r) {
                        $this->add_to_array(
                            'SELECT DISTINCT pub_id from pub_cat WHERE cat_id='
                            . $this->db->quote_smart($r->cat_id),
                            $union_array);
                    }

                    //Search category specific fields
                    $this->add_to_array(
                        'SELECT DISTINCT pub_id from pub_cat_info WHERE value '
                        . 'RLIKE ' . $this->db->quote_smart('[[:<:]]'.$term.'[[:>:]]'),
                        $union_array);

                    //Search Authors
                    $search_result = $this->db->query(
                        'SELECT author_id from author WHERE name RLIKE '
                        . $this->db->quote_smart('[[:<:]]'.$term.'[[:>:]]'));

                    foreach ($search_result as $r) {
                        $this->add_to_array(
                            'SELECT DISTINCT pub_id from pub_author '
                            . 'WHERE author_id=' . $this->db->quote_smart($r->author_id),
                            $union_array);
                    }

                    // search pub_ranking
                    $search_result = $this->db->query(
                        'SELECT rank_id from pub_rankings '
                        . 'WHERE description RLIKE '
                        . $this->db->quote_smart('[[:<:]]'.$term.'[[:>:]]'));
                        
                    foreach ($search_result as $r) {
                        if (is_numeric($rank_id)) {
                            $this->add_to_array(
                                'SELECT DISTINCT pub_id from publication '
                                . 'WHERE rank_id=' . $this->db->quote_smart($r->rank_id),
                                $union_array);
                        }
                    }

                    // search venue_id
                    $search_result = $this->db->query(
                        'SELECT venue_id from venue_rankings '
                        . 'WHERE description RLIKE '
                        . $this->db->quote_smart('[[:<:]]'.$term.'[[:>:]]'));
                        
                    foreach ($search_result as $r) {
                        if (is_numeric($r->venue_id)) {
                            $this->add_to_array(
                                'SELECT DISTINCT pub_id from publication '
                                . 'WHERE venue_id=' . $this->db->quote_smart($r->venue_id),
                                $union_array);
                        }
                    }

                    // search collaborations
                    $search_result = $this->db->query(
                        'SELECT col_id from collaboration '
                        . 'WHERE description RLIKE '
                        . $this->db->quote_smart('[[:<:]]'.$term.'[[:>:]]'));
                        
                    foreach ($search_result as $r) {
                        if($r->col_id != null) {
                            $this->add_to_array(
                                'SELECT DISTINCT pub_id from pub_col '
                                . 'WHERE col_id=' . $this->db->quote_smart($r->col_id),
                                $union_array);
                        }
                    }
                }
                $result_pubs = array_intersect($result_pubs, $union_array);
                if ($this->debug) {
                    debugVar('$result_pubs', $result_pubs);
                }
            }
        }
        // All results from quick search are in $this->result_pubs
        return $result_pubs;
    }

    /**
     * Performs and advanced search.
     */
    private function advancedSearch() {
        // VENUE SEARCH ------------------------------------------
        if ($this->sp->venue != '') {
            $parser = new SearchTermParser($this->sp->venue);
            $the_search_array = $parser->getWordList();
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
                . $this->db->quote_smart($cat_id);
            //we then add these matching id's to a temp array
            $this->add_to_array($search_query, $temporary_array);

            //then we only keep the common ids between both arrays
            $this->result_pubs
                = array_intersect($this->result_pubs, $temporary_array);
        }

        // PUBLICATION FIELDS SEARCH ------------------------------------------
        $fields = array ("title",  "paper", "abstract", "keywords",
                             "extra_info");
        //same thing happening as category, just with each of these fields
        foreach ($fields as $field) {
            if (isset($this->sp->$field) && ($this->sp->$field != '')) {
                $parser = new SearchTermParser($this->sp->$field);
                $the_search_array = $parser->getWordList();
                foreach ($the_search_array as $and_terms) {
                    $union_array = null;
                    foreach ($and_terms as $or_term) {
                        $this->add_to_array(
                            'SELECT DISTINCT pub_id from publication WHERE '
                            . $field . ' LIKE '
                            . $this->db->quote_smart('%'.$or_term.'%'),
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
                if ($author_id !== false) {
	                $author_ids[] = $author_id;
                }
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
                    . "WHERE author_id=" . $this->db->quote_smart($auth_id);
                $this->add_to_array($search_query, $author_pubs);

                $this->result_pubs = array_intersect($this->result_pubs,
                                                     $author_pubs);
            }
        }

        // ranking
        if (isset($this->sp->paper_rank)) {
            $union_array = array();
            foreach ($this->sp->paper_rank as $rank_id => $value) {
                if ($value != 'yes') continue;

                $search_result = $this->db->query(
                    'SELECT venue_id from venue_rankings '
    	            . 'WHERE rank_id=' . $this->db->quote_smart($rank_id));
    	            
  	            foreach ($search_result as $row) {
                    if (!empty($row->venue_id))
                    $this->add_to_array(
                        'SELECT DISTINCT pub_id from publication '
                        . 'WHERE venue_id=' . $this->db->quote_smart($row->venue_id),
                        $union_array);
                }
            }

            foreach ($this->sp->paper_rank as $rank_id => $value) {
                if ($value != 'yes') continue;

                $this->add_to_array('SELECT DISTINCT pub_id from publication '
	                . 'WHERE rank_id=' . $this->db->quote_smart($rank_id),
                $union_array);
            }
        }

        if (!empty($this->sp->paper_rank_other)) {
            $this->add_to_array('SELECT DISTINCT pub_id from rankings '
                                . 'WHERE description LIKE '
                                . $this->db->quote_smart(
                                    "%" . $this->sp->paper_rank_other . "%"),
                                $union_array);
        }

        if (isset($union_array) && is_array($union_array)
            && (count($union_array) > 0)) {
            $this->result_pubs = array_intersect($this->result_pubs,
                                                 $union_array);
        }

        // collaboration
        if (isset($this->sp->paper_col)) {
            $union_array = array();
            foreach ($this->sp->paper_col as $col_id => $value) {
                if ($value != 'yes') continue;

                $this->add_to_array('SELECT DISTINCT pub_id from pub_col '
	                . 'WHERE col_id=' . $this->db->quote_smart($col_id),
	                $union_array);
            }

            if (count($union_array) > 0) {
                $this->result_pubs 
                    = array_intersect($this->result_pubs, $union_array);
            }
        }
        
        // user info
        if (!empty($this->sp->user_info)) {
            pdDb::debugOn();
            $union_array = array();
            $user_infos = preg_split('/\s*[;,]\s*/', $this->sp->user_info);
            foreach ($user_infos as $user_info) {
                $user_info = trim($user_info);
                $this->add_to_array('SELECT DISTINCT pub_id from publication '
	                . "WHERE user like '%$user_info%'",
	                $union_array);
            }

            if (count($union_array) > 0) {
                $this->result_pubs 
                    = array_intersect($this->result_pubs, $union_array);
            }
        }

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
                    . "WHERE published BETWEEN " . $this->db->quote_smart($startdate_str)
                    . " AND " . $this->db->quote_smart($enddate_str);
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

        $search_result = $this->db->query('SELECT venue_id from venue WHERE ' . $field
                                  . ' RLIKE '
                                  . $this->db->quote_smart('[[:<:]]'. $value . '[[:>:]]'));
        foreach ($search_result as $r) {
            $search_query
                = "SELECT DISTINCT pub_id from publication WHERE venue_id="
    	        . $this->db->quote_smart($r->venue_id);
            $this->add_to_array($search_query, $union_array);
        }
    }
}

$page = new search_publication_db();
echo $page->toHtml();

?>
