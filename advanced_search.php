<html>
<head>
<title>Search Publication</title>
<link rel="stylesheet" href="style.css">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
 /* advanced_search.php
    this is the advanced search page. It is mainly only forms, with little
	data being read from the database. It sends the users input to
	search_publication_db.php.
*/
	if($admin =="true")
		include 'headeradmin.php';
	else
		include 'header.php';

	require('functions.php');


	/* Connecting, selecting database */
    $link = connect_db();

    /* Performing SQL query */
	$cat_result = query_db("SELECT cat_id, category FROM category");

	isValid($cat_id);
	$info_result = query_db("SELECT info.name FROM info, category, cat_info
							WHERE category.cat_id=cat_info.cat_id
	                		AND info.info_id=cat_info.info_id
							AND category.cat_id=" . quote_smart($cat_id));

	$info_counter = 0;
	while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)) {
		$info[$info_counter] = $info_line[name];
		$info_counter++;
	}

	$author_result = query_db("SELECT name, author_id FROM author ORDER BY name ASC");

?>

<script language="JavaScript" type="text/JavaScript">
window.name="search_publication.php";
function resetAll() {
	location.href="advanced_search.php";
}
function refresher() { window.location.reload(true);}

function dataKeep(num) {
	var temp_qs = "";
	var info_counter = 0;
	var form = document.forms["pubForm"];

	for (i = 0; i < form.elements.length; i++) {
		if ((form.elements[i].value != "") &&
                    (form.elements[i].value != null)) {
			if (info_counter > 0) {
			 temp_qs = temp_qs + "&";
			}

				temp_qs = temp_qs + form.elements[i].name + "=" + form.elements[i].value;

			info_counter++;
		}
	}
	if(num == 1){temp_qs = temp_qs + "&expand=true";}
	temp_qs = temp_qs.replace("\"", "?");
	temp_qs = temp_qs.replace(" ", "%20");
	location.href = "http://" + "<? echo $_SERVER["HTTP_HOST"]; echo $PHP_SELF."?"; ?>" + temp_qs;

}


</script>

<body>
<A NAME ="Start"></A>
<? if($admin == "true"){ ?>
<form name="pubForm" action="search_publication_db.php?admin=true" method="POST" enctype="multipart/form-data"> <!--application/x-www-form-urlencoded">-->
<? } else { ?>
<form name="pubForm" action="search_publication_db.php" method="POST" enctype="multipart/form-data"> <!--application/x-www-form-urlencoded">-->
<? }

	if ($edit)
		echo "<input type=hidden name=pub_id value=$pub_id> \n";
	if($expand == "true")
		echo "<input type=hidden name=\"expand\" value=\"true\"> \n";
	if($admin == "true")
		echo "<input type=hidden name=\"admin\" value=\"true\"> \n";

?>
   <A NAME ="Start"></A>
	<h2><b><u>Search</u></b></h2>
	<table width="750" border="0" cellspacing="0" cellpadding="6">

<tr>

		<td width="25%" class="small"><b>Search: </b></td>
		<td width="75%"><input type="text" name="search" size="50" maxlength="250" value="<? echo stripslashes($search); ?>"> <input type="SUBMIT" name="Quick" value="Search" class="text"></td>
</tr>
<TR><TD colspan="2"><HR></TD></TR>
<tr>

		<td><h3>Advanced Search</h3></td>

</tr>
<tr><td>
<h4><b>Search within:</b></h4>
</td></tr>
<!-- Category -->
	  <tr>
		<td width="25%" class="small">Category: </td>
		<td width="75%">
			<select name="cat_id" onChange="dataKeep(0);">
				<option value="">All Categories</option>
				<?
					while ($cat_line = mysql_fetch_array($cat_result, MYSQL_ASSOC)) {
						echo "<option value=\"" . $cat_line[cat_id] . "\"";

						if (stripslashes($cat_id) == $cat_line[cat_id])
							echo " selected";

						echo ">" . $cat_line[category] . "</option>";
				 	}
				?>
			</select>
		</td>
	  </tr>

<!-- Title of the paper -->
	<? quickForm("Title","small","title", $title); ?>
<!-- Authors  -->
	  <tr>
		<td width="25%" class="small">Author(s): </td>
		<td width="75%" >
		<input type="text" name="authortyped" size="20" maxlength="250" value="<? echo stripslashes($authortyped); ?>">
		 or select from list
			<select name="authorselect[]" >
				<?
					echo "<option value=\"\">All Authors</option>";
					$counter = 0;
					while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
						//print_r($author_line);
						//echo "<br>";
						echo "<option value=\"" . $author_line['author_id'] . "\"" . "";

						if ($authors[$counter] != "" ||
						    $authors_from_db[$author_line['name']] != "")
						    echo " selected";

						echo ">" . $author_line['name'] . "</option>";
						$counter++;
				 	}
				?>
			</select>



		 </td>
	  </tr>
