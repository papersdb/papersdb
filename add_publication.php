<html>
<head>
<title>Add or Edit Publication</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
include("header.php");
require("lib_functions.php");
require('lib_dbfunctions.php');

// Global variable to keep track of what we're doing - can change this
// to not be a boolean if we want to deal with more than 2 different
// operations (save, new) on this page
$edit = FALSE;

// Check to see if we've been passed a publication ID
if (isset($_GET['pub_id']) & $_GET['pub_id'] != "") {

	// Set "edit mode" to true - we could just check for the existence
	// of pub_id in the GET variables, but this is more clear.
	$edit = TRUE;

	// Connect to the DB
	$db = db_connect();

	// Get publication info
	$pubInfo = get_publication_info($_GET['pub_id']);

	// Check if the publication actually exists
	if ($pubInfo == NULL) {
			 "Error: Publication with ID " . $_GET['pub_id'] . " doesn't exist.";
			 $db->disconnect();

			 // andy_note: make this not an exit... need more graceful
			 // error handling.
		exit;
	}

	// Set the variables to be set in the page as initial values.
	// We have to check and see if there's a value that's already been
	// posted back to us, and use that instead, in case it changes
	// between page updates.

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


	while (!(strpos($category, "\\") === FALSE)) { 
		$category = stripslashes($category);
	}
	while (!(strpos($title, "\\") === FALSE)) { 
		$title = stripslashes($title);
	}


	// Adding a new author
	if ($newAuthorSubmitted == "true") {
		$authorname = ucfirst(strtolower(trim($lastname))) . ", " . ucfirst(strtolower(trim($firstname)));
	
		/* Connecting, selecting database */
		$link = connect_db();
	
		/* Performing SQL query */
		$author_query = "INSERT INTO author (author_id, name, title, email, organization, webpage) VALUES (NULL, \"$authorname\", \"$auth_title\", \"$email\", \"$organization\", \"$webpage\")";
		$author_result = mysql_query($author_query) or die("Query failed : " . mysql_error());
		
		$unique_interest_id_counter = 0;
		
		for ($i = 0; $i < count($newInterest); $i++) {
			if ($newInterest[$i] != "") {
				$interest_query = "INSERT INTO interest (interest_id, interest) VALUES (NULL, \"$newInterest[$i]\")";
				$interest_result = mysql_query($interest_query) or die("Query failed : " . mysql_error());
				
				$interest_id_query = "SELECT interest_id FROM interest WHERE interest=\"$newInterest[$i]\"";
				$interest_id_result = mysql_query($interest_id_query) or die("Query failed: " . mysql_error());
				$interest_id_temp_array =  mysql_fetch_array($interest_id_result, MYSQL_ASSOC);
				
				$interest_id_array[$unique_interest_id_counter] = $interest_id_temp_array[interest_id];
				$unique_interest_id_counter++;
		
				mysql_free_result($interest_id_result);
			}
		}
		
		$author_id_query = "SELECT author_id FROM author WHERE name=\"$authorname\"";
		$author_id_result = mysql_query($author_id_query) or die("Query failed: " . mysql_error());
		
		$author_id_array = mysql_fetch_array($author_id_result, MYSQL_ASSOC);
		$author_id = $author_id_array[author_id];
		
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
			$author_interest_query = "INSERT INTO author_interest (author_id, interest_id) VALUES $temp";
			$author_interest_result = mysql_query($author_interest_query) or die("Query failed: " . mysql_error());
		}
		
		$newAuthorSubmitted == "false";
		
		// This is to preserve the selections the user has already made
		$all_author_query = "SELECT name FROM author";
		$all_author_result = mysql_query($all_author_query) or die("Query failed: " . mysql_error());
		$position = -1;
		$author_counter = 0;
		
		while ($all_author_line = mysql_fetch_array($all_author_result, MYSQL_ASSOC)) {
			if (strcmp($all_author_line[name], $authorname) == 0) {
				$position = $author_counter;
			}
			$author_counter++;
		}
		
		$push_counter = 0;
		//echo "author count: " . $author_counter . "<br>";
		//echo "position is: " . $position . "<br>";
		
		for ($i = 0; $i < $author_counter; $i++) {
			if ($i >= $position) {
				if ($authors[$i] != "") {
					$push_array[$push_counter] = $i + 1;
					$push_counter++;
				}
			}
		}

		//echo "push count: " . count($push_array) . "<br>";
		//for ($i = 0; $i < count($push_array); $i++) {
		//	echo "selected: " . $push_array[$i] . "<br>";
		//}		
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
		disconnect_db($link);
	}

	// Adding a new category
	if ($newCatSubmitted == "true") {
		/* Connecting, selecting database */
		$link = connect_db();
	
		/* Performing SQL query */
		$cat_query = "INSERT INTO category (cat_id, category) VALUES (NULL, \"$catname\")";
		$cat_result = mysql_query($cat_query) or die("Query failed : " . mysql_error());
		
		$unique_info_id_counter = 0;
		
		for ($i = 0; $i < count($newField); $i++) {
			if ($newField[$i] != "") {
				$info_query = "INSERT INTO info (info_id, name) VALUES (NULL, \"$newField[$i]\")";
				$info_result = mysql_query($info_query) or die("Query failed : " . mysql_error());
				
				$info_id_query = "SELECT info_id FROM info WHERE name=\"$newField[$i]\"";
				$info_id_result = mysql_query($info_id_query) or die("Query failed: " . mysql_error());
				$info_id_temp_array =  mysql_fetch_array($info_id_result, MYSQL_ASSOC);
				
				$info_id_array[$unique_info_id_counter] = $info_id_temp_array[info_id];
				$unique_info_id_counter++;
				
				mysql_free_result($info_id_result);
			}
		}
		
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
		$newCatSubmitted = "false";
		$category = $catname;
		
		mysql_free_result($cat_id_result);
		disconnect_db($link);
	}

	$info[0] = "";
	
    /* Connecting, selecting database */
    $link = connect_db();

    /* Performing SQL query */
    $cat_query = "SELECT category FROM category";
    $cat_result = mysql_query($cat_query) or die("Query failed : " . mysql_error());
	
	$info_query = "SELECT info.name FROM info, category, cat_info WHERE category.cat_id=cat_info.cat_id
	                AND info.info_id=cat_info.info_id
					AND category.category=\"$category\"";
	$info_result = mysql_query($info_query) or die("Query failed : " . mysql_error());
	
	$info_counter = 0;
	while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)) {
		$info[$info_counter] = $info_line[name];
		$info_counter++;
	}
	
	$author_query = "SELECT * FROM author ORDER BY name ASC";
	$author_result = mysql_query($author_query) or die("Query failed : " . mysql_error());
