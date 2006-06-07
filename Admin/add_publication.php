<?php ;

// $Id: add_publication.php,v 1.16 2006/06/07 14:04:49 aicmltec Exp $

/**
 * \file
 *
 * \brief This page is the form for adding/editing a publication.
 *
 * It has many side functions that are needed for the form to work
 * smoothly. It takes the input from the user, and then sends that input to
 * add_publication_db.php.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pageConfig.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdPublication.php';
require_once 'includes/pdPubList.php';

require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/advmultiselect.php';
require_once 'HTML/Table.php';

$db =& dbCreate();

htmlHeader('add_publication', 'Add or Edit Publication');

if (!$logged_in) {
    echo '<body>';
    pageHeader();
    navMenu('add_publication');
    echo "<div id='content'>\n";
    loginErrorMessage();
}


//User's 10 most popular Authors
function popularauthors(){
    $userauthorcount = 0;
    $user_query
        = "SELECT pub_author.author_id "
        . "FROM pub_author, publication, user "
		. "WHERE publication.submit = user.name "
        . "AND publication.pub_id = pub_author.pub_id "
        . "AND user.login=\"" .$_SERVER['PHP_AUTH_USER'] . "\"";

    $user_result  = mysql_query($user_query)
        or die("Query failed: " . mysql_error());
    while($user_array = mysql_fetch_array($user_result, MYSQL_ASSOC)){
		$popular_users[$user_array['author_id']]++;
		$listofauthors[$userauthorcount++] = $user_array['author_id'];
    }
    if($userauthorcount < 10) $length = $userauthorcount; else $length = 10;
    for($count = 0; $count < $length; $count++){
        $largest = "";
        $largestvalue = 0;
        for($index = 0; $index< $userauthorcount; $index++)
            if($popular_users[$listofauthors[$index]] > $largestvalue){
                $largestvalue = $popular_users[$listofauthors[$index]];
                $largest = $listofauthors[$index];
            }
        $finallist[$count] = $largest;
        $popular_users[$largest] = 0;
    }
    return $finallist;

}

// Global variable to keep track of what we're doing - can change this
// to not be a boolean if we want to deal with more than 2 different
// operations (save, new) on this page
$edit = FALSE;
//////////////////////EDIT START/////////////////////////////////
// Check to see if we've been passed a publication ID
if ((isset($_GET['pub_id']) && $_GET['pub_id'] != "")
    && ($_GET['new'] != "false")) {

	// Set "edit mode" to true - we could just check for the existence
	// of pub_id in the GET variables, but this is more clear.
	$edit = TRUE;
	// Get publication info
    $pub = new pdPublication();
    $pub->dbLoad($db, $_GET['pub_id']);

	// Check if the publication actually exists
	if (!isset($pub->pub_id)) {
        "Error: Publication with ID " . $_GET['pub_id'] . " doesn't exist.";
        echo "</div>\n";
        pageFooter();
        echo "</body></html>";
        $db->close();
		exit;
	}
    if(($intpoint == "")&&($ext == "")){
        $point_query = "SELECT type, name, value FROM pointer WHERE pub_id="
            . $_GET['pub_id'];
        $point_result = query_db($point_query);
        $intpoint = 0;
        $ext = 0;
        while($point_line = mysql_fetch_array($point_result, MYSQL_ASSOC)){
            if($point_line[type] == "int"){
                $internal = "intpointer".($intpoint++);
                $$internal = $point_line[value];
            }
            else if($point_line[type] == "ext"){
                $externalname = "extname".$ext;
                $$externalname = $point_line[name];

                $temparray1 = split("<a href=\"",$point_line[value]);
                $temparray2 = split("\" target=\"_blank\">",$temparray1[1]);
                $temparray3 = split("</a>",$temparray2[1]);

                $externalvalue = "extvalue".$ext;
                $$externalvalue = $temparray3[0];
                $externallink = "extlink".($ext++);
                $$externallink = $temparray2[0];
            }
        }
    }

	// Set the variables to be set in the page as initial values.  We have to
	// check and see if there's a value that's already been posted back to us,
	// and use that instead, in case it changes between page updates.

	$catvals = get_category($_GET['pub_id']);
	$category_id = $catvals['cat_id'];

	if ($_GET['category'] == "") {
		$category = $catvals['category'];
	}

	if ($_GET['title'] == "") {
		$title = $pubInfo['title'];
	}

	if ($_GET['abstract'] == "") {
		$abstract = $pubInfo['abstract'];
	}

	if ($_GET['venue'] == "") {
		$venue = $pubInfo['venue'];
	}

	if ($_GET['extra_info'] == "") {
		$extra_info = $pubInfo['extra_info'];
	}

	if ($_GET['keywords'] == "") {
		$keywords = $pubInfo['keywords'];
	}

	// Deal with the publication date.
	// variables we care about are $month, $day, $year.
	$published = $pubInfo['published'];

	$myYear = strtok($published,"-");
	$myMonth = strtok("-");
	$myDay = strtok("-");

	if ($_GET['month'] == "") {
		$month = $myMonth;
	}

	if ($_GET['day'] == "") {
		$day = $myDay;
	}

	if ($_GET['year'] == "") {
		$year = $myYear;
	}


	// Check the number of materials
	// Don't allow the user to set the number of materials less
	// than what currently exist in the DB.
	$dbMaterials = get_num_db_materials ($pub_id);

	if ($_GET['numMaterials'] != "") {
		if ($_GET['numMaterials'] < $dbMaterials) {
			$numMaterials = $dbMaterials;
		}
	}
	else {
		$numMaterials = $dbMaterials;
	}


	// andy_note: Paper is a special case! For now we'll use strtok to
	// get only the name of the file and discard the rest.
	$paper = $pubInfo['paper'];
	$paperTmp = strtok($paper,"/");

	// Since strtok will return a "false" as the last element, the
	// item we're actually interested in is the item that appears
	// *second to last*.  So we set $paper = $paperTmp and then get
	// the right thing.
	while ($paperTmp) {
		$paper = $paperTmp;
		$paperTmp = strtok("/");
	}

	$authors_from_db = get_authors($_GET['pub_id']);
}
/////////////////////EDIT END///////////////////////////////////////

if (isset($_GET['category']))
    while (!(strpos($_GET['category'], "\\") === FALSE)) {
        $_GET['category'] = stripslashes($_GET['category']);
    }

if (isset($_GET['title']))
while (!(strpos($_GET['title'], "\\") === FALSE)) {
    $title = stripslashes($title);
}


/* Adding a new author

This takes input from add_author.php and then adds it to the
database. This code is on this page because it allows the author
to be instantly added to the list to choose from.

*/
if ($newAuthorSubmitted == "true") {
    $authorname = trim($lastname) . ", " .trim($firstname);
    $check_query = "SELECT author_id FROM author WHERE name=\"$authorname\"";
    $check_result = mysql_query($check_query);
    $check_array =  mysql_fetch_array($check_result, MYSQL_ASSOC);
    if ($check_array[author_id] != "") {
        echo "<script language=\"Javascript\">"
            . "alert (\"Author already exists.\")"
            . "</script>";
    }
    else {
	    //add http:// to webpage address if needed
	    if(strpos($webpage, "http") === FALSE)
        {
		    $webpage = "http://".$webpage;
        }

		/* Performing SQL query */
		$author_query = "INSERT INTO author "
            . "(author_id, name, title, email, organization, webpage) "
            . "VALUES (NULL, \"$authorname\", \"$auth_title\", \"$email\", "
            . "\"$organization\", \"$webpage\")";
		$author_result = mysql_query($author_query)
            or die("Query failed : " . mysql_error());

		$unique_interest_id_counter = 0;

		for ($i = 0; $i < count($newInterest); $i++) {
			if ($newInterest[$i] != "") {
				$interest_query = "INSERT INTO interest "
                    . "(interest_id, interest) "
                    . "VALUES (NULL, \"$newInterest[$i]\")";
				$interest_result = mysql_query($interest_query)
                    or die("Query failed : " . mysql_error());

				$interest_id_query = "SELECT interest_id FROM interest "
                    . "WHERE interest=\"$newInterest[$i]\"";
				$interest_id_result = mysql_query($interest_id_query)
                    or die("Query failed: " . mysql_error());
				$interest_id_temp_array
                    =  mysql_fetch_array($interest_id_result, MYSQL_ASSOC);

				$interest_id_array[$unique_interest_id_counter]
                    = $interest_id_temp_array[interest_id];
				$unique_interest_id_counter++;

				mysql_free_result($interest_id_result);
			}
		}

		$author_id_query
            = "SELECT author_id FROM author WHERE name=\"$authorname\"";
		$author_id_result = mysql_query($author_id_query)
            or die("Query failed: " . mysql_error());

		$author_id_array = mysql_fetch_array($author_id_result, MYSQL_ASSOC);
		$author_id = $author_id_array['author_id'];

		$temp = "";

		for ($i = 0; $i < $numInterests; $i++) {
			if ($interests[$i] != null) {
				$temp .= " (" . $author_id . "," . $interests[$i] . "),";
			}
		}

		for ($i = 0; $i < $unique_interest_id_counter; $i++) {
			$temp .= " (" . $author_id . "," . $interest_id_array[$i] . "),";
		}

		$temp = substr_replace($temp, "", (strlen($temp) - 1), strlen($temp));

		if ($temp != "") {
			$author_interest_query
                = "INSERT INTO author_interest (author_id, interest_id) VALUES $temp";
			$author_interest_result = mysql_query($author_interest_query) or die("Query failed: " . mysql_error());
		}

		$newAuthorSubmitted == "false";

		// This is to preserve the selections the user has already made
		$all_author_query = "SELECT name FROM author";
		$all_author_result = mysql_query($all_author_query) or die("Query failed: " . mysql_error());
		$position = -1;
		$author_counter = 0;

		while ($all_author_line = mysql_fetch_array($all_author_result, MYSQL_ASSOC)) {
			if (strcmp($all_author_line['name'], $authorname) == 0) {
				$position = $author_counter;
			}
			$author_counter++;
		}

		$push_counter = 0;

		for ($i = 0; $i < $author_counter; $i++) {
			if ($i >= $position) {
				if ($authors[$i] != "") {
					$push_array[$push_counter] = $i + 1;
					$push_counter++;
				}
			}
		}

		for ($i = 0; $i < ($author_counter + 1); $i++) {
			if ($i > $position) {
				$authors[$i] = "";
			}
			if (in_array($i, (array)$push_array)) {
				$authors[$i] = $i . "selected";
			}
		}

		$authors[$position] = $position . "selected";

		mysql_free_result($author_id_result);
		mysql_free_result($all_author_result);

	}

	if($fromauthorspage == "true")
	{
		echo "<h3>Author added.</h3>";
		echo "<a href=\"../list_author.php?admin=true\">Back to Authors</a>";
		echo "<br><a href=\"./\">Administrator Page</a>";
		exit;

	}
}

