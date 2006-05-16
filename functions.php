<?php

/* functions.php
   I rewrote lib_functions because it was way too buggy.
   These functions are used throughout the pages and are here
   to save on time and complexity. Each function is pretty
   straight forward.
*/

require "lib_dbfunctions.php";

$relative_files_path = "uploaded_files/";
$absolute_files_path = FS_PATH . $relative_files_path;


/* isValid
 Checks to see if the given string is nothing but
 letters or numbers and is shorter then a certain
 length. */
function isValid($string){
	for($a = 0; $a < strlen($string); $a++){
		$char = substr($string,$a,1);
		$isValid = false;
		// Numbers 0-9
		for($b = 48; $b <= 57; $b++)
			if($char == chr($b))
				$isValid = true;
		//Uppercase A to Z
		if(!$isValid)
			for($b = 65; $b <= 90; $b++)
				if($char == chr($b))
					$isValid = true;
		//Lowercase a to z
		if(!$isValid)
			for($b = 97; $b <= 122; $b++)
				if($char == chr($b))
					$isValid = true;
		if(!$isValid)
			return errorMessage();
	}
	return "";
}

function quote_smart($value) {
	// Stripslashes
	if (get_magic_quotes_gpc()) {
		$value = stripslashes($value);
	}
	// Quote if not a number or a numeric string
	if (!is_numeric($value) || $value[0] == '0') {
		$value = "'" . mysql_real_escape_string($value) . "'";
	}
	return $value;
}

function errorMessage(){
	echo "<BR><BR>";
	echo "<h4>There was a problem handling your request.<BR>Please go back and try again.</h4>";
	echo "<BR>";
	back_button();
	exit;
}

function quickForm($title, $style, $name, $value){
	$value = stripslashes($value);
	echo "<tr> \n";
	echo "<td width=\"25%\" class=\"".$style."\">".$title.": </td> \n";
	echo "<td width=\"75%\" class=\"".$style."\"> \n";
	echo "<input type=\"text\" name=\"" . $name
        . "\" size=\"60\" maxlength=\"250\" value=\"".$$value."\"> \n";
	echo "</td></tr> \n";
}

// Handy back button usually used at the end of pages.
function back_button()
{
	echo "<form> \n";
	echo "<input type=\"button\" value=\"Back\" onclick=\"history.back()\"> \n";
	echo "</form> \n";
}

function generate_select_body ($start, $end, $compare) {
    for ($i = $start; $i <= $end; $i++) {
	echo "  <option value='$i' ";
	if ($compare == $i) echo "selected";
	echo "> $i </option> \n";
    }
}

function generate_select($name, $start, $end, $compare) {
    echo "<select name='$name'> \n";
    generate_select_body ($start, $end, $compare);
    echo "</select> \n";
}

function generate_select_date($name, $start, $end, $compare = NULL) {
    echo "<select name='$name'> \n";
	echo " <option value='--'";
	if($compare == NULL) echo "selected";
	echo "> -- </option>";
	if($name == "year"){
	for ($i = $end; $i >= $start; $i--) {
		if($compare == $i)
			echo "  <option value='$i' selected> $i </option> \n";
		else
			echo "  <option value='$i'> $i </option> \n";
		}
	}
	else {
	for ($i = $start; $i <= $end; $i++) {
		if($compare == $i)
			echo "  <option value='$i' selected> $i </option> \n";
		else
			echo "  <option value='$i'> $i </option> \n";
		}
	}
    echo "</select> \n";
}

function generate_select_month($name, $start, $end, $compare = NULL) {
    echo "<select name='$name'> \n";
	echo " <option value='--'";
	if($compare == NULL) echo "selected";
	echo "> -- </option>";
    for ($i = $start; $i <= $end; $i++) {
	echo "  <option value='$i' ";
	if ($compare == $i) echo "selected";
	echo "> " . date ("F", mktime (0,0,0,$i)) . " </option> \n";
    }
    echo "</select> \n";
}


function get_num_db_materials ($pubID) {
    global $db;

    if ($db == NULL) {
	return;
    }

    $rval = NULL;
    $count = 0;

	$sql = "select * from pub_add where pub_id = $pubID";
    $result = query_db($sql);
	while(mysql_fetch_array($result, MYSQL_ASSOC))
    	$count++;

    return $count;
}