?>

<script language="JavaScript" type="text/JavaScript">

window.name="add_publication.php";

<? // andy_note: do we need to edit this javascript function to keep
   // the pubID around too, if it exists? -->yes.  how?
?>
function dataKeep() {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
		if ((document.forms["pubForm"].elements[i].value != "") && 
                    (document.forms["pubForm"].elements[i].value != null)) {
			if (info_counter > 0) {
			 temp_qs = temp_qs + "&";
			}
			
			if (document.forms["pubForm"].elements[i].name == "authors[]") {
				author_array = document.forms["pubForm"].elements['authors[]'];
				var author_list = "";
				var author_count = 0;
				
				for (j = 0; j < author_array.length; j++) {
					if (author_array[j].selected == 1) {
						if (author_count > 0) {
							author_list = author_list + "&";
						}
						author_list = author_list + "authors[" + j + "]=" + author_array[j].value;
						author_count++;
					}
				}
				
				temp_qs = temp_qs + author_list;
			}
			else {
				temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + document.forms["pubForm"].elements[i].value;
			}
			
			info_counter++;
		}
	}
	
	temp_qs = temp_qs.replace(" ", "%20");
	location.href = "http://" + "<? echo $_SERVER["HTTP_HOST"]; echo $PHP_SELF; ?>?" + temp_qs;
}