/* Adding a new category
 This code takes input from add_category.php and
 adds the category to the database. Like the authors,
 this is here so that the newly added category can be
 instantly selected.
*/
print_r($_POST);

if ($_POST['newCatSubmitted'] == "true") {
    $category = new pdCategory();
    $category->load(array('category' => $_POST['catname']));

    if (isset($_POST['new_field']) && is_array($_POST['new_field'])) {
        $category->load(array('info' => $_POST['new_field']));
    }
    print_r($category);


    /* Performing SQL query */
    $cat_query = "INSERT INTO category (cat_id, category) VALUES (NULL, \"$catname\")";
    $cat_result = mysql_query($cat_query) or die("Query failed : " . mysql_error());

    $unique_info_id_counter = 0;

    for ($i = 0; $i < count($new_field); $i++) {
        if ($new_field[$i] != "") {
            $info_query = "INSERT INTO info (info_id, name) VALUES (NULL, \"$new_field[$i]\")";
            $info_result = mysql_query($info_query) or die("Query failed : " . mysql_error());

            $info_id_query = "SELECT info_id FROM info WHERE name=\"$new_field[$i]\"";
            $info_id_result = mysql_query($info_id_query) or die("Query failed: " . mysql_error());
            $info_id_temp_array =  mysql_fetch_array($info_id_result, MYSQL_ASSOC);

            $info_id_array[$unique_info_id_counter] = $info_id_temp_array[info_id];
            $unique_info_id_counter++;

            mysql_free_result($info_id_result);
        }
    }

    // update our information to sync with what we added to the db
    $cat_id_query = "SELECT cat_id FROM category WHERE category=\"$catname\"";
    $cat_id_result = mysql_query($cat_id_query) or die("Query failed: " . mysql_error());

    $cat_id_array = mysql_fetch_array($cat_id_result, MYSQL_ASSOC);
    $cat_id = $cat_id_array[cat_id];

    $temp = "";

    //if there were additional fields associated with the category then add them to cat_info
    if ($unique_info_id_counter!=0){

        for ($i = 0; $i < $numInfo; $i++) {
            if ($related[$i] != null) {
                $temp .= " (" . $cat_id . "," . $related[$i] . "),";
            }
        }

        for ($i = 0; $i < $unique_info_id_counter; $i++) {
            $temp .= " (" . $cat_id . "," . $info_id_array[$i] . "),";
        }

        $temp = substr_replace($temp, "", (strlen($temp) - 1), strlen($temp));
        $cat_info_query = "INSERT INTO cat_info (cat_id, info_id) VALUES $temp";
        $cat_info_result = mysql_query($cat_info_query) or die("Query failed: " . mysql_error());
    }
    $_POST['newCatSubmitted'] = "false";
    $_GET['category'] = $catname;

    mysql_free_result($cat_id_result);

}

