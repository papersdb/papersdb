<?php ;

// $Id: search_publication_db.php,v 1.29 2006/08/16 17:47:32 aicmltec Exp $

/**
 * \file
 *
 * \brief Takes info from either advanced_search.php or the navigation menu.
 *
 * This takes the search query input and then searches the database and then
 * displays the results.
 *
 * \note register_globals is assumed to be turned off.
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';
/**
 * Renders the whole page.
 */
class search_publication_db extends pdHtmlPage {
    /**
     * These are the only options allowed by this script. These can be passed
     * by either GET or POST methods.
     */
    var $allowed_options = array('search',
                                 'cat_id',
                                 'title',
                                 'authortyped',
                                 'authorselect',
                                 'paper',
                                 'abstract',
                                 'venue',
                                 'keywords',
                                 'startdate',
                                 'enddate');
    var $option_list;
    var $pub_id_array;
    var $parse_search_add_word_or_next = false;
    var $input;

    function search_publication_db() {
        parent::pdHtmlPage('search_results');
        $this->optionsGet();

        $link = connect_db();
        $pub_id_count = 0;

        // We start as the result being every pub_id
        $this->pub_id_array = NULL;
        $search_query = "SELECT DISTINCT pub_id FROM publication";
        $this->add_to_array($search_query, $this->pub_id_array);

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

        $search_url = $protocol."://".$_SERVER['SERVER_NAME'].$port.$location."?";

        $url_opt = array();
        foreach ($this->allowed_options as $opt) {
            if (isset($this->option_list->$opt)
                && ($this->option_list->$opt != '')) {
                if ($opt == 'authorselect') {
                    foreach ($this->option_list->$opt as $key => $value) {
                        $url_opt[] = $opt .'[' . $key . ']='
                            . urlencode($value);
                    }
                }
                else {
                    $url_opt[] = $opt . '='
                        . urlencode($this->option_list->$opt);
                }
            }
        }

        if (count($url_opt) > 0) {
            $search_url .= implode("&", $url_opt);
        }

        if($this->option_list->search != "") {
            $this->quickSearch($this->pub_id_array);
            $this->contentPre .= '<h3>SEARCH RESULTS</h3>';
        }
        else {
            $this->advancedSearch();
        }

        $this->searchResultsGenerate($search_url);

    }

