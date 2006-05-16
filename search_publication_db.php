<?php

  /**
   * \file
   *
   * \brief Takes info from either advanced_search.php or header.php or
   * index.php.
   *
   * This takes the search query input and then searches the database and then
   * displays the results.
   */

require('functions.php');

?>

<html>
<head>
<title>Search Publication</title>
<link rel="stylesheet" href="style.css">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
      <link rel="stylesheet" href="style.css">

<?php
// Simple function to check to see if the string is a common word or not
function is_common_word($string){

  $common_words = array("a", "all", "am", "an", "and","any","are","as","at","be","but","can","did","do","does","for","from","had","has","have","here","how","i","if","in","is","it","no","not","of","on","or","so","that","the","then","there","this","to","too","up","use", "what","when","where","who","why","you");

  for($a =0; $a< count($common_words); $a++)
    if($string == $common_words[$a])
      return true;

  return false;
}

/**
 * Add words to the array except for special tokens,
 * keeps track of ors, doesn't keep track of quotes.
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
	for ($index=0; $index<strlen($search); $index++) {
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
		else if ($search[$index] == " " || $search[$index] == "," || $search[$index] == "\t") {
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

// adds the queried pub_ids to the array, checking for repeats as well
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

// return an array that consists of elements that exist in both arrays
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

print_r($_POST);

if($admin =="true")
    include 'headeradmin.php';
else
    include 'header.php';
echo "</head>";
echo "<body>";

$link = connect_db();
$pub_id_count = 0;

/* Publish date */
$pubdate1 = $year1 . "-" . $month1 . "-" . $day1;
$pubdate2 = $year2 . "-" . $month2 . "-" . $day2;

// We start as the result being every pub_id
$pub_id_array = NULL;
$search_query = "SELECT DISTINCT pub_id FROM publication";
add_to_array($search_query, $pub_id_array);

$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, strpos($_SERVER["SERVER_PROTOCOL"], "/"))).$s;
$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
$position = strpos($_SERVER["REQUEST_URI"], "?");
if ($position === false)
    $location = $_SERVER["REQUEST_URI"];
else
    $location = substr($_SERVER['REQUEST_URI'], 0,  $position);
$search_url = $protocol."://".$_SERVER['SERVER_NAME'].$port.$location."?";
$search_url .= "categorycheck=" . urlencode($categorycheck) . "&titlecheck=" . urlencode($titlecheck) .
    "&authorcheck=" . urlencode($authorcheck) . "&papercheck=" . urlencode($papercheck) . "&additionalcheck=" . urlencode($additionalcheck) .
    "&venuecheck=" . urlencode($venuecheck) . "&fullabstractcheck=" . urlencode($fullabstractcheck) . "&halfabstractcheck=" . urlencode($halfabstractcheck) .
    "&keywordscheck=" . urlencode($keywordscheck) . "&extracheck=" . urlencode($extracheck) . "&datecheck=" . urlencode($datecheck);
// I would love to make this ridiculously long URL shorter
// remember to urlencode anything else that gets added as a term to the search