if (!isset($_GET['ext']) || ($_GET['ext'] == ''))
    $ext = 0;
else
    $ext = intval($_GET['ext']);

if (!isset($_GET['intpoint']) || ($_GET['intpoint'] == ''))
    $intpoint = 0;
else
    $intpoint = intval($_GET['intpoint']);

if (isset($_GET['numMaterials']))
    $numMaterials = intval($_GET['numMaterials']);
else
    $numMaterials = 0;


// Optiontransfer is the author selection windows.
?>
<script language="JavaScript" src="../calendar.js"></script>
<script language="JavaScript" type="text/JavaScript">

    window.name="add_publication.php";
var venueHelp=
    "Where the paper was published -- specific journal, conference, "
    + "workshop, etc. If many of the database papers are in the same venue, "
    + "you can create a single &quot;label&quot; for that venue, to specify "
    + "name of "
    + "the venue, location, date, editors and other common information. "
    + "You will then be able to use and re-use that information.";

var categoryHelp=
    "Category describes the type of document that you are submitting to the "
    + "site. For examplethis could be a journal entry, a book chapter, etc."
    + "<br><br>"
    + "Please use the drop down menu to select an appropriate category to "
    + "classify your paper. If you cannot find an appropriate category you "
    + "can use the Add Category link to update the category listings."
    + "<br><br>"
    + "Clicking Add Category will bring up another window that will allow "
    + "you to specifiy a new category by entering the Category Name and then "
    + "selecting related fields.";

var titleHelp=
    "Title should contain the title given to your document.<br><br>"
    +  "Please enter the title of your document in the field provided.";

var authorsHelp=
    "This field is to store the author(s) of your document in the database."
    + "<br><br>"
    + "To use this field select the author(s) of your document from the "
    + "listbox. You can select multiple authors by holding down the control "
    + "key and clicking. If you do not see the name of the author(s) of the "
    + "document listed in the listbox then you must add them with the Add "
    + "Author button.";

var abstractHelp=
    "Abstract is an area for you to provide an abstract of the document you "
    + "are submitting.<br><br>"
    + "To do this enter a plain text abstract for your paper in the field "
    + "provided. HTML tags can be used.";

var extraInfoHelp=
    "Specify auxiliary information, to help classify this publication. "
    + "Eg, &quot;with student&quot; or &quot;best paper&quot;, etc. Note "
    + "that, by default, this information will NOT be shown when this "
    + "document is presented. Separate using semicolons(;). You can see "
    + "previously enterred entries by clicking the &quot;Select from a list "
    + "of previously used information options&quot;, and just check off "
    + "those that apply to the current publication.";