function get_publication_info ($pubID) {
    global $db;


    $sql = "select * from publication where pub_id = $pubID";
    $result = query_db($sql);
	$line = mysql_fetch_array($result, MYSQL_ASSOC);

    // Make sure there's one and only one publication with this ID
    if ($line == NULL)
		echo "Error: There is no publication with this ID!";

	return $line;
}

function get_category ($pubID) {
    global $db;
    //if ($db == NULL) {
	//	return;
    //}

    $sql = "select B.category, B.cat_id from pub_cat A, category B
		WHERE pub_id = $pubID
		AND A.cat_id = B.cat_id";
    $result = query_db($sql);
	$line = mysql_fetch_array($result, MYSQL_ASSOC);

    if ($line == NULL)
		echo "Error: Couldn't locate category for paper.";

    return $line;
}


function get_authors ($pubID) {
    global $db;
    if ($db == NULL) {
	return;
    }
	$rval = NULL;
    $sql = "select B.name from pub_author A, author B
		WHERE pub_id = $pubID
		AND A.author_id = B.author_id";
    $result = query_db($sql);

    // Use a hash to return the existence of a author with a paper.
    // This makes checking in the form very easy.
    while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	$rval[$row['name']] = 1;
    }
    if ($rval == NULL)
		echo "Error: Couldn't locate authors for paper.";
    return $rval;
}


function get_info_field_value ($pubID, $catID, $infoID) {
	global $db;
    if ($db == NULL) {
	return;
    }

    $rval = NULL;

    $sql = "SELECT value FROM pub_cat_info
		WHERE pub_id = $pubID
		AND cat_id = $catID
		AND info_id = $infoID";
    $result = query_db($sql);
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	return $line['value'];
}


function get_info_id ($catID, $infoName) {
    global $db;

    if ($db == NULL) {
	return;
    }

    $rval = NULL;

    $info_id_get = "SELECT info_id FROM info WHERE name = \"$infoName\"";
    $info_id_result = query_db($info_id_get);
	$info_id_line = mysql_fetch_array($info_id_result, MYSQL_ASSOC);

	return $info_id_line['info_id'];
}

function removematerial ($pubID, $i) {
    global $db;

 	if ($db == NULL) {

	return "Did not delete succesfully, DB is NULL";
    }

    $rval = NULL;

    $sql = "select B.location, B.add_id from pub_add A, additional_info B
		WHERE A.pub_id = $pubID
		AND A.add_id = B.add_id
		ORDER BY B.add_id";
    $result = query_db($sql);
	$row = mysql_fetch_array($result, MYSQL_ASSOC);

    if ($row == NULL) {
	echo "Error: Couldn't locate additional material for pub $pubID and item number $i.";
    }

	$location = $row['location'];

	$sql = "SELECT * FROM additional_info WHERE location = \"$location\"";
    $result = query_db($sql);
	$value = mysql_fetch_array($result, MYSQL_ASSOC);

	$add_id = $value['add_id'];


	$pub_query = "SELECT * FROM publication WHERE pub_id=$pubID";
	$pub_result = query_db($pub_query);

	$query = "DELETE FROM additional_info WHERE add_id = $add_id";
	$result = query_db($query);

	$query = "DELETE FROM pub_add WHERE add_id = $add_id AND pub_id = $pubID";
	$result = query_db($query);

	system("rm -rf " . FS_PATH . $location);
	$location = split("/",$location);
	$name = $location[3];
	return "Deleted $name Succesfully";

}
function get_additional_material ($pubID, $i) {
    global $db;
    if ($db == NULL) {
	return "Error";
    }

    $rval = NULL;

    $sql = "SELECT B.location, B.add_id, B.type FROM pub_add A, additional_info B <br>
		WHERE A.pub_id = $pubID
		AND A.add_id = B.add_id
		ORDER BY B.add_id";
    $res = query_db($sql);

	$b = 0;
	while($row = mysql_fetch_array($res, MYSQL_ASSOC))
	{
		if($b == $i){
			$temp_string = $row['location'];
			$temp_string2 = split("/additional_",$temp_string);
			$temparray[0] = $temp_string2[1];
			$temparray[1] = $row['type'];
			}
	$b++;
	}
    return $temparray;
}