    /**
     * Generates results in citation format.
     */
    function searchResultsGenerate($search_url) {
        global $access_level;

        $db =& dbCreate();
        $countentries = 0;
        $input_unsanitized = str_replace("\'", "", stripslashes($this->input));

        $table = new HTML_Table();

        $cvForm =& $this->cvFormCreate($this->pub_id_array);
        if ($cvForm != null) {
            $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
            $cvForm->accept($renderer);
            $table->addRow(array($renderer->toHtml()));
        }

        $this->contentPre .= $table->toHtml();

        if ($this->pub_id_array == null) {
            $this->contentPre
                .= '<br><h3>Your search did not generate any results.</h3>';
            return;
        }

        $table = new HTML_Table(array('class' => 'nomargins',
                                      'width' => '100%'));

        $b = 0;
        foreach ($this->pub_id_array as $pub_id) {
            $pub = new pdPublication();
            $pub->dbLoad($db, $pub_id);

            $pubTable = new HTML_Table();

            $citation = $pub->getCitationHtml();

            // Show Paper
            if ($pub->paper != 'No paper') {
                $citation .= '<a href="' . $pub->paperAttGetUrl() . '">';

                if (preg_match("/\.(pdf|PDF)$/", $pub->paper)) {
                    $citation .= '<img src="images/pdf.gif" alt="PDF" '
                        . 'height="18" width="17" border="0" align="middle">';
                }

                if (preg_match("/\.(ppt|PPT)$/", $pub->paper)) {
                    $citation .= '<img src="images/ppt.gif" alt="PPT" height="18" '
                        . 'width="17" border="0" align="middle">';
                }

                if (preg_match("/\.(ps|PS)$/", $pub->paper)) {
                    $citation .= '<img src="images/ps.gif" alt="PS" height="18" '
                        . 'width="17" border="0" align="middle">';
                }
                $citation .= '</a>';
            }

            // Show Additional Materials
            if (count($pub->additional_info) > 0) {
                $add_count = 1;
                foreach ($pub->additional_info as $att) {
                    $citation .= '<a href="'
                        . $pub->attachmentGetUrl($add_count - 1) . '">';

                    if (preg_match("/\.(pdf|PDF)$/", $att->location)) {
                        $citation .= '<img src="images/pdf.gif" alt="PDF" height="18" '
                            . 'width="17" border="0" align="middle">';
                    }

                    if (preg_match("/\.(ppt|PPT)$/", $att->location)) {
                        $citation .= '<img src="images/ppt.gif" alt="PPT" height="18" '
                            . 'width="17" border="0" align="middle">';
                    }

                    if (preg_match("/\.(ps|PS)$/", $att->location)) {
                        $citation .= '<img src="images/ps.gif" alt="PS" height="18" '
                            . 'width="17" border="0" align="middle">';
                    }

                    $add_count++;
                }
            }

            $pubTable->addRow(array($citation));

            $indexTable = new HTML_Table();

            $cell = ($b + 1)
                . '<br/><a href="view_publication.php?pub_id=' . $pub->pub_id . '">'
                . '<img src="images/viewmag.png" title="view" alt="view" height="16" '
                . 'width="16" border="0" align="middle" /></a>';

            if ($access_level > 0)
                $cell .= '<a href="Admin/add_publication.php?pub_id='
                    . $pub->pub_id . '">'
                    . '<img src="images/pencil.png" title="edit" alt="edit" height="16" '
                    . 'width="16" border="0" align="middle" /></a>';

            $indexTable->addRow(array($cell), array('nowrap'));

            $table->addRow(array($indexTable->toHtml(), $pubTable->toHtml()));
            $b++;
        }

        tableHighlightRows($table);

        $searchLinkTable = new HTML_Table(array('id' => 'searchlink',
                                                'border' => '0',
                                                'cellpadding' => '0',
                                                'cellspacing' => '0'));
        $searchLinkTable->addRow(
            array('<a href="' . $search_url . '">'
                  . '<img src="images/link.png" title="view" alt="view" '
                  . 'height="16" width="16" border="0" align="top" />'
                  . ' Link to this search</a></div><br/>'));

        $this->contentPre .= $table->toHtml()
            . '<hr/>' . $searchLinkTable->toHtml();

        $db->close();
    }


    /**
     * Retrieves the allowed options from an array. Note that this function
     * should only be called with $_POST or $_GET as the array.
     *
     * This code deals with advanced_search.php's form naming the 'startdate'
     * and 'enddate' fields in an array named 'datesGroup.'
     */
    function optionsGet() {
        if (count($_POST) > 0)
            $arr =& $_POST;
        else
            $arr =& $_GET;

        $this->option_list = new stdClass; // start off a new (empty) object

        foreach($this->allowed_options as $allowed_opt) {
            if (isset($arr[$allowed_opt])) {
                if ($allowed_opt == 'authorselect') {
                    $this->option_list->$allowed_opt = $arr[$allowed_opt];
                }
                else {
                    $this->option_list->$allowed_opt = $arr[$allowed_opt];
                }
            }
            else if (($allowed_opt == 'startdate')
                     || ($allowed_opt == 'enddate')) {
                if (isset($arr['datesGroup']))
                    $this->option_list->$allowed_opt
                        = $arr['datesGroup'][$allowed_opt];
                else
                    $this->option_list->$allowed_opt = $arr[$allowed_opt];
            }
        }
    }