var externalPtrHelp=
    "These can be used to connect this publication to an outside source "
    + "such as a website or a publication that is not in the current "
    + "database."
    + "<ul>"
    + "<li>The &quot;Pointer Type&quot; is the kind of object you are linking "
    + "with. eg website or publication,</li>"
    + "<li>The &quot;Title of link&quot; would be the name of the website, or "
    + "the nameof the publication</li>"
    + "<li>The &quot;http://&quot; would be where you would enter the url."
    + "</li>"
    + "</ul>";

var internalPtrHelp=
    "These can be used to connect this publication with another publication "
    + "inside the database.";


var keywordsHelp=
    "Keywords is a field where you can enter keywords that will be used to "
    + "possibly locate your paper by others searching the database. You may "
    + "want to enter multiple terms that are associated with your document. "
    + "Examples may include words like: medical imaging; robotics; data "
    + "mining.<br><br>"
    + "Please enter keywords used to describe your paper, each keyword should "
    + "be seperated by a semicolon.";

var datePublishedHelp=
    "Specifies the date that this document was published. If you have "
    + "specified a publication_venue that include a date, then this date "
    + "field will already be enterred.";

function dataKeep(tab) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
        var element = document.forms["pubForm"].elements[i];
		if ((element.type != "submit") && (element.type != "reset")
            && (element.value != "") && (element.value != null)) {
			if (info_counter > 0) {
                temp_qs = temp_qs + "&";
			}

			if (element.name == "authors[]") {
				author_array = document.forms["pubForm"].elements['authors[]'];
				var author_list = "";
				var author_count = 0;

				for (j = 0; j < author_array.length; j++) {
                    if (author_array[j].selected) {
                        if (author_count > 0) {
                            author_list = author_list + "&";
                        }
                        author_list = author_list + "authors["
                            + author_count + "]=" + author_array[j].value;
                        author_count++;
                    }
				}

				temp_qs = temp_qs + author_list;
			}
			else if(element.name == "comments")
				temp_qs = temp_qs + element.name + "=" + element.value.replace("\"","'");

			else if(element.name == "nopaper"){
                if(element.checked)
                    temp_qs = temp_qs + element.name + "=" + element.value;
			}
			else if(element.name == "ext"){
                if(tab == "addext")
                    temp_qs = temp_qs + element.name + "=" + "<? echo ($ext+1); ?>";
                else if(tab == "remext")
                    temp_qs = temp_qs + element.name + "=" + "<? echo ($ext-1); ?>";
                else
                    temp_qs = temp_qs + element.name + "=" + "<? echo $ext; ?>";
			}
			else if(element.name == "intpoint"){
                if(tab == "addint")
                    temp_qs = temp_qs + element.name + "=" + "<? echo ($intpoint+1); ?>";
                else if(tab == "remint")
                    temp_qs = temp_qs + element.name + "=" + "<? echo ($intpoint-1); ?>";
                else
                    temp_qs = temp_qs + element.name + "=" + "<? echo $intpoint; ?>";
			}
			else if(element.name == "numMaterials"){
                if(tab == "addnum")
                    temp_qs = temp_qs + element.name + "=" + "<? echo ($numMaterials+1); ?>";
                else if(tab == "remnum")
                    temp_qs = temp_qs + element.name + "=" + "<? echo ($numMaterials-1); ?>";
            }
			else
				temp_qs = temp_qs + element.name + "=" + element.value;

			info_counter++;
		}
	}
	if((tab == "addnum")||(tab == "remnum"))
		temp_qs = temp_qs + "&#" + "STEP2";
	if(((tab == "addext")||(tab == "remext"))||((tab == "addint")||(tab == "remint")))
		temp_qs = temp_qs + "&#" + "pointers";
	else if(tab != "none")
		temp_qs = temp_qs + "&#" + tab;
	temp_qs = temp_qs.replace("\"", "?");
	temp_qs = temp_qs.replace(" ", "%20");
	location.href = "http://" + "<? echo $_SERVER['HTTP_HOST']; echo $_SERVER['PHP_SELF']; ?>?" + temp_qs;
}

function dataKeepPopup(page) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
        var element = document.forms["pubForm"].elements[i];
		if ((element.type != "submit") && (element.type != "reset")
            && (element.type != "button") && (element.type != "radio")
            && (element.value != "") && (element.value != null)) {
			if (info_counter > 0) {
                temp_qs = temp_qs + "&";
			}
			if (element.name == "authors[]") {
				author_array = document.forms["pubForm"].elements['authors[]'];
				var author_list = "";
				var author_count = 0;

				for (j = 0; j < author_array.length; j++) {
					if (author_array[j].selected == 1) {
						if (author_count > 0) {
							author_list = author_list + "&";
						}
						author_list = author_list + "authors[" + j + "]="
                            + author_array[j].value;
						author_count++;
					}
				}

				temp_qs = temp_qs + author_list;
			}
			else if(element.name == "comments") {
                temp_qs = temp_qs + element.name
                    + "=" + element.value.replace("\"","'");
            }
            else
                temp_qs = temp_qs + element.name + "="
                    + element.value;

            info_counter++;
        }
	}

	if (page == "add_category.php") {
		temp_qs = temp_qs + "&newFields=0";
	}
    temp_qs = temp_qs + "&new=false";

    var term_url;
    if (page.indexOf('?') > 0) {
        temp_url = "./" + page + "&" + temp_qs;
    }
    else {
        temp_url = "./" + page + "?" + temp_qs;
    }
	temp_url = temp_url.replace(" ", "%20");
	temp_url = temp_url.replace("\"", "'");
	if(page == "keywords.php")
		window.open(temp_url, 'Add', 'width=860,height=600,scrollbars=yes,resizable=yes');
	else
		window.open(temp_url, 'Add', 'width=700,height=405,scrollbars=yes,resizable=yes');
}