// QUICK SEARCH -----------------------------------------------------------------
$search = $_POST['search'];
if($search != "") {
	$input = $search;
	$quick_search_array = parse_search(stripslashes($search));
	$search_url .= "&search=" . urlencode($search);
	echo "<h3> QUICK SEARCH </h3>";
	for ($index1 = 0; $index1 < count($quick_search_array); $index1++) {
		$union_array = NULL;
		for ($index2 = 0; $index2 < count($quick_search_array[$index1]); $index2++) {
			$search_term = $quick_search_array[$index1][$index2];

			//Search through the publication table
			$pub_search = array("title", "paper", "abstract", "keywords", "venue", "extra_info");

			for($a = 0; $a < count($pub_search); $a++) {
				$search_query = "SELECT DISTINCT pub_id from publication WHERE " . $pub_search[$a] . " LIKE " . quote_smart("%".$search_term."%");
				add_to_array($search_query, $union_array);
			}

			//Search Categories
			$search_query = "SELECT cat_id from category WHERE category LIKE " . quote_smart("%".$search_term."%");
			$search_result = query_db($search_query);
			while ($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
				$cat_id = $search_array['cat_id'];
				if($cat_id != null) {
					$search_query = "SELECT DISTINCT pub_id from pub_cat WHERE cat_id=" . quote_smart($cat_id);
					add_to_array($search_query, $union_array);
				}
			}

			//Search category specific fields
			$search_query = "SELECT DISTINCT pub_id from pub_cat_info WHERE value LIKE " . quote_smart("%".$search_term."%");
			add_to_array($search_query, $union_array);

			//Search Authors
			$search_query = "SELECT author_id from author WHERE name LIKE " . quote_smart("%".$search_term."%");
			$search_result = query_db($search_query);
			while ($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
				$author_id = $search_array['author_id'];
				if($author_id != null) {
					$search_query = "SELECT DISTINCT pub_id from pub_author WHERE author_id=" . quote_smart($author_id);
					add_to_array($search_query, $union_array);
				}
			}
		}
		$pub_id_array = keep_the_intersect($union_array, $pub_id_array);
	}
}// All results from quick search are in $pub_id_array
else {
	//ADVANCED SEARCH

	$first_item = true;

	/////// CATEGORY SEARCH -----------------------------------------------------
        //if category search found, pass on only the ids found with that match with category
        if($category != "") {
            $search_url .= "&category=" . urlencode($category);
            $first_item = false;
            $temporary_array = NULL;
            $search_query = "SELECT cat_id from category WHERE category LIKE " . quote_smart($category);
            $search_result = query_db($search_query);
            $search_array = mysql_fetch_array($search_result, MYSQL_ASSOC);
            $cat_id = $search_array['cat_id'];


            $search_query = "SELECT DISTINCT pub_id FROM pub_cat WHERE cat_id=" . quote_smart($cat_id);
            //we then add these matching id's to a temp array
            add_to_array($search_query, $temporary_array);

            //then we only keep the common ids between both arrays
            $pub_id_array = keep_the_intersect($temporary_array, $pub_id_array);

            // Search category related fields
            $info_query = "SELECT DISTINCT info.info_id, info.name FROM info, cat_info, pub_cat WHERE info.info_id=cat_info.info_id AND cat_info.cat_id=" . quote_smart($cat_id);
            $info_result = query_db($info_query);
            while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)) {
                $temporary_array = NULL;
                $info_id = $info_line['info_id'];
                $info_name = strtolower($info_line['name']);
                if($$info_name != "") {
                    $input .= " ".$$info_name;
                    $search_query = "SELECT DISTINCT pub_id FROM pub_cat_info WHERE cat_id=" . quote_smart($cat_id) . " AND info_id=" . quote_smart($info_id) . " AND value LIKE " . quote_smart("%".$info_name."%");
                    add_to_array($search_query, $temporary_array);
                    $pub_id_array = keep_the_intersect($temporary_array, $pub_id_array);
                }
            }
        }

        /////// PUBLICATION FIELDS SEARCH -------------------------------------------
            $pub_search = array ("title",  "paper", "abstract", "keywords", "venue", "extra_info");
            //same thing happening as category, just with each of these fields
            for ($a = 0; $a < count($pub_search); $a++) {
                $field = $pub_search[$a];
                if ($$field != "") {
                    $search_url .= "&" . urlencode($pub_search[$a]) . "=" . urlencode($$field);
                    $first_item = false;
                    $input .= " ".$$field;
                    $the_search_array = parse_search($$field);
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

            /////// AUTHOR SELECTED SEARCH ----------------------------------------------
                if ($authorselect[0] != NULL && $authortyped == "") {
                    $search_url .= "&authorselect[0]=" . urlencode($authorselect[0]);
                    $first_item = false;
                    $temporay_array = NULL;
                    $search_query = "SELECT DISTINCT pub_id from pub_author WHERE author_id=" . quote_smart($authorselect[0]);
                    add_to_array($search_query, $temporary_array);
                    $pub_id_array = keep_the_intersect($temporary_array, $pub_id_array);
                }

                /////// AUTHOR TYPED SEARCH --------------------------------------------------
                    if ($authortyped != "") {
                        $search_url .= "&authortyped=" . urlencode($authortyped);
                        $first_item = false;
                        $input .= " ".$authortyped;
                        $temporay_array = NULL;
                        $the_search_array = parse_search($authortyped);
                        for ($index1 = 0; $index1 < count($the_search_array); $index1++) {
                            $union_array = NULL;
                            for ($index2 = 0; $index2 < count($the_search_array[$index1]); $index2++) {
                                $term = $the_search_array[$index1][$index2];
                                $search_query = "SELECT DISTINCT author_id from author WHERE name LIKE " . quote_smart("%".$term."%");
                                $search_result = query_db($search_query);
                                while($search_array = mysql_fetch_array($search_result, MYSQL_ASSOC)) {
                                    $author_id = $search_array['author_id'];
                                    $search_query = "SELECT pub_id from pub_author WHERE author_id=" . quote_smart($author_id);
                                    add_to_array($search_query, $union_array);
                                }
                            }
                            $pub_id_array = keep_the_intersect($union_array, $pub_id_array);
                        }
                    }

                    //////DATES SEARCH-----------------------------------------------------------------------------------------
                    if ($pubdate1 != $pubdate2) {
                        $search_url .= "&year1=" . urlencode($year1) . "&" . "month1=" . urlencode($month1) . "&" . "day1=" . urlencode($day1);
                        $search_url .= "&" . "year2=" . urlencode($year2) . "&" . "month2=" . urlencode($month2) . "&" . "day2=" . urlencode($day2);
                        $temporary_array = NULL;
                        $search_query = "SELECT DISTINCT pub_id from publication WHERE published BETWEEN " . quote_smart($pubdate1) . " AND " . quote_smart($pubdate2);
                        add_to_array($search_query, $temporary_array);
                        $pub_id_array = keep_the_intersect($temporary_array, $pub_id_array);
                    }
}
//SHOW THE RESULTS-----------------------------------------------------------------------------------------------

$countentries = 0;
//$input = str_replace("\\\"","",$input);
//$input = str_replace("\"","",$input);
$input_unsanitized = str_replace("\'", "", stripslashes($input));
$titlecheck = true;
?>

<form name="pubForm" action="search_publication_db.php<? if($admin == "true") echo "?admin=true"; ?>" method="POST" enctype="multipart/form-data">
	<INPUT TYPE=hidden NAME="titlecheck" value="<? echo $titlecheck; ?>">
	<INPUT TYPE=hidden NAME="authorcheck" value="<? echo $authorcheck; ?>">
	<INPUT TYPE=hidden NAME="categorycheck" value="<? echo $categorycheck; ?>">
	<INPUT TYPE=hidden NAME="extracheck" value="<? echo $extracheck; ?>">
	<INPUT TYPE=hidden NAME="papercheck" value="<? echo $papercheck; ?>">
	<INPUT TYPE=hidden NAME="additionalcheck" value="<? echo $additionalcheck; ?>">
	<INPUT TYPE=hidden NAME="halfabstractcheck" value="<? echo $halfabstractcheck; ?>">
	<INPUT TYPE=hidden NAME="fullabstractcheck" value="<? echo $fullabstractcheck; ?>">
	<INPUT TYPE=hidden NAME="venuecheck" value="<? echo $venuecheck; ?>">
	<INPUT TYPE=hidden NAME="extra_infocheck" value="<? echo $extra_infocheck; ?>">
	<INPUT TYPE=hidden NAME="keywordscheck" value="<? echo $keywordscheck; ?>">
	<INPUT TYPE=hidden NAME="datecheck" value="<? echo $datecheck; ?>">
	<table><tr><td>
<font face="Arial, Helvetica, sans-serif" size="2"><b>Search: </b></font>
<input type="text" name="search" size="50" maxlength="250" value='<? echo $input_unsanitized; ?>'>
	<input type="SUBMIT" name="Quick" value="Search" class="text">
	<a href="./advanced_search.php<? if($admin == "true") echo "?admin=true"; ?>"><b>Advanced Search</b></a></td></tr>
</form>
<? 	for($c=0; $c < count($pub_id_array); $c++){
    $temp_ids .= $pub_id_array[$c];
    if($c < (count($pub_id_array)-1))
        $temp_ids .= ",";
}
if($pub_id_array != null){
	?>
	<tr><td colspan=2><table><tr><td>
		<form name="cvForm" action="cv.php" method="POST" enctype="multipart/form-data" target="_blank">
		<input type="hidden"  name="pub_ids" value="<? echo $temp_ids; ?>">
		<input type="SUBMIT" name="submit" value="Output these results to CV format" class="text">
    	</form>
        </td>
        <? } if($admin == "true"){ ?>
    <td>
		<form name="quickedit" action="Admin/quickedit.php?selection=true&pubid=<? echo $temp_ids; ?>" method="POST" enctype="multipart/form-data">
		&nbsp;&nbsp;<b>Quick edit these results:</b>
                         <select name="choice">
                         <option value="title">Title</option>
                         <option value="abstract">Abstract</option>
                         <option value="venue">Venue</option>
                         <option value="extra_info">Extra Information</option>
                         <option value="keywords">Keywords</option>
                         </select>
                         <input type="SUBMIT" name="submit" value="Go" class="text">
                         </form>
                         </td>
                         <? } ?>
                         </tr></table>
                         </td></tr></table>


                         <?
                         if($pub_id_array  == null)
                         {echo "<br><h3>No entries found.</h3>";}
                         else{
                             echo "<h3>".count($pub_id_array)." entries found.</h3>";
                             echo "<table>";
                             for($b=0; $b < count($pub_id_array); $b++){
                                 $pub_id = $pub_id_array[$b];
                                 /* Performing SQL query */
                                 $pub_query = "SELECT * FROM publication WHERE pub_id=" . quote_smart($pub_id);
                                 $pub_result = query_db($pub_query);
                                 $pub_array = mysql_fetch_array($pub_result, MYSQL_ASSOC);

                                 $cat_query = "SELECT category.category FROM category, pub_cat WHERE category.cat_id=pub_cat.cat_id AND pub_cat.pub_id=" . quote_smart($pub_id);
                                 $cat_result = query_db($cat_query);
                                 $cat_array = mysql_fetch_array($cat_result, MYSQL_ASSOC);

                                 $add_query = "SELECT additional_info.location FROM additional_info, pub_add WHERE additional_info.add_id=pub_add.add_id AND pub_add.pub_id=" . quote_smart($pub_id);
                                 $add_result = query_db($add_query);

                                 $author_query = "SELECT author.author_id, author.name FROM author, pub_author WHERE author.author_id=pub_author.author_id AND pub_author.pub_id=" . quote_smart($pub_id);
                                 $author_result = query_db($author_query);

                                 $info_query = "SELECT info.info_id, info.name FROM info, cat_info, pub_cat WHERE info.info_id=cat_info.info_id AND cat_info.cat_id=pub_cat.cat_id AND pub_cat.pub_id=" . quote_smart($pub_id);
                                 $info_result = query_db($info_query);

                                 echo "<tr class=\"";
                                 if($b%2 == 0) echo "odd";
                                 else echo "even";
                                 echo "\"><td class=\"large\" valign=\"top\">".($b+1).": </td><td>";

                                 ?>
                                     <table width="850" border="0" cellspacing="0" cellpadding="2">
                                          <? // Show Category
                                          if(($categorycheck)||($category != "")){
                                              echo "<tr><td class=\"small\">";
                                              echo "<u>".$cat_array['category']."</u>";
                                              echo "</td></tr>";
                                          }
                                 // Show Title
                                 if(($titlecheck)||($title != "")){
                                     echo "<tr><td class=\"large\">";
                                     echo "<b><a href=\"view_publication.php?";
                                     if($admin == "true") echo "admin=true&";
                                     echo "pub_id=".$pub_id."\">".$pub_array['title'];
                                     echo "</a></b>";
                                     echo "</td></tr>";
                                 }
                                 // Show Author
                                 if($authorcheck != false){
                                     echo "<tr><td class=\"standard\">";
                                     $first = true;
                                     while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
                                         if(!$first) echo " <b>-</b> ";
                                         echo "<a href=\"./view_author.php?";
                                         if($admin == "true") echo "admin=true&";
                                         echo "author_id=".$author_line['author_id']."\">";
                                         $author = split(",",$author_line['name']);
                                         echo "".$author[1]." ".$author[0]."</a>";
                                         $first = false;
                                     }
                                     echo "</td></tr>";
                                 }
                                 // Show Paper
                                 if(($papercheck != false)||($paper != "")){
                                     if($pub_array['paper'] == "No paper")
                                         $paperstring = "No Paper at this time.";
                                     else {
                                         $paperstring = "<a href=\".".$pub_array['paper'];
                                         $papername = split("paper_", $pub_array['paper']);
                                         $paperstring .= "\"> Paper:<i><b>$papername[1]</b></i></a>";
                                     }
                                     echo "<tr><td class=\"standard\">";
                                     echo "<b>Paper:</b>".$paperstring;
                                     echo "</td></tr>";
                                 }
                                 // Show Additional Materials
                                 if(($additionalcheck)||($additional != "")){
                                     $add_checker = mysql_fetch_array($add_result, MYSQL_ASSOC);
                                     if($add_checker['location'] != null){
                                         $add_query = "SELECT additional_info.location, additional_info.type
								FROM additional_info, pub_add
								WHERE additional_info.add_id=pub_add.add_id
								AND pub_add.pub_id=" . quote_smart($pub_id);
                                         $add_result = query_db($add_query);
                                         echo "<tr><td class=\"small\">";
                                         $add_count = 1;
                                         while ($add_line = mysql_fetch_array($add_result, MYSQL_ASSOC)) {
                                             $temp = split("additional_", $add_line['location']);
                                             echo "<b>";
                                             if($add_line['type'] != "")
                                                 echo $add_line['type'].":";
                                             else
                                                 echo "Additional Material " . ($add_count++).":";
                                             echo "</b>";
                                             echo "<a href=." . $add_line['location'] . ">";
                                             echo "<i><b>".$temp[1]."</b></i>";
                                             echo "</a><br>";
                                         }
                                         echo "</td></tr>";
                                     }
                                 }
                                 // Show the venue.
                                 if(($venuecheck)||($venue != "")){
                                     if($pub_array['venue'] != ""){
                                         echo "<tr><td class=\"standard\">";
                                         echo get_venue_info($pub_array['venue']);
                                         echo "</td></tr>";
                                     }
                                 }
                                 // Show the full abstract, and not the part abstract.
                                 if($fullabstractcheck){
                                     echo "<tr><td class=\"small\">";
                                     echo stripslashes($pub_array['abstract']);
                                     echo "</td></tr>";
                                 }
                                 // Show part of the abstract.
                                 else if(($halfabstractcheck != false )||($abstract != "")){
                                     echo "<tr><td class=\"small\">";
                                     $tempstring = stripslashes($pub_array['abstract']);
                                     if(strlen($tempstring) > 350) {$tempstring = substr($tempstring,0,350)."...";}
                                     echo $tempstring;
                                     echo "</td></tr>";
                                 }
                                 // Show the keywords
                                 if(($keywordscheck)||($keywords != "")){
                                     echo "<tr><td class=\"small\">";
                                     echo "<b>Keywords: </b>";
                                     $display_array = explode(";", $pub_array['keywords']);
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
                                 if(($extracheck)||($$pub_cat_info_name != "")){
                                     while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)) {
                                         $info_id = $info_line['info_id'];
                                         $value_query = "SELECT pub_cat_info.value
								FROM pub_cat_info, pub_cat
								WHERE pub_cat.pub_id=" . quote_smart($pub_id) . "
								AND pub_cat.cat_id=pub_cat_info.cat_id
								AND pub_cat_info.pub_id=" . quote_smart($pub_id) . "
								AND pub_cat_info.info_id=" . quote_smart($info_id);
                                         $value_result = mysql_query($value_query) or die("Query failed : " . mysql_error());
                                         $value_line = mysql_fetch_array($value_result, MYSQL_ASSOC);
                                         if(($value_line['value'] != "")&&($info_line['name'] != "")) {
                                             echo "<tr><td class=\"small\">";
                                             echo "<b>" . $info_line['name'] . ": </b>";
                                             echo $value_line['value'];
                                             echo "</td></tr>";
                                         }
                                     }
                                 }
                                 // Show the date the publication was published.
                                 if(($datecheck)||($pubdate1 != $pubdate2)){
                                     //PARSE DATES
                                     $thedate = "";
                                     $published = split("-",$pub_array['published']);
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
                             }
                             echo "</table>";
                         }