<?  // Quickform just makes a standard text form using basic parameters.
	quickForm("Paper Filename","small","paper", $paper);
	quickForm("Abstract","small","abstract", $abstract);
	quickForm("Publication Venue","small","venue", $venue);
	quickForm("Keywords","small","keywords", $keywords);

	for ($i = 0; $i < count($info); $i++) {
	  $varname = strtolower($info[$i]);
	  if ($varname != "") {
		$varname = str_replace(" ", "", $varname);

		// If the user didn't enter anything into the form,
		// use the value we pulled from the database
		if ($$varname == "") {
		    $infoID = get_info_id($category_id, $info[$i]);
		    $$varname = get_info_field_value($pub_id, $category_id, $infoID);
		}

		quickForm($info[$i], "small", $varname, $$varname);
	  }
    }
?>


<!-- Date Published stuff -->
	  <tr>
		<td width="20%" class="small">Published Inbetween: </td>

		<td width="40%">

			<? $currmonth = date("m");
			   if ($month1 == "")
			       generate_select_month("month1", 1, 12, 1);
			   else
			       generate_select_month("month1", 1, 12, $month1);

               $currday = date("d");
			   if ($day1 == "")
			       generate_select("day1", 1, 31, 1);
			   else
			       generate_select("day1", 1, 31, $day1);

			   $curryear = date("Y");
			   if ($year1 == "")
                   generate_select ("year1", 1950, $curryear+2, 1950);
			   else
			       generate_select ("year1", 1950, $curryear+2, $year1);
			   echo " and ";
			   $currmonth = date("m");
			   if ($month2 == "")
			       generate_select_month("month2", 1, 12, $currmonth);
			   else
			       generate_select_month("month2", 1, 12, $month2);

               $currday = date("d");
			   if ($day2 == "")
			       generate_select("day2", 1, 31, $currday);
			   else
			       generate_select("day2", 1, 31, $day2);

			   $curryear = date("Y");
			   if ($year2 == "")
                   generate_select ("year2", 1950, $curryear+2, $curryear);
			   else
			       generate_select ("year2", 1950, $curryear+2, $year2);


			?>
		</td>
	  </tr>


	<? if($expand == "true"){ ?>
	<TR><TD colspan="2"><HR></TD></TR>
	  <tr>
		<td class="small"><b>User Search Preferences</b></td>
		<td class="small"><b>Show:</b><BR>
		<table><tr><td class="small">
			<INPUT TYPE=CHECKBOX NAME="titlecheck" CHECKED>Title<BR>
			<INPUT TYPE=CHECKBOX NAME="authorcheck" CHECKED>Author(s)<BR>
			<INPUT TYPE=CHECKBOX NAME="categorycheck">Category<BR>
			<INPUT TYPE=CHECKBOX NAME="extracheck">Category Related Information<BR>
			<INPUT TYPE=CHECKBOX NAME="papercheck">Link to Paper<BR>
			<INPUT TYPE=CHECKBOX NAME="additionalcheck">Link to Additional Materials<BR>

			</td><td class="small">
			<INPUT TYPE=CHECKBOX NAME="halfabstractcheck" CHECKED>Short Abstract<BR>
			<INPUT TYPE=CHECKBOX NAME="venuecheck">Publication Venue<BR>
			<INPUT TYPE=CHECKBOX NAME="keywordscheck">Keywords<BR>
			<INPUT TYPE=CHECKBOX NAME="datecheck" CHECKED>Date Published<BR>

		</td></tr></table>
			<b>in the search</b>.
		</td>
	  </tr>
	  <TR><TD colspan="2"><HR></TD></TR>
	<? }
		else { ?>
		<tr><td colspan = "2"><a href="javascript:dataKeep(1);">User Search Preferences</a></td></tr>
		<INPUT TYPE=hidden NAME="titlecheck" value=true>
		<INPUT TYPE=hidden NAME="authorcheck" value=true>
		<INPUT TYPE=hidden NAME="halfabstractcheck" value=true>
		<INPUT TYPE=hidden NAME="datecheck" value=true>
		<? } ?>

<!-- Buttons to control what we do with the data -->

	  <tr>
		<td width="25%">&nbsp;</td>
		<td width="75%" align="left">

		  <input type="SUBMIT" name="Submit" value="Search" class="text">
		  <input type="RESET" name="Clear" value="Clear" class="text" onClick="resetAll();">


		&nbsp;&nbsp;

                <!-- This will clear out all the values in all fields -->


		<!-- Reset will set everything back to what they are in the DB (not implemented) -->
                <!-- <input type="RESET" name="Reset" value="Reset" class="text"></td> -->
	  </tr>
	</table>
</form>
<? back_button(); ?>
</body>
</html>

<?
    /* Free resultset */
    mysql_free_result($cat_result);
    mysql_free_result($info_result);
    mysql_free_result($author_result);

    /* Closing connection */
    disconnect_db($link);
?>