function dataKeepPopupWithID(page, id) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
        var element = document.forms["pubForm"].elements[i];
		if ((element.value != "") &&
            (element.value != null)) {
			if (info_counter > 0) {
                temp_qs = temp_qs + "&";
			}
			if (element.name == "authors[]") {
				author_array = document.forms["pubForm"].elements['authors[]'];
				var author_list = "";
				var author_count = 0;

				for (j = 0; j < author_array.length; j++) {

                    if (author_count > 0) {
                        author_list = author_list + "&";
                    }
                    author_list = author_list + "authors[" + j + "]=" + author_array[j].value;
                    author_count++;

				}

				temp_qs = temp_qs + author_list;
			}
			else {
				if(element.name == "comments"){
					temp_qs = temp_qs + element.name + "=" + element.value.replace("\"","'");
				}
				else
                    temp_qs = temp_qs + element.name + "=" + element.value;
			}

			info_counter++;
		}
	}

	if (page == "add_category.php") {
		temp_qs = temp_qs + "&newFields=0";
	}
	temp_qs = temp_qs.replace("\"", "?");

	var temp_url = "./" + page + "?" + temp_qs + "&pub_id=" + id;
	temp_url = temp_url.replace(" ", "%20");
	window.open(temp_url, 'Add');
}

function verify(num) {
	if (document.forms["pubForm"].elements["category"].value == "") {
        alert("Please select a category for the publication.");
        return false;
	}
	else if (document.forms["pubForm"].elements["title"].value == "") {
        alert("Please enter a title for the publication.");
        return false;
	}
	else if(document.forms["pubForm"].elements["nopaper"].value == "false"){
        if (document.forms["pubForm"].elements["uploadpaper"].value == "") {
            alert("Please choose a paper to upload or select \"No Paper\".");
            return false;
        }
	}
	else if (document.forms["pubForm"].elements["selected_authors"].value == "") {
        alert("Please select the author(s) of this publication.");
        return false;
	}
	else if (document.forms["pubForm"].elements["abstract"].value == "") {
        alert("Please enter the abstract for this publication.");
        return false;
	}
	else if (document.forms["pubForm"].elements["keywords"].value == "") {
        alert("Please enter the keywords for this publication.");
        return false;
	}
	else
        return true;

	alert("Error: Verifying");
	return false;

}

function resetAll() {
	location.href="./add_publication.php";
}
function refresher() { window.location.reload(true);}

</script>

<?

$form = new HTML_QuickForm('pubForm', 'post', "./add_publication.php?",
                           "add_publication.php");
if ($edit) {
    $form->addElement('hidden', 'pub_id', $_GET['pub_id']);
}

// Venue
if (isset($_GET['category_id']) && ($_GET['category_id'] != '')) {
    $category = new pdCategory();
    $category->dbLoad($db, $_GET['category_id']);
}

if (isset($_GET['venue_id']) && ($_GET['venue_id'] != "")
    && ($_GET['venue_id'] != -1) && ($_GET['venue_id'] != -2)) {

    $venue_id = $_GET['venue_id'];

    $venue = new pdVenue();
    $venue->dbLoad($db, $venue_id);

    if (($category->category == "")
        || ($category->category == "In Conference")
        || ($category->category == "In Workshop")
        || ($category->category == "In Journal")) {
        if ($venue->type == "Conference")
            $category->category = "In Conference";
        else if ($venue->type == "Workshop")
            $category->category = "In Workshop";
        else if ($venue->type == "Journal")
            $category->category = "In Journal";
    }

    if(($venue->date != NULL) && ($venue->date != "")) {
        $date = split("-", $venue->date);
        $year = $date[0];
        $month = $date[1];
        $day = $date[2];
    }
}

$options = array(''   => '--- Select a Venue ---',
                 '-1' => 'No Venue',
                 '-2' => 'Unique Venue');
$venue_list = new pdVenueList();
$venue_list->dbLoad($db);
assert('is_array($venue_list->list)');
foreach ($venue_list->list as $v) {
    $options[$v->venue_id] = $v->title;
}
$form->addElement('select', 'venue_id', null, $options,
                  array('onChange' => "javascript:dataKeep('Start');"));
$form->addElement('button', 'add_venue', 'Add Venue',
                  'onClick="dataKeepPopup(\'add_venue.php?popup=true\');"');


// Category
unset($options);
$options = array('' => '--- Please Select a Category ---');
$category_list = new pdCatList();
$category_list->dbLoad($db);
assert('is_array($category_list->list)');
foreach ($category_list->list as $cat) {
    $options[$cat->cat_id] = $cat->category;
}
$form->addElement('select', 'category_id', null, $options,
                  array('onChange' => "javascript:dataKeep('Start');"));
$form->addElement('button', 'add_category', 'Add Category',
                  'onClick="dataKeepPopup(\'add_category.php\');"');

if (isset($category) && is_object($category) && is_array($category->info)) {
    foreach ($category->info as $info_id => $name) {
        $form->addElement('text', $name, null,
                          array('size' => 70, 'maxlength' => 250));
    }
}

// title
$form->addElement('text', 'title', null,
                  array('size' => 60, 'maxlength' => 250));