?>
<HR>
<?php echo "Link to this search: <font size=-2><a href=\"$search_url\">" . substr($search_url,0,96) . "...</a></font><br>"; ?>
<table><tr><td>
<form name="pubForm" action="search_publication_db.php<? if($admin == "true") echo "?admin=true"; ?>" method="POST" enctype="multipart/form-data">
                 <font face="Arial, Helvetica, sans-serif" size="2"><b>Search: </b></font>
<input type="text" name="search" size="50" maxlength="250" value='<? echo $input_unsanitized; ?>'>
                 <INPUT TYPE=hidden NAME="titlecheck" value="<? echo $titlecheck; ?>">
                 <INPUT TYPE=hidden NAME="authorcheck" value="<? echo $authorcheck; ?>">
                 <INPUT TYPE=hidden NAME="categorycheck" value="<? echo $categorycheck; ?>">
                 <INPUT TYPE=hidden NAME="extracheck" value="<? echo $extracheck; ?>">
                 <INPUT TYPE=hidden NAME="papercheck" value="<? echo $papercheck; ?>">
                 <INPUT TYPE=hidden NAME="additionalcheck" value="<? echo $additionalcheck; ?>">
                 <INPUT TYPE=hidden NAME="halfabstractcheck" value="<? echo $halfabstractcheck; ?>">
                 <INPUT TYPE=hidden NAME="fullabstractcheck" value="<? echo $fullabstractcheck; ?>">
                 <INPUT TYPE=hidden NAME="venuecheck" value="<? echo $venuecheck; ?>">
                 <INPUT TYPE=hidden NAME="extra_infocheck" value="<? echo $extra_infocheck; ?>">
                 <INPUT TYPE=hidden NAME="keywordscheck" value="<? echo $keywordscheck; ?>">
                 <INPUT TYPE=hidden NAME="datecheck" value="<? echo $datecheck; ?>">
                 <input type="SUBMIT" name="Quick" value="Search" class="text">
                 </form>