function dataKeepPopup(page) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
		if ((document.forms["pubForm"].elements[i].value != "") && 
                    (document.forms["pubForm"].elements[i].value != null)) {
			if (info_counter > 0) {
			 temp_qs = temp_qs + "&";
			}
			if (document.forms["pubForm"].elements[i].name == "authors[]") {
				author_array = document.forms["pubForm"].elements['authors[]'];
				var author_list = "";
				var author_count = 0;
				
				for (j = 0; j < author_array.length; j++) {
					if (author_array[j].selected == 1) {
						if (author_count > 0) {
							author_list = author_list + "&";
						}
						author_list = author_list + "authors[" + j + "]=" + author_array[j].value;
						author_count++;
					}
				}
				
				temp_qs = temp_qs + author_list;
			}
			else {
				temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + document.forms["pubForm"].elements[i].value;
			}
			
			info_counter++;
		}
	}
	
	if (page == "add_category.php") {
		temp_qs = temp_qs + "&newFields=0";
	}
	
	//var temp_url = "http://" + "<? echo $_SERVER["HTTP_HOST"]; ?>/~loh/" + page + "?" + temp_qs;
	var temp_url = "./" + page + "?" + temp_qs;
	temp_url = temp_url.replace(" ", "%20");
	window.open(temp_url, 'Add', 'width=600,height=350,scrollbars=yes,resizable=yes');
}


function dataKeepPopupWithID(page, id) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
		if ((document.forms["pubForm"].elements[i].value != "") && 
                    (document.forms["pubForm"].elements[i].value != null)) {
			if (info_counter > 0) {
			 temp_qs = temp_qs + "&";
			}
			if (document.forms["pubForm"].elements[i].name == "authors[]") {
				author_array = document.forms["pubForm"].elements['authors[]'];
				var author_list = "";
				var author_count = 0;
				
				for (j = 0; j < author_array.length; j++) {
					if (author_array[j].selected == 1) {
						if (author_count > 0) {
							author_list = author_list + "&";
						}
						author_list = author_list + "authors[" + j + "]=" + author_array[j].value;
						author_count++;
					}
				}
				
				temp_qs = temp_qs + author_list;
			}
			else {
				temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + document.forms["pubForm"].elements[i].value;
			}
			
			info_counter++;
		}
	}
	
	if (page == "add_category.php") {
		temp_qs = temp_qs + "&newFields=0";
	}
	
	//var temp_url = "http://" + "<? echo $_SERVER["HTTP_HOST"]; ?>/~loh/" + page + "?" + temp_qs + "&pub_id=" + id;
	var temp_url = "./" + page + "?" + temp_qs + "&pub_id=" + id;
	temp_url = temp_url.replace(" ", "%20");
	window.open(temp_url, 'Add', 'width=600,height=350,scrollbars=yes,resizable=yes');
}

function verify() {
	if (document.forms["pubForm"].elements["category"].value == "") {
		alert("Please enter a category for the publication.");
		return false;
	}
	if (document.forms["pubForm"].elements["title"].value == "") {
		alert("Please enter a title for the publication.");
		return false;
	}
	if (document.forms["pubForm"].elements["uploadpaper"].value == "") {
		alert("Please choose a paper to upload.");
		return false;
	}
	if (document.forms["pubForm"].elements["authors"].value == "") {
		alert("Please select the authors of this publication.");
		return false;
	}
	if (document.forms["pubForm"].elements["abstract"].value == "") {
		alert("Please enter the abstract for this publication.");
		return false;
	}
	if (document.forms["pubForm"].elements["keywords"].value == "") {
		alert("Please enter the keywords for this publication.");
		return false;
	}
	
	return true;
}

function resetAll() {
	location.href="./add_publication.php";
}
</script>