    /**
     * Simple function to check to see if the string is a common word or not
     */
    function is_common_word($string){
        $common_words = array("a", "all", "am", "an", "and","any","are","as","at",
                              "be","but","can","did","do","does","for","from",
                              "had", "has","have","here","how","i","if","in","is",
                              "it","no", "not","of","on","or","so","that","the",
                              "then","there", "this","to","too","up","use",
                              "what","when","where", "who","why","you");

        for ($a =0; $a< count($common_words); $a++)
            if($string == $common_words[$a])
                return true;

        return false;
    }

    /**
     * Add words to the array except for special tokens, keeps track of ors,
     * doesn't keep track of quotes.
     */
    function parse_search_add_word($word, &$array) {
        if (strlen($word) == 0)
            return $array;
        if (strcasecmp($word, "and") == 0)
            return $array;
        if (strcasecmp($word, "or") == 0) {
            $parse_search_add_word_or_next = true;
            return $array;
        }
        else if ($this->parse_search_add_word_or_next == true) {
            $index = count($array)-1;
            $array[$index][] = $word;
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
    function parse_search($search) {
        $search_terms = array();
        $word = "";
        $quote_mode = false;
        for ($index=0; $index < strlen($search); $index++) {
            if ($search[$index] == "\"") {
                if ($quote_mode == true) {
                    $search_terms = parse_search_add_word($word, $search_terms);
                    $quote_mode = false;
                    $word = "";
                }
                else {
                    $search_terms = parse_search_add_word($word, $search_terms);
                    $quote_mode = true;
                    $word = "";
                }
            }
            else if (($search[$index] == " ") || ($search[$index] == ",")
                     || ($search[$index] == "\t")) {
                if ($quote_mode == true) {
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
    function add_to_array($query, &$thearray)
    {
        $search_result = query_db($query);
        while ($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC))
        {
            $found = false;
            for($e = 0; $e < count($thearray); $e++)
                if($thearray[$e] == $search_array['pub_id'])
                    $found = true;

            if(!$found)
                $thearray[] = $search_array['pub_id'];
        }
    }

    /**
     * return an array that consists of elements that exist in both arrays
     */
    function keep_the_intersect($array1, $array2)
    {
        $intersect_array = NULL;
        $count = 0;
        for($a = 0; $a < count($array1); $a++)
            for($b = 0; $b < count($array2); $b++)
                if($array1[$a] == $array2[$b])
                    $intersect_array[$count++] = $array1[$a];

        return $intersect_array;
    }

    /**
     * Performs a quick search.
     */
    function quickSearch() {
        $this->input = $this->option_list->search;
        $quick_search_array
            = $this->parse_search(stripslashes($this->option_list->search));

        for ($index1 = 0; $index1 < count($quick_search_array); $index1++) {
            $union_array = NULL;
            for ($index2 = 0; $index2 < count($quick_search_array[$index1]);
                 $index2++) {
                $search_term = $quick_search_array[$index1][$index2];

                //Search through the publication table
                $pub_search = array('title', 'paper', 'abstract', 'keywords',
                                    'extra_info', 'venue');

                foreach ($pub_search as $a) {
                    $search_query = 'SELECT DISTINCT pub_id '
                        . 'from publication WHERE ' . $a
                        . ' LIKE ' . quote_smart('%'.$search_term.'%');
                    $this->add_to_array($search_query, $union_array);
                }

                // search venues - title
                $search_query = "SELECT venue_id from venue "
                    . "WHERE title LIKE " . quote_smart("%".$search_term."%");
                $search_result = query_db($search_query);
                while ($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                    $venue_id = $search_array['venue_id'];
                    if($venue_id != null) {
                        $search_query = "SELECT DISTINCT pub_id from publication WHERE venue LIKE " . quote_smart($venue_id);
                        $this->add_to_array($search_query, $union_array);
                    }
                }

                // search venues - name
                $search_query = "SELECT venue_id from venue "
                    . "WHERE name LIKE " . quote_smart("%".$search_term."%");
                $search_result = query_db($search_query);
                while ($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                    $venue_id = $search_array['venue_id'];
                    if($venue_id != null) {
                        $search_query = "SELECT DISTINCT pub_id from publication WHERE venue LIKE " . quote_smart($venue_id);
                        $this->add_to_array($search_query, $union_array);
                    }
                }

                //Search Categories
                $search_query = "SELECT cat_id from category "
                    . "WHERE category LIKE " . quote_smart("%".$search_term."%");
                $search_result = query_db($search_query);
                while ($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                    $cat_id = $search_array['cat_id'];
                    if($cat_id != null) {
                        $search_query = "SELECT DISTINCT pub_id from pub_cat WHERE cat_id=" . quote_smart($cat_id);
                        $this->add_to_array($search_query, $union_array);
                    }
                }

                //Search category specific fields
                $search_query = "SELECT DISTINCT pub_id from pub_cat_info "
                    . "WHERE value LIKE " . quote_smart("%".$search_term."%");
                $this->add_to_array($search_query, $union_array);

                //Search Authors
                $search_query = "SELECT author_id from author "
                    . "WHERE name LIKE " . quote_smart("%".$search_term."%");
                $search_result = query_db($search_query);
                while ($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                    $author_id = $search_array['author_id'];
                    if($author_id != null) {
                        $search_query = "SELECT DISTINCT pub_id from pub_author "
                            . "WHERE author_id=" . quote_smart($author_id);
                        $this->add_to_array($search_query, $union_array);
                    }
                }
            }
            $this->pub_id_array = $this->keep_the_intersect($union_array, $this->pub_id_array);
        }
        // All results from quick search are in $this->pub_id_array
        return $this->pub_id_array;
    }

    /**
     * Performs and advanced search.
     */
    function advancedSearch() {
        $first_item = true;

        // CATEGORY SEARCH ----------------------------------------------------
        //
        // if category search found, pass on only the ids found with that match
        // with category
        if($this->option_list->cat_id != '') {
            $first_item = false;
            $temporary_array = NULL;
            $cat_id = $this->option_list->cat_id;

            $search_query = "SELECT DISTINCT pub_id FROM pub_cat WHERE cat_id="
                . quote_smart($cat_id);
            //we then add these matching id's to a temp array
            $this->add_to_array($search_query, $temporary_array);

            //then we only keep the common ids between both arrays
            $this->pub_id_array
                = $this->keep_the_intersect($temporary_array,
                                            $this->pub_id_array);

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
                    $this->input .= " ".$$info_name;
                    $search_query = "SELECT DISTINCT pub_id "
                        . "FROM pub_cat_info WHERE cat_id=" . quote_smart($cat_id)
                        . " AND info_id=" . quote_smart($info_id)
                        . " AND value LIKE " . quote_smart("%".$info_name."%");
                    $this->add_to_array($search_query, $temporary_array);
                    $this->pub_id_array
                        = $this->keep_the_intersect($temporary_array, $this->pub_id_array);
                }
            }

            /**
             * \todo what about category related fields? where are they
             * incorporated into the search?
             */
        }

        // PUBLICATION FIELDS SEARCH ------------------------------------------
        $pub_search = array ("title",  "paper", "abstract", "keywords", "venue",
                             "extra_info");
        //same thing happening as category, just with each of these fields
        for ($a = 0; $a < count($pub_search); $a++) {
            $field = $pub_search[$a];
            if ($this->option_list->$field != "") {
                $first_item = false;
                $this->input .= " ".$_POST[$field];
                $the_search_array
                    = $this->parse_search($this->option_list->$field);
                for ($index1 = 0; $index1 < count($the_search_array); $index1++) {
                    $union_array = NULL;
                    for ($index2 = 0; $index2 < count($the_search_array[$index1]); $index2++) {
                        $term = $the_search_array[$index1][$index2];
                        $search_query = "SELECT DISTINCT pub_id from publication WHERE " . $field . " LIKE " . quote_smart("%".$term."%");
                        $this->add_to_array($search_query, $union_array);
                    }
                    $this->pub_id_array = $this->keep_the_intersect($union_array, $this->pub_id_array);
                }
            }
        }

        // AUTHOR SELECTED SEARCH ---------------------------------------------
        /** \todo this code only handles the first author selected from the list */
        if (($this->option_list->authorselect[0] != NULL) && ($this->option_list->authortyped == "")) {
            $first_item = false;
            $temporay_array = NULL;
            $search_query = "SELECT DISTINCT pub_id from pub_author "
                . "WHERE author_id=" . quote_smart($this->option_list->authorselect[0]);
            $this->add_to_array($search_query, $temporary_array);
            $this->pub_id_array = $this->keep_the_intersect($temporary_array, $this->pub_id_array);
        }

        // AUTHOR TYPED SEARCH ------------------------------------------------
        if ($this->option_list->authortyped != "") {
            $first_item = false;
            $this->input .= " ".$authortyped;
            $temporay_array = NULL;
            $the_search_array = $this->parse_search($this->option_list->authortyped);
            for ($index1 = 0; $index1 < count($the_search_array); $index1++) {
                $union_array = NULL;
                for ($index2 = 0; $index2 < count($the_search_array[$index1]); $index2++) {
                    $term = $the_search_array[$index1][$index2];
                    $search_query = "SELECT DISTINCT author_id from author "
                        . "WHERE name LIKE " . quote_smart("%".$term."%");
                    $search_result = query_db($search_query);
                    while($search_array = mysql_fetch_array($search_result,
                                                            MYSQL_ASSOC)) {
                        $author_id = $search_array['author_id'];
                        $search_query = "SELECT pub_id from pub_author "
                            . "WHERE author_id=" . quote_smart($author_id);
                        $this->add_to_array($search_query, $union_array);
                    }
                }
                $this->pub_id_array = $this->keep_the_intersect($union_array, $this->pub_id_array);
            }
        }

        // DATES SEARCH --------------------------------------
        $startdate =& $this->option_list->startdate;
        $enddate =& $this->option_list->enddate;

        if (($startdate != $enddate)
            && preg_match('/\d{4,4}-\d{2,2}-\d{2,2}/', $startdate)
            && preg_match('/\d{4,4}-\d{2,2}-\d{2,2}/', $enddate)) {

            $temporary_array = NULL;

            $search_query = "SELECT DISTINCT pub_id from publication "
                . "WHERE published BETWEEN " .
                quote_smart($startdate)
                . " AND " . quote_smart($enddate);
            $this->add_to_array($search_query, $temporary_array);
            $this->pub_id_array = $this->keep_the_intersect($temporary_array, $this->pub_id_array);
        }

        return $this->pub_id_array;
    }

    /**
     *
     */
    function searchFormCreate() {
        $form = new HTML_QuickForm('pubForm', 'post',
                                   'search_publication_db.php',
                                   '_self', 'multipart/form-data');
        $form->addElement('text', 'search', null,
                          array('size' => 45, 'maxlength' => 250));
        $form->addElement('submit', 'Quick', 'Search');

        return $form;
    }

    /**
     *
     */
    function cvFormCreate() {
        if ($this->pub_id_array == null) return;

        $form = new HTML_QuickForm('cvForm', 'post', 'cv.php', '_blank',
                                   'multipart/form-data');
        $form->addElement('hidden', 'pub_ids', implode(",", $this->pub_id_array));
        $form->addElement('submit', 'submit', 'Output these results to CV format');

        return $form;
    }
}

session_start();
$access_level = check_login();
$page = new search_publication_db();
echo $page->toHtml();

?>