</td>
<td><a href="./advanced_search.php<? if($admin == "true") echo "?admin=true"; ?>"><b>Advanced Search</b></a></td></tr>
<tr><? if($pub_id_array != null){ ?><td colspan=2><table><tr><td>
                                  <form name="cvForm" action="cv.php" method="POST" enctype="multipart/form-data" target="_blank">
                                  <input type="hidden"  name="pub_ids" value="<? echo $temp_ids; ?>">
                                  <input type="SUBMIT" name="submit" value="Output these results to CV format" class="text">
                                  </form>
                                  </td>
                                  <? } if($admin == "true"){ ?>
    <td>
		<form name="quickedit" action="Admin/quickedit.php?selection=true&pubid=<? echo $temp_ids; ?>" method="POST" enctype="multipart/form-data">
		&nbsp;&nbsp;<b>Quick edit these results:</b>
                         <select name="choice">
                         <option value="title">Title</option>
                         <option value="abstract">Abstract</option>
                         <option value="venue">Venue</option>
                         <option value="extra_info">Extra Information</option>
                         <option value="keywords">Keywords</option>
                         </select>
                         <input type="SUBMIT" name="submit" value="Go" class="text">
                         </form>
                         </td>
                         <? } ?>
                         </tr></table>
                         </td></tr></table>
                         <BR><BR>
                         <?php back_button(); ?>
                         </body>
                         </html>

                         <?	/* Closing connection */
                         disconnect_db($link);
?>

                         </body>
                         </html>
