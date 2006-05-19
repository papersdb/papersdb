<?php ;

// $Id: search_publication_db.php,v 1.9 2006/05/19 17:42:40 aicmltec Exp $

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

ini_set("include_path", ini_get("include_path") . ":.:./includes:./HTML");

require_once 'functions.php';
require_once 'pdPublication.php';

require_once 'HTML/QuickForm.php';
require_once 'HTML/Table.php';

/**
 * These are the only options allowed by this script. These can be passed by
 * either GET or POST methods.
 */
$allowedOptions = array('categorycheck',
                        'titlecheck',
                        'authorcheck',
                        'papercheck',
                        'additionalcheck',
                        'venuecheck',
                        'fullabstractcheck',
                        'halfabstractcheck',
                        'keywordscheck',
                        'extracheck',
                        'datecheck',
                        'search',
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

makePage();

/**
 * Generates all the HTML for the page.
 */
function makePage() {
    global $allowedOptions;

    htmlHeader('Search Publication');
    echo "</head>\n<body>";

    if (count($_POST) > 0)
        $option = optionsGet($_POST);
    else
        $option = optionsGet($_GET);

    pageHeader();
    navigationMenu();

    print "<div id='content'>\n";

    $link = connect_db();
    $pub_id_count = 0;

    // We start as the result being every pub_id
    $pub_id_array = NULL;
    $search_query = "SELECT DISTINCT pub_id FROM publication";
    add_to_array($search_query, $pub_id_array);

    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
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
    foreach ($allowedOptions as $opt) {
        if (isset($option->$opt))
            $url_opt[] = $opt . "=" . urlencode($option->$opt);
    }

    if (count($url_opt) > 0) {
        $search_url .= implode("&", $url_opt);
    }

    // I would love to make this ridiculously long URL shorter remember to
    // urlencode anything else that gets added as a term to the search

    if($option->search != "") {
        $pub_id_array = quickSearch($pub_id_array, $option);
        echo "<h3> QUICK SEARCH </h3>";
    }
    else {
        $pub_id_array = advncedSearch($pub_id_array, $option);
    }

    // SHOW THE RESULTS -------------------------------------------------------
    $db =& dbCreate();
    $countentries = 0;
    $input_unsanitized = str_replace("\'", "", stripslashes($input));
    $titlecheck = true;

    $form =& searchFormCreate();
    $form->setDefaults(array('search' => $input_unsanitized));
    $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
    $form->accept($renderer);

    $table = new HTML_Table();

    $data = 'Search: '
        . $renderer->elementToHtml('search') . ' '
        . $renderer->elementToHtml('Quick') . ' '
        . "<a href='advanced_search.php'>"
        . "<b>Advanced Search</b></a>";

    $table->addRow(array($renderer->toHtml($data)), array('id' => 'emph'));

    $cvForm =& cvFormCreate($pub_id_array);
    if ($cvForm != null) {
        $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
        $cvForm->accept($renderer);
        $table->addRow(array($renderer->toHtml()));
    }

    print $table->toHtml();

    if($pub_id_array == null) {
        echo "<br><h3>No entries found.</h3>";
    }
    else {
        if (count($pub_id_array) == 1)
            echo "<h3>1 entry found.</h3>";
        else
            echo "<h3>" . count($pub_id_array) . " entries found.</h3>";
        echo "<table id='nomargins'>";

        $b = 0;
        foreach ($pub_id_array as $pub_id) {
            $pub = new pdPublication();
            $pub->dbLoad($db, $pub_id);

            echo "<tr class=\"";
            if ($b%2 == 0)
                echo "odd";
            else
                echo "even";

            echo "\"><td class=\"large\" valign=\"top\">".($b+1).": </td><td>";

            echo "<table id='nomargins' width='550' border='0' cellspacing='0' cellpadding='2'>";

            // Show Category
            if(($option->categorycheck == "yes") || ($option->category != "")) {
                echo "<tr><td class=\"small\">";
                echo "<u>" . $pub->category . "</u>";
                echo "</td></tr>";
            }

            // Show Title
            if(($option->titlecheck == "yes") || ($option->title != "")) {
                echo "<tr><td class=\"large\">";
                echo "<b><a href=\"view_publication.php?";
                if($admin == "true") echo "admin=true&";
                echo "pub_id=".$pub_id."\">".$pub->title;
                echo "</a></b>";
                echo "</td></tr>";
            }

            // Show Author
            if($option->authorcheck == "yes"){
                echo "<tr><td class=\"standard\">";
                $first = true;
                foreach ($pub->author as $auth) {
                    if(!$first) echo " <b>-</b> ";
                    echo "<a href=\"./view_author.php?";
                    echo "author_id=" . $auth->author_id . "\">";
                    $author = split(",", $auth->name);
                    echo "".$author[1]." ".$author[0]."</a>";
                    $first = false;
                }
                echo "</td></tr>";
            }

            // Show Paper
            if(($option->papercheck == 'yes')||($option->paper != "")){
                if($pub->paper == "No paper")
                    $paperstring = "No Paper at this time.";
                else {
                    $paperstring = "<a href=\".".$pub->paper;
                    $papername = split("paper_", $pub->paper);
                    $paperstring .= "\"> Paper:<i><b>$papername[1]</b></i></a>";
                }
                echo "<tr><td class=\"standard\">";
                echo "<b>Paper:</b>".$paperstring;
                echo "</td></tr>";
            }

            // Show Additional Materials
            if (($option->additionalcheck == 'yes')
                || ($option->additional != "")) {
                if(is_array($pub->additional_info)) {
                    echo "<tr><td class=\"small\">";
                    $add_count = 1;
                    foreach ($pub->additional_info as $additional) {
                        $temp = split("additional_", $additional->location);
                        echo "<b>";
                        if ($additional->type != "")
                            echo $additional->type . ":";
                        else
                            echo "Additional Material " . ($add_count++).":";
                        echo "</b>";
                        echo "<a href=." . $additional->location . ">";
                        echo "<i><b>".$temp[1]."</b></i>";
                        echo "</a><br>";
                    }
                    echo "</td></tr>";
                }
            }

            // Show the venue.
            if(($option->venuecheck = 'yes')||($option->venue != "")){
                if($pub->venue != ""){
                    echo "<tr><td class=\"standard\">";
                    echo get_venue_info($pub->venue);
                    echo "</td></tr>";
                }
            }

            if($option->fullabstractcheck == 'yes'){
                // Show the full abstract, and not the part abstract.
                echo "<tr><td class=\"small\">";
                echo stripslashes($pub->abstract);
                echo "</td></tr>";
            }
            else if(($option->halfabstractcheck == 'yes')
                    || ($option->abstract != "")){
                // Show part of the abstract.
                echo "<tr><td class=\"small\">";
                $tempstring = stripslashes($pub->abstract);
                if(strlen($tempstring) > 350) {
                    $tempstring = substr($tempstring,0,350)."...";
                }
                echo $tempstring;
                echo "</td></tr>";
            }

            // Show the keywords
            if(($option->keywordscheck = 'yes') || ($option->keywords != "")){
                echo "<tr><td class=\"small\">";
                echo "<b>Keywords: </b>";
                $display_array = explode(";", $pub->keywords);
                for ($i = 0; $i < count($display_array); $i++) {
                    if (($display_array[$i] != "")&& ($display_array[$i] != null))
                    { 	echo $display_array[$i];
                        if($i < count($display_array)-2)
                        {echo ", ";}
                    }
                }
                echo "</td></tr>";
            }

            // Show the extra information related to the category
            if (($option->extracheck = 'yes') && is_array($pub->info)) {
                foreach ($pub->info as $info) {
                    if(($info->value != "") && ($info->name != "")) {
                        echo "<tr><td class=\"small\">";
                        echo "<b>" . $info->name . ": </b>";
                        echo $info->value;
                        echo "</td></tr>";
                    }
                }
            }
            // Show the date the publication was published.
            if(($option->datecheck) || ($startdate != $enddate)){
                //PARSE DATES
                $thedate = "";
                $published = split("-",$pub->published);
                if($published[1] != 00)
                    $thedate .= date("F", mktime (0,0,0,$published[1]))." ";
                if($published[2] != 00)
                    $thedate .= $published[2].", ";
                if($published[0] != 0000)
                    $thedate .= $published[0];
                if($thedate != NULL){
                    echo "<tr><td class=\"small\">";
                    echo "<b>Date Published: </b>";
                    echo $thedate;
                    echo "</td></tr>";
                }
            }
            echo "</table></td></tr>";
            $b++;
        }
        echo "</table>";
    }

    echo "<hr/>"
        . "Link to this search: <div id='small'><a href='" . $search_url . "'>"
        . substr($search_url,0,96) . "...</a></div><br/>"
        . "</div>";

    $db->close();

    pageFooter();

    echo "</body>\n</html>\n";

}

/**
 * Retrieves the allowed options from an array. Note that this function should
 * only be called with $_POST or $_GET as the array.
 */
function optionsGet($arr) {
    global $allowedOptions;

    $option = new stdClass; // start off a new (empty) object

    foreach($allowedOptions as $allowedOpt) {
        if (isset($arr[$allowedOpt])) {
            if (is_array($arr[$allowedOpt])) {
                $option->$allowedOpt = arr2obj($arr[$allowedOpt]);
            }
            else {
                $option->$allowedOpt = $arr[$allowedOpt];
            }
        }
    }
    return $option;
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
$parse_search_add_word_or_next = false;
function parse_search_add_word($word, $array) {
	global $parse_search_add_word_or_next;

	if (strlen($word) == 0)
		return $array;
	if (strcasecmp($word, "and") == 0)
		return $array;
	if (strcasecmp($word, "or") == 0) {
	$parse_search_add_word_or_next = true;
		return $array;
	}
	else if ($parse_search_add_word_or_next == true) {
		$index = count($array)-1;
		$array[$index][] = $word;
		$parse_search_add_word_or_next = false;
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
				$search_terms = parse_search_add_word($word, $search_terms);
				$word = "";
			}
		}
		else {
			$word .= $search[$index];
		}
	}
	$search_terms = parse_search_add_word($word, $search_terms);
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
function quickSearch(&$pub_id_array, &$option) {
	$quick_search_array = parse_search(stripslashes($option->search));

	for ($index1 = 0; $index1 < count($quick_search_array); $index1++) {
		$union_array = NULL;
		for ($index2 = 0; $index2 < count($quick_search_array[$index1]);
             $index2++) {
			$search_term = $quick_search_array[$index1][$index2];

			//Search through the publication table
			$pub_search = array("title", "paper", "abstract", "keywords",
                                "venue", "extra_info");

			for($a = 0; $a < count($pub_search); $a++) {
				$search_query = "SELECT DISTINCT pub_id "
                    . "from publication WHERE " . $pub_search[$a]
                    . " LIKE " . quote_smart("%".$search_term."%");
				add_to_array($search_query, $union_array);
			}

			//Search Categories
			$search_query = "SELECT cat_id from category "
                . "WHERE category LIKE " . quote_smart("%".$search_term."%");
			$search_result = query_db($search_query);
			while ($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
				$cat_id = $search_array['cat_id'];
				if($cat_id != null) {
					$search_query = "SELECT DISTINCT pub_id from pub_cat WHERE cat_id=" . quote_smart($cat_id);
					add_to_array($search_query, $union_array);
				}
			}

			//Search category specific fields
			$search_query = "SELECT DISTINCT pub_id from pub_cat_info "
                . "WHERE value LIKE " . quote_smart("%".$search_term."%");
			add_to_array($search_query, $union_array);

			//Search Authors
			$search_query = "SELECT author_id from author "
                . "WHERE name LIKE " . quote_smart("%".$search_term."%");
			$search_result = query_db($search_query);
			while ($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
				$author_id = $search_array['author_id'];
				if($author_id != null) {
					$search_query = "SELECT DISTINCT pub_id from pub_author "
                        . "WHERE author_id=" . quote_smart($author_id);
					add_to_array($search_query, $union_array);
				}
			}
		}
		$pub_id_array = keep_the_intersect($union_array, $pub_id_array);
	}
    // All results from quick search are in $pub_id_array
    return $pub_id_array;
}

/**
 * Performs and advanced search.
 */
function advncedSearch(&$pub_id_array, &$option) {
	$first_item = true;

	// CATEGORY SEARCH ---------------------------------------------------
    //
    //if category search found, pass on only the ids found with that match with
    //category
    if($option->cat_id != "") {
        $first_item = false;
        $temporary_array = NULL;
        $cat_id = $option->cat_id;

        $search_query = "SELECT DISTINCT pub_id FROM pub_cat WHERE cat_id="
            . quote_smart($cat_id);
        //we then add these matching id's to a temp array
        add_to_array($search_query, $temporary_array);

        //then we only keep the common ids between both arrays
        $pub_id_array = keep_the_intersect($temporary_array, $pub_id_array);

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
                $input .= " ".$$info_name;
                $search_query = "SELECT DISTINCT pub_id "
                    . "FROM pub_cat_info WHERE cat_id=" . quote_smart($cat_id)
                    . " AND info_id=" . quote_smart($info_id)
                    . " AND value LIKE " . quote_smart("%".$info_name."%");
                add_to_array($search_query, $temporary_array);
                $pub_id_array
                    = keep_the_intersect($temporary_array, $pub_id_array);
            }
        }

        /** \todo what about category related fields? where are they
         * incorporated into the search?
         */
    }

    // PUBLICATION FIELDS SEARCH -------------------------------------------
    $pub_search = array ("title",  "paper", "abstract", "keywords", "venue",
                         "extra_info");
    //same thing happening as category, just with each of these fields
    for ($a = 0; $a < count($pub_search); $a++) {
        $field = $pub_search[$a];
        if ($option->$field != "") {
            $first_item = false;
            $input .= " ".$_POST[$field];
            $the_search_array = parse_search($option->$field);
            for ($index1 = 0; $index1 < count($the_search_array); $index1++) {
                $union_array = NULL;
                for ($index2 = 0; $index2 < count($the_search_array[$index1]); $index2++) {
                    $term = $the_search_array[$index1][$index2];
                    $search_query = "SELECT DISTINCT pub_id from publication WHERE " . $field . " LIKE " . quote_smart("%".$term."%");
                    add_to_array($search_query, $union_array);
                }
                $pub_id_array = keep_the_intersect($union_array, $pub_id_array);
            }
        }
    }

    // AUTHOR SELECTED SEARCH ----------------------------------------------
    /** \todo this code only handles the first author selected from the list */
    if (($option->authorselect[0] != NULL) && ($option->authortyped == "")) {
        $first_item = false;
        $temporay_array = NULL;
        $search_query = "SELECT DISTINCT pub_id from pub_author "
            . "WHERE author_id=" . quote_smart($option->authorselect[0]);
        add_to_array($search_query, $temporary_array);
        $pub_id_array = keep_the_intersect($temporary_array, $pub_id_array);
    }

    // AUTHOR TYPED SEARCH --------------------------------------------------
    if ($option->authortyped != "") {
        $first_item = false;
        $input .= " ".$authortyped;
        $temporay_array = NULL;
        $the_search_array = parse_search($option->authortyped);
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
                    add_to_array($search_query, $union_array);
                }
            }
            $pub_id_array = keep_the_intersect($union_array, $pub_id_array);
        }
    }

    // DATES SEARCH --------------------------------------
    if (($option->startdate != $option->enddate)
        && preg_match('/\d{4,4}-\d{2,2}-\d{2,2}/', $option->startdate)
        && preg_match('/\d{4,4}-\d{2,2}-\d{2,2}/', $option->enddate)) {

        $temporary_array = NULL;

        print "startdate: ". $startdate . " enddate: " . $enddate . "<br/>\n";

        $search_query = "SELECT DISTINCT pub_id from publication "
            . "WHERE published BETWEEN " . quote_smart($startdate)
            . " AND " . quote_smart($enddate);
        add_to_array($search_query, $temporary_array);
        $pub_id_array = keep_the_intersect($temporary_array, $pub_id_array);
    }

    return $pub_id_array;
}