// Authors
if (!isset($_GET['num_authors'])) {
    $num_authors = 1;
}
else {
    $num_authors = intval($_GET['num_authors']);
}

$form->addElement('hidden', 'num_authors', $num_authors);
$auth_list = new pdAuthorList();
$auth_list->dbLoad($db);
assert('is_array($auth_list->list)');
unset($options);
foreach ($auth_list->list as $auth) {
    $options[$auth->author_id] = $auth->name;
}

//for ($i = 1; $i <= $num_authors; $i++) {
//    $form->addElement('combobox', 'author' . $i, null, $options);
//}

$authSelect =& $form->addElement('advmultiselect', 'authors', null, $options,
                                 array('class' => 'pool',
                                       'style' => 'width:150px;'),
                                 SORT_ASC);
$authSelect->setLabel(array('Authors:', 'Selected', 'Available'));

$authSelect->setButtonAttributes('add'     , array('value' => '<<',
                                                   'class' => 'inputCommand'));
$authSelect->setButtonAttributes('remove'  , array('value' => '>>',
                                                   'class' => 'inputCommand'));
$authSelect->setButtonAttributes('moveup'  , array('class' => 'inputCommand'));
$authSelect->setButtonAttributes('movedown', array('class' => 'inputCommand'));

// template for a dual multi-select element shape
$template =
    '<table{class}>'
    . '<!-- BEGIN label_2 --><tr><th>{label_2}</th><!-- END label_2 -->'
    . '<!-- BEGIN label_3 --><th>&nbsp;</th><th>{label_3}</th></tr><!-- END label_3 -->'
    . '<tr>'
    . '  <td>{selected}</td>'
    . '  <td align="center">'
    . '    {add}<br />{remove}<br /><br />{moveup}<br />{movedown}'
    . '  </td>'
    . '  <td>{unselected}</td>'
    . '</tr>'
    . '</table>'
    . '{javascript}';
$authSelect->setElementTemplate($template);

$form->addElement('button', 'add_author', 'Add Author',
                  'onClick="dataKeepPopup(\'add_author.php?popup=true\');"');

$form->addElement('textarea', 'abstract', null,
                  array('cols' => 60, 'rows' => 10));

if (isset($_GET['venue_id']) && ($_GET['venue_id'] == -2))
    $form->addElement('textarea', 'venue_name', null,
                      array('cols' => 60, 'rows' => 5));
$form->addElement('textarea', 'extra_info', null,
                  array('cols' => 60, 'rows' => 5));
$form->addElement('button', 'extra_info_select',
                  'Select from a list of previously used information options',
                  'onClick="dataKeepPopup(\'extra_info.php\');"');

$form->addElement('hidden', 'ext', $ext);

if ($ext > 0)
    for ($e = 1; $e <= $ext; $e++) {
        $form->addElement('text', 'extname' . $e, null,
                          array('size' => 15, 'maxlength' => 250));
        $form->addElement('text', 'extvalue' . $e, null,
                          array('size' => 18, 'maxlength' => 250));
        $form->addElement('text', 'extlink' . $e, null,
                          array('size' => 25, 'maxlength' => 250));
    }

$form->addElement('hidden', 'intpoint', $intpoint);

if ($intpoint > 0) {
    $pub_list = new pdPubList($db);
    unset($options);
    $options[''] = '--- Link to a publication --';
    foreach ($pub_list->list as $pub) {
        if (strlen($pub->title) > 70)
            $options[$pub->pub_id] = substr($pub->title, 0, 67) . '...';
        else
            $options[$pub->pub_id] = $pub->title;
    }
    for ($e = 1; $e <= $intpoint; $e++) {
        $form->addElement('select', 'intpointer' . $e, null, $options);
    }
}

$form->addElement('text', 'keywords', null,
                  array('size' => 55, 'maxlength' => 250));

$form->addElement('text', 'date_published', null,
                  array('size' => 10, 'maxlength' => 10));

$form->addElement('radio', 'nopaper', null, null, 'false');
$form->addElement('radio', 'nopaper', null, 'no paper at this time', 'true');
$form->addElement('file', 'uploadpaper', null,
                  array('size' => 45, 'maxlength' => 250));
$form->addElement('hidden', 'numMaterials', $numMaterials);

if ($numMaterials > 0) {
    for ($i = 1; $i <= $numMaterials; $i++) {
        $form->addElement('text', 'type' . $i, null,
                          array('size' => 17, 'maxlength' => 250));
        $form->addElement('text', 'uploadadditional' . $i, null,
                          array('size' => 50, 'maxlength' => 250));
    }
}

$form->addElement('submit', 'Save', 'Add Publication');
$form->addElement('reset', 'Clear', 'Clear');

//
//
//
$form->setDefaults($_GET);
if ($numMaterials > 0) {
    for ($i = 1; $i <= $numMaterials; $i++) {
        if (!isset($_GET['type' . $i]) || ($_GET['type' . $i] = '')) {
            $materials['type' . $i] = 'Additional Material ' . $i;
        }
    }
    $form->setDefaults($materials);
}

if ($ext > 0) {
    for ($e = 1; $e <= $ext; $e++) {
        if (!isset($_GET['extname'.$e]) || $_GET['extname'.$e] == '')
            $defaults['extname'.$e] = "Pointer Type";
        if (!isset($_GET['extvalue'.$e]) || $_GET['extvalue'.$e] == '')
            $defaults['extvalue'.$e] = "http://";
        if (!isset($_GET['extlink'.$e]) || $_GET['extlink'.$e] == '')
            $defaults['extlink'.$e] = "Title of link";
        $form->setDefaults($defaults);
    }
}