<body>
<form name="pubForm" action="add_publication_db.php" method="POST" enctype="multipart/form-data"> <!--application/x-www-form-urlencoded">-->

<?

	if ($edit) {
		echo "<input type=hidden name=pub_id value=$pub_id> \n";
	}

?>

	<table width="750" border="0" cellspacing="0" cellpadding="6">


<!-- Category -->
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Category: </b></font><a href="./help.php" target="_blank" onClick="window.open('./help.php?helpcat=category', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>
		<td width="75%">
			<select name="category" onChange="dataKeep();">
				<option value="">--- Please Select a Category ---</option>
				<? 
					while ($cat_line = mysql_fetch_array($cat_result, MYSQL_ASSOC)) {
						echo "<option value=\"" . $cat_line[category] . "\"";

						if (stripslashes($category) == $cat_line[category])
							echo " selected";

						echo ">" . $cat_line[category] . "</option>";
				 	}
				?>
			</select>
			&nbsp;&nbsp;<a href="javascript:dataKeepPopup('add_category.php');"><font face="Arial, Helvetica, sans-serif" size="1">Add Category</font></a>
		</td>
	  </tr>


<!-- Title of the paper -->
	  <tr>

		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Title: </b></font><a href="./help.php" target="_blank" onClick="window.open('./help.php?helpcat=title', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>
		<td width="75%"><input type="text" name="title" size="50" maxlength="250" value="<? echo stripslashes($title); ?>"></td>
	  </tr>


<!-- Number of additional materials -->
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Number of Additional Materials: </b></font><a href="./help.php" target="_blank" onClick="window.open('./help.php?helpcat=nummaterials', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>
		<td width="75%">
			<select name="numMaterials" onChange="dataKeep();">
				<option value="">--- Number of Additional Materials ---</option>
			        <? generate_select_body (1, 10, $numMaterials) ?>
			</select>
		</td>
	  </tr>


<!-- The Paper -->
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2" color="#990000"><b>Paper: </b></font><a href="./help.php" target="_blank" onClick="window.open('./help.php?helpcat=paper', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>


<?  if ($edit) { ?>
  <td width="75%"> <? echo $paper; ?> &nbsp; &nbsp; &nbsp; 
     <a href="" onClick="dataKeepPopupWithID('change_paper.php', <? echo $pub_id; ?>);"><font face="Arial, Helvetica, sans-serif" size="1">Change Paper</font></a> 
  </td>
<?
  }
  else {
?>
  <td width="75%"><input type="file" name="uploadpaper" size="60" maxlength="250"></td>
<? } ?>
	  </tr>


<!-- Additional Materials -->
<? for ($i = 0; $i < $numMaterials; $i++) { ?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2" color="#999999"><b>Additional Material <? echo $i + 1 ?>: </b></font></td>
<? 
	       if ($i < $dbMaterials) {
		   $curMaterial = get_additional_material($pub_id, $i);
?>
		<td width="75%">  <? echo $curMaterial ?> &nbsp; &nbsp; &nbsp; <a href="" onClick="dataKeepPopup('change_paper.php');"><font face="Arial, Helvetica, sans-serif" size="1">Delete</font></a> </td>
<?
	       }
	       else {
?>
		<td width="75%"><input type="file" name="uploadadditional<? echo $i ?>" size="60" maxlength="250"></td>
	  </tr>
<? } 

} ?>


<!-- Authors  -->
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Authors: </b></font><a href="./help.php" target="_blank" onClick="window.open('./help.php?helpcat=authors', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>
		<td width="75%">
			<select name="authors[]" size="10" multiple>
				<? 
					$counter = 0;
					while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
						echo "<option value=\"" . $author_line[author_id] . "\"" . "";

						if ($authors[$counter] != "" ||
						    $authors_from_db[$author_line[name]] != "") 
						    echo " selected";

						echo ">" . $author_line[name] . "</option>";
						$counter++;
				 	}
				?>
			</select>
			&nbsp;&nbsp;<a href="javascript:dataKeepPopup('add_author.php');"><font face="Arial, Helvetica, sans-serif" size="1">Add Author</font></a>
		</td>
	  </tr>