function get_venue_info($venue) {
 $output = "";
 $temp_array = split("venue_id:<", $venue);
	 if($temp_array[1] != ""){
		$temp_array = split(">", $temp_array[1]);
		$venue_id = $temp_array[0];
		$venue_query = "SELECT * FROM venue WHERE venue_id=$venue_id";
		$venue_result = query_db($venue_query);
		$venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC);
		$venue_name = $venue_line[name];
		$venue_url = $venue_line[url];
		$venue_type = $venue_line[type];
		$venue_data = $venue_line[data];
		$output .= "<b>".$venue_type.":&nbsp;</b>";
		if($venue_url != "")
			$output .= " <a href=\"".$venue_url."\" target=\"_blank\">";
		$output .= $venue_name;
		if($venue_url != "")
			$output .= "</a>";
		if($venue_data != ""){
			$output .= "</td></tr><tr><td>";
			if($venue_type == "Conference")
				$output .= "<b>Location:&nbsp;</b>";
			else if($venue_type == "Journal")
				$output .= "<b>Publisher:&nbsp;</b>";
			else if($venue_type == "Workshop")
				$output .= "<b>Associated Conference:&nbsp;</b>";
			$output .= $venue_data;
		}
	}
	else $output .= "<b>Publication Venue:</b>".$venue;
	return $output;
}

function htmlHeader($title) {
    print "<html>\n"
        . "<head>\n"
        . "<title>". $title . "</title>\n"
        . "<meta http-equiv='Content-Type' "
        . "content='text/html; charset=iso-8859-1'>\n"
        . "<link rel='stylesheet' href='style.css'>\n"
        . "</head>";
}

function pageHeader() {
    echo <<<END
<div id="titlebar">
        <a href="http://www.uofaweb.ualberta.ca/science/">
        <img src="http://www.cs.ualberta.ca/library/images/science.gif"
        alt="Faculty of Science Home Page" width="525" height="20"
        border="0"/></a>
        <a href="http://www.ualberta.ca/">
        <img src="http://www.cs.ualberta.ca/library/images/uofa_top.gif"
        alt="University of Alberta Home Page" width="225" height="20"
        border="0"/></a>
    </div>

<div id="header">
        <h1>Papers Database</h1>
    </div>

END;
}

function navigationMenu() {
    global $logged_in;

    $options = array();

    if ($logged_in) {
        $options += array('Add Publication' => 'Admin/add_publication.php',
                          'Add Author' => 'Admin/add_author.php');
    }

    $options += array('Advanced Search' => 'advanced_search.php',
                      'All Publications' => 'list_publication.php',
                      'All Authors' => 'list_author.php');

    if ($logged_in) {
        $options += array('Logout' => 'logout.php');
    }
    else {
        $options += array('Login or Register' => 'login.php');
    }

    echo <<<END
<div id="nav">
    <h2>navigation</h2>
    <ul>
END;

    foreach ($options as $key => $value) {
        printf("<li><a href='%s'>%s</a></li>", $value, $key);
    }

    echo <<<END
</ul>
<form name="quicksearch" action="search_publication_db.php" method="POST"
    enctype="multipart/form-data">
    <input type="hidden" name="titlecheck" value="true"/>
    <input type="hidden" name="authorcheck" value="true"/>
    <input type="hidden" name="halfabstractcheck" value="true"/>
    <input type="hidden" name="datecheck" value="true"/>
    <input type="text" name="search" size="12" maxlength="250" value=""/>
    <input type="SUBMIT" name="Quick" value="Search" class="text"/>
    </form>
</div>
END;
}

function pageFooter() {
    echo <<<END
<div id="footer">

For any questions/comments about the Papers Database please e-mail
<a href="mailto:papersdb@cs.ualberta.ca">PapersDB Administrator</a>
        <div class="ualogo">
		<a href="http://www.ualberta.ca">
        <img src="http://www.cs.ualberta.ca/library/images/uofa_logo.gif"
        width="162" height="36" alt="University of Alberta Logo" />
        </a>
        </div>
        <div id="copyright">
		<ul>
		<li>Copyright &copy; 2002-2006</li>
                                 </ul>
                                 </div>
                                 </div>

END;
}


?>