$renderer =& new HTML_QuickForm_Renderer_QuickHtml();
$form->accept($renderer);

$tableAttrs = array('width' => '100%',
                    'border' => '0',
                    'cellpadding' => '6',
                    'cellspacing' => '0');
$table = new HTML_Table($tableAttrs);
$table->setAutoGrow(true);

$table->addRow(array('<hr/>'), array('colspan' => 2));
$table->addRow(array('Step 1:'));
$table->addRow(array(helpTooltip('Publication Venue', 'venueHelp') . ':',
                     $renderer->elementToHtml('venue_id')
                     . ' ' . $renderer->elementToHtml('add_venue')));
$table->addRow(array(helpTooltip('Category', 'categoryHelp') . ':',
                     $renderer->elementToHtml('category_id')
                     . ' ' . $renderer->elementToHtml('add_category')));
$table->addRow(array(helpTooltip('Title', 'titleHelp') . ':',
                                 $renderer->elementToHtml('title')));
$table->addRow(array(helpTooltip('Author(s)', 'authorsHelp') . ':',
                     $renderer->elementToHtml('authors')
                     . $renderer->elementToHtml('add_author')));
$table->addRow(array(helpTooltip('Abstract', 'abstractHelp')
                     . ':<br/><div id="small">HTML Enabled</div>',
                     $renderer->elementToHtml('abstract')));

// Show venue info
if (isset($venue) && is_object($venue)) {
    $cell1 = '';
    $cell2 = '';

    if ($venue->type != '')
        $cell1 .= $venue->type;

    if ($venue->url != '')
        $cell2 .= '<a href="' . $venue->url . '" target="_blank">';

    if ($venue->name != '')
        $cell2 .= $venue->name;

    if ($venue->url != '')
        $cell2 .= '</a>';

    $table->addRow(array($cell1 . ':', $cell2));

	if($venue->type == "Conference")
		$cell1 = 'Location:';
	else if($venue->type == "Journal")
		$cell1 = 'Publisher:';
	else if($venue->type == "Workshop")
		$cell1 = 'Associated Conference:';

    $table->addRow(array($cell1, $venue->data));
}

if (isset($_GET['venue_id']) && ($_GET['venue_id'] == -2)) {
    $table->addRow(array('Unique Venue:'
                         . '<br/><div id="small">HTML Enabled</div>',
                         $renderer->elementToHtml('venue_name')));
}

$table->addRow(array(helpTooltip('Extra Information', 'extraInfoHelp') . ':'
                     . '<br/><div id="small">optional</div>',
                     $renderer->elementToHtml('extra_info')
                     . $renderer->elementToHtml('extra_info_select')));

// External Pointers
if ($ext == 0) {
    $table->addRow(array(helpTooltip('External Pointers', 'externalPtrHelp')
                         . ':<br/><div id="small">optional</div>',
                         '<a href="javascript:dataKeep(\'addext\');">'
                         . 'Add an external pointer</a>'));
}
else {
    for ($e = 1; $e <= $ext; $e++) {
        $cell = '';
        if ($e == 1) {
            $cell = 'External Pointers:<br/><div id="small">optional</div>';
        }

        $table->addRow(array($cell,
                             $renderer->elementToHtml('extname'.$e)
                             . ' ' . $renderer->elementToHtml('extvalue'.$e)
                             . ' ' . $renderer->elementToHtml('extlink'.$e)));

    }
    $table->addRow(array('',
                         '<a href="javascript:dataKeep(\'addext\');">'
                         . 'Add another external pointer</a>'
                         . '&nbsp;&nbsp;'
                         . '<a href="javascript:dataKeep(\'remext\');">'
                         . 'Remove the above pointer</a>'));
}

// Internal Pointers
if ($intpoint == 0) {
    $table->addRow(array(helpTooltip('Internal Pointers', 'internalPtrHelp')
                         . ':<br/><div id="small">optional</div>',
                         '<a href="javascript:dataKeep(\'addint\');">'
                         . 'Add an internal pointer</a>'));
}
else {
    for ($e = 1; $e <= $intpoint; $e++) {
        $cell = '';
        if ($e == 1)
            $cell = helpTooltip('Internal Pointers', 'internalPtrHelp')
                . ':<br/><div id="small">optional</div>';
        $table->addRow(array($cell,
                             $renderer->elementToHtml('intpointer' . $e)));
    }
    $table->addRow(array('',
                         '<a href="javascript:dataKeep(\'addint\');">'
                         . 'Add another internal pointer</a>'
                         . '&nbsp;&nbsp;'
                         . '<a href="javascript:dataKeep(\'remint\');">'
                         . 'Remove the above pointer</a>'));
}

$table->addRow(array(helpTooltip('Keywords', 'keywordsHelp') . ':',
                     $renderer->elementToHtml('keywords')
                   . ' <div id="small">separate using semicolon (;)</div>'));

// Additional Information
if (isset($category) && is_object($category) && is_array($category->info)) {
    foreach ($category->info as $info_id => $name) {
        $table->addRow(array($name . ':', $renderer->elementToHtml($name)));
    }
}