<!-- Abstract -->
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Abstract: </b></font><a href="./help.php" target="_blank" onClick="window.open('./help.php?helpcat=abstract', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>
		<td width="75%"><textarea name="abstract" cols="70" rows="10"><? echo stripslashes($abstract) ?></textarea></td>
	  </tr>


<!-- Keywords -->
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Keywords: </b></font><a href="./help.php" target="_blank" onClick="window.open('./help.php?helpcat=keywords', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>
		<td width="75%"><input type="text" name="keywords" size="60" maxlength="250" value="<? echo stripslashes($keywords) ?>">&nbsp;&nbsp;<font face="Arial, Helvetica, sans-serif" size="1">seperate by semi-colon (;)</font></td>
	  </tr>


<!-- Additional info fields  -->
<? for ($i = 0; $i < count($info); $i++) { 
	  $varname = strtolower($info[$i]);
	  if ($varname != "") {
		$varname = str_replace(" ", "", $varname);
		
		// If the user didn't enter anything into the form,
		// use the value we pulled from the database
		if ($$varname == "") {
		    $infoID = get_info_id($category_id, $info[$i]);
		    $$varname = get_info_field_value($pub_id, $category_id, $infoID);
		}
?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b><? echo $info[$i] ?>: </b></font><a href="./help.php" target="_blank" onClick="window.open('./help.php?helpcat=Additional Fields', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>
		<td width="75%"><input type="text" name="<? echo $varname ?>" size="50" maxlength="250" value="<? echo stripslashes($$varname) ?>"></td>
	  </tr>
<? 	  }
   } 
?>


<!-- Date Published stuff -->
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Date Published: </b></font><a href="./help.php" target="_blank" onClick="window.open('./help.php?helpcat=date', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>

		<td width="75%">
			<? $currmonth = date("m");
			   if ($month == "") { 
			       generate_select_month("month", 1, 12, $currmonth);
			   }
			   else {
			       generate_select_month("month", 1, 12, $month);
			   } 
                        ?>

			&nbsp;&nbsp;

			<? $currday = date("d");
			   if ($day == "") { 
			       generate_select("day", 1, 31, $currday);
			   }
			   else { 
			       generate_select("day", 1, 31, $day);
			   }
                        ?>
			
			&nbsp;&nbsp;

			<? $curryear = date("Y");
			   if ($year == "") { 
                             generate_select ("year", 1950, 2050, $curryear);
			   }
                           else {	
			     generate_select ("year", 1950, 2050, $year);       
			   }
                        ?>
		</td>
	  </tr>


<!-- This row is superfluous? -->
	  <tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	  </tr>


<!-- Buttons to control what we do with the data -->
	  <tr>
		<td width="25%">&nbsp;</td>
		<td width="75%" align="left">

		<? if ($edit) { ?>
		  <input type="SUBMIT" name="Save" value="Save Changes" class="text" onClick="return verify();">
		<? } else { ?>
		  <input type="SUBMIT" name="Submit" value="Add Publication" class="text" onClick="return verify();">
		<? } ?>

		&nbsp;&nbsp;

                <!-- This will clear out all the values in all fields -->
                <input type="RESET" name="Clear" value="Clear" class="text" onClick="resetAll();">

		<!-- Reset will set everything back to what they are in the DB (not implemented) -->
                <!-- <input type="RESET" name="Reset" value="Reset" class="text"></td> -->
	  </tr>
	</table>
</form>

</body>
</html>

<?

    if ($edit) {
      // Disconnect from the DB
      $db->disconnect();    
    }

    /* Free resultset */
    mysql_free_result($cat_result);
    mysql_free_result($info_result);
    mysql_free_result($author_result);

    /* Closing connection */
    disconnect_db($link);
?>