/**
 *
 */
function searchFormCreate() {
    global $option;

    $form = new HTML_QuickForm('pubForm', 'post', 'search_publication_db.php',
                               '_self', 'multipart/form-data');

    $elements = array('titlecheck'        => $option->titlecheck,
                      'authorcheck'       => $option->authorcheck,
                      'categorycheck'     => $option->categorycheck,
                      'extracheck'        => $option->extracheck,
                      'papercheck'        => $option->papercheck,
                      'additionalcheck'   => $option->additionalcheck,
                      'halfabstractcheck' => $option->halfabstractcheck,
                      'fullabstractcheck' => $option->fullabstractcheck,
                      'venuecheck'        => $option->venuecheck,
                      'extra_infocheck'   => $option->extra_infocheck,
                      'keywordscheck'     => $option->keywordscheck,
                      'datecheck'         => $option->datecheck,
        );

    foreach ($elements as $name => $value) {
        $form->addElement('hidden', $name, $value);
    }
    $form->addElement('text', 'search', null,
                      array('size' => 45, 'maxlength' => 250));
    $form->addElement('submit', 'Quick', 'Search');

    return $form;
}

/**
 *
 */
function cvFormCreate($pub_id_array) {
    if ($pub_id_array == null) return;

    $form = new HTML_QuickForm('cvForm', 'post', 'cv.php', '_blank',
                               'multipart/form-data');
    $form->addElement('hidden', 'pub_ids', implode(",", $pub_id_array));
    $form->addElement('submit', 'submit', 'Output these results to CV format');

    return $form;
}

?>