$table->addRow(array(helpTooltip('Date Published', 'datePublishedHelp') . ':',
                     $renderer->elementToHtml('date_published')
                     . '<a href="javascript:doNothing()" '
                     . 'onClick="setDateField('
                     . 'document.pubForm.date_published);'
                     . 'top.newWin=window.open(\'../calendar.html\','
                     . '\'cal\',\'dependent=yes,width=230,height=250,'
                     . 'screenX=200,screenY=300,titlebar=yes\')">'
                     . '<img src="../calendar.gif" border=0></a> '
                     . '(yyyy-mm-dd) '
                   ));

$table->addRow(array('<hr/>'), array('colspan' => 2));
$table->addRow(array('Step 2:'));
$table->addRow(array('Paper:',
                     $renderer->elementToHtml('nopaper', 'false')
                     . ' ' . $renderer->elementToHtml('uploadpaper')));
$table->addRow(array('', $renderer->elementToHtml('nopaper', 'true')));
if ($numMaterials > 0) {
    $table->addRow(array('Additional Materials:'));

    for ($i = 1; $i <= $numMaterials; $i++) {
        $table->addRow(array($renderer->elementToHtml('type' . $i),
                             ':' . $renderer->elementToHtml('uploadadditional' . $i)));
    }
    $table->addRow(array('',
                         '<a href="javascript:dataKeep(\'addnum\');">'
                         . 'Add other material</a>'
                         . ' <a href="javascript:dataKeep(\'remnum\');">'
                         . 'Remove this material</a>'));
}
else {
    $table->addRow(array('',
                         '<a href="javascript:dataKeep(\'addnum\');">'
                         . 'Add other material</a>'));
}

$table->addRow(array('<hr/>'), array('colspan' => 2));
$table->addRow(array('',
                     $renderer->elementToHtml('Save')
                     . ' ' . $renderer->elementToHtml('Clear')));

$table->updateColAttributes(0, array('id' => 'emph', 'width' => '25%'));

// emphasize the 'step' cells
$table->updateCellAttributes(1, 0, array('id' => 'emph_large'));
$table->updateCellAttributes(13, 0, array('id' => 'emph_large'));

echo '<body>'
. '<a name="Start"></a>';
pageHeader();
navMenu('add_publication');
echo "<div id='content'>\n";

echo '<h3>';
if (isset($_GET['edit']))
    echo 'Edit';
else
    echo 'Add';
echo 'Publication</h3>';

if(!$edit) {
    echo 'Adding a publication takes two steps:<br>
        1. Fill in the appropriate fields<br>
        2. Upload the paper and any additional materials';
}

echo $renderer->toHtml(($table->toHtml())) . '</div>';

?>

<form name="pubForm2" action="add_publication_db.php" method="POST"
    enctype="multipart/form-data">

    <?
if ($edit) {
    echo "<input type=\"hidden\" name=\"pub_id\" value=\"". $pub_id
    . "\"> \n";
}
?>

<table width="790" border="0" cellspacing="0" cellpadding="6">

<!-- Additional Materials -->
<?
if($numMaterials > 0) {
    echo "<tr><td width=\"25%\" valign=\"top\">"
        . "<a href=\"javascript:help('additional_materials');\">"
        . "<font color=\"#000000\" size=\"2\""
        . " face=\"Arial, Helvetica, sans-serif\">"
        . "<b>Additional Materials: </b></a></font></td>"
        . "</tr>\n";
}

for ($i = 0; $i < $numMaterials; $i++) {
    echo "<tr>";

    if ($i < $dbMaterials) {
        $add_info_array = get_additional_material($pub_id, $i);
        echo "<td width=\"25%\">"
            . "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\" "
            . "color=\"#990000\"><b>";
        if($add_info_array[1] != "")
            echo $add_info_array[1];
        else
            echo "Additional Material " . ($i+1);
        echo ": </b></font></td>"
            . "<td width=\"75%\">";
        echo $add_info_array[0];
        echo "&nbsp; &nbsp; &nbsp; "
            . "<a href=\"javascript:\" "
            . " onClick=\"javascript:window.open('";
        echo "delete.php?info=" . $pub_id . "/" . $i . "&confirm=false"
            ."','deleteadd','width=200,height=200,directories=no,location=no,"
            . "menubar=no,scrollbars=no,status=no,toolbar=no,"
            . "resizable=no')\">"
            . "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\">"
            . "Delete</font></a> </td>";
    }
    else {
        echo "<td width=\"25%\"><input type=\"text\" name=\"type" . $i
            . "\" size=\"17\" maxlength=\"250\" "
            . "value=\"Additional Material ";
        echo $i+1;
        echo "\"><b>:</b></td>"
            . "<td width=\"75%\">"
            . "<input type=\"file\" name=\"uploadadditional" . $i
            . "\" size=\"50\" maxlength=\"250\"></td>"
            . "</tr>\n";
    }

}
?>
<tr><td></td>
<td>
<?
if($numMaterials == "") {
    $numMaterials = 0;
}

echo "<input type=\"hidden\" name=\"numMaterials\" value=\"" . $numMaterials
. "\">"
. "&nbsp;&nbsp;"
. "<a href=\"javascript:dataKeep('addnum');\">"
. "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\">"
. "Add other material</a>";

if ($numMaterials > 0) {
    echo "&nbsp;&nbsp;<a href=\"javascript:dataKeep('remnum');\">"
        . "Remove this material</a>";
}
echo "</font></td></tr>"
. "<tr><td colspan=\"2\"><hr></td></tr>";
?>

</table>
</form>

<?

echo '</div>';

$db->close();

pageFooter();

echo '<script language="JavaScript" type="text/javascript" src="../wz_tooltip.js"></script>';

echo "</body>\n</html>\n";

?>
