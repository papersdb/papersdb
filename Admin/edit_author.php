<html>
<head>
<title>Edit Author</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?
	/* edit_author.php
		This page is for editing an author. 
		It is passed an author_id, and then fills
		the selected fields, and then replaces the information
		in the database.
	
	*/
require('../functions.php');
if ($editAuthorSubmitted == "true"){
	echo "<script language='javascript'>setTimeout(\"top.location.href = './'\",5000)</script>";
}
echo "</head>";

	
	$link = connect_db();

	if ($popup != "true" ) {
		include 'header.php';
	}

	/* Are we editing data in the db? */
	if ($editAuthorSubmitted == "true") {
		$author_id = $_POST['author_id'];
		$authorname = $_POST['lastname'] . ", " . $_POST['firstname'];
		$auth_title = $_POST['auth_title'];
		$email = $_POST['email'];
		$organization = $_POST['organization'];
		$webpage = $_POST['webpage'];
		$interests = $_POST['interests'];
		$newInterests = $_POST['newInterests'];
		$author_query = "UPDATE author SET name=\"$authorname\", title=\"$auth_title\", email=\"$email\", organization=\"$organization\", webpage=\"$webpage\" WHERE author_id=$author_id";
		query_db($author_query);
		
		$interest_query = "DELETE FROM author_interest WHERE author_id = $author_id";
		query_db($interest_query);
		
		// add new interests
		while (count($newInterest) > 0) {
			$int = trim(array_pop($newInterest));
			if (isset($int) && $int != "") {
				$interest_query = "INSERT INTO interest (interest_id, interest) VALUES (NULL, \"$int\")";
				query_db($interest_query);
				
				$interest_query = "SELECT interest_id FROM interest WHERE interest=\"$int\"";
				$interest_result = query_db($interest_query);
				$interest_array =  mysql_fetch_array($interest_result, MYSQL_ASSOC);
				$interest_id = $interest_array['interest_id'];
				
				$interest_query = "INSERT INTO author_interest (author_id, interest_id) VALUES ($author_id, $interest_id)";
				query_db($interest_query);
			}
		}
		
		// add selected old interests
		while (count($interests) > 0) {
			$interest_id = array_pop($interests);
			$interest_query = "INSERT INTO author_interest (author_id, interest_id) VALUES ($author_id, $interest_id)";
			query_db($interest_query);
		}
		
		//echo "<script language='javascript'>alert('Author successfully edited.');document.write(location.href='/.)</script>";
		//the above line doesn't seem to work, I don't know why --Jeff
		echo "<body>You have successfully made changes to the author $authorname.";
		echo "<br><br>You will be transported to the main page in 5 seconds.</body></html>";
		disconnect_db($link);
		//echo "<script language='javascript'>alert('Author successfully edited.');</script>";
		
		exit();
	}

echo "<body>";

	/* Connecting, selecting database */

	/* Performing SQL query */
	$author_query = "SELECT * FROM author WHERE author_id=$author_id";
	$author_result = query_db($author_query);
	$author_array = mysql_fetch_array($author_result, MYSQL_ASSOC);
	
	//author's listed interests
	$int_query = "SELECT interest_id FROM author_interest WHERE author_id=$author_id";
	$int_result = query_db($int_query);
	
	//interests to choose from
	$interest_query = "SELECT * FROM interest";
	$interest_result = query_db($interest_query);
	$num_rows = mysql_num_rows($interest_result);

	//data to set in the window
	$author_array[name] = str_replace("\n", "", $author_array[name]);
	if (!isset($firstname))
		$firstname = trim(substr($author_array['name'],1+strpos($author_array['name'],',')));
	if (!isset($lastname))
		$lastname = substr($author_array['name'],0,strpos($author_array['name'],','));
	if (!isset($auth_title))
		$auth_title = $author_array['title'];
	if (!isset($email))
		$email = $author_array['email'];
	if (!isset($organization))
		$organization = $author_array['organization'];
	if (!isset($webpage))
		$webpage = $author_array['webpage'];
	if (!isset($interests)) {
		while ($int_line = mysql_fetch_array($int_result, MYSQL_ASSOC)) {
			$interests[$int_line['interest_id']-1] = $int_line['interest_id'];
		}
	}

?>

<script language="JavaScript" type="text/JavaScript">

function verify() {
	if (document.forms["authorForm"].elements["firstname"].value == "") {
		alert("Please enter a complete name for the new author.");
		return false;
	}
	if (document.forms["authorForm"].elements["lastname"].value == "") {
		alert("Please enter a complete name for the new author.");
		return false;
	}
	if ((document.forms["authorForm"].elements["firstname"].value).search(",")!=-1) {
		alert("Please do not use commas in the author's first name");
		return false;
	}
	if ((document.forms["authorForm"].elements["lastname"].value).search(",")!=-1) {
		alert("Please do not use commas in the author's last name");
		return false;
	}
	return true;
}

function dataKeep(num) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["authorForm"].elements.length; i++) {
		if ((document.forms["authorForm"].elements[i].value != "") && (document.forms["authorForm"].elements[i].value != null)) {
			if (info_counter > 0) {
				temp_qs = temp_qs + "&";
			}
			
			if (document.forms["authorForm"].elements[i].name == "interests[]") {
				interest_array = document.forms["authorForm"].elements['interests[]'];
				var interest_list = "";
				var interest_count = 0;
				
				for (j = 0; j < interest_array.length; j++) {
					if (interest_array[j].selected == 1) {
						if (interest_count > 0) {
							interest_list = interest_list + "&";
						}
						interest_list = interest_list + "interests[" + j + "]=" + interest_array[j].value;
						interest_count++;
					}
				}
				
				temp_qs = temp_qs + interest_list;
			}
			else {
				temp_qs = temp_qs + document.forms["authorForm"].elements[i].name + "=" + document.forms["authorForm"].elements[i].value;
			}
			
			info_counter++;
		}
	}
	
	temp_qs = temp_qs.replace(" ", "%20");
	location.replace("./edit_author.php?<? echo $_SERVER['QUERY_STRING'] ?>&newInterests=" + num + "&" + temp_qs);
	//window.open("./edit_author.php?<? echo $_SERVER['QUERY_STRING'] ?>&newInterests=" + num + "&" + temp_qs, "Add");
}

function resetAll() {
	location.replace("./edit_author.php?<? echo $_SERVER['QUERY_STRING'] . "&newInterests=0" ?>");
	//window.open("./edit_author.php?<? echo $_SERVER['QUERY_STRING'] ?>&newInterests=0", "Add");
}
</script>

<form name="authorForm" action="./edit_author.php?editAuthorSubmitted=true" method="POST" enctype="application/x-www-form-urlencoded" onsubmit="setTimeout('self.close()',0);">
	<table width="600" border="0" cellspacing="0" cellpadding="6">
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>First Name: </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="firstname" size="50" maxlength="250" value="<? echo stripslashes($firstname); ?>"></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Last Name: </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="lastname" size="50" maxlength="250" value="<? echo stripslashes($lastname); ?>"></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Title: </b></font><a href="../help.php" target="_blank" onClick="window.open('../help.php?helpcat=Author Title', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>
		<td colspan="2" width="75%"><input type="text" name="auth_title" size="50" maxlength="250" value="<? echo stripslashes($auth_title); ?>"></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Email: </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="email" size="50" maxlength="250" value="<? echo stripslashes($email); ?>"></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Organization: </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="organization" size="50" maxlength="250" value="<? echo stripslashes($organization); ?>"></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Webpage: </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="webpage" size="50" maxlength="250" value="<? echo stripslashes($webpage); ?>"></td>
	  </tr>
	  <tr>
		<td width="25%">
			<font face="Arial, Helvetica, sans-serif" size="2"><b>Interest(s): </b></font><br>
			<font face="Arial, Helvetica, sans-serif" size="1"><a href="javascript:dataKeep(<? echo ($newInterests + 1) ?>)">[Add Interest]</a></font>
		</td>
		<td width="20%" align="left">
			<select name="interests[]" size="5" multiple>
				<? 
					$counter = 0;
					while ($interest_line = mysql_fetch_array($interest_result, MYSQL_ASSOC)) {
						echo "<option value=\"" . $interest_line[interest_id] . "\"" . "";
						if (isset($interests[$counter]) && $interests[$counter] != "")
							echo " selected";
						echo ">" . $interest_line[interest] . "</option>";
						$counter++;
				 	}
				?>
			</select>
		</td>
	  </tr>
	  <? for ($i = 0; $i < $newInterests; $i++) { ?>
	 		<tr>
				<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Interest Name: </b></font></td>
				<td colspan="2" width="75%"><input type="text" name="newInterest[<? echo $i ?>]" size="50" maxlength="250" value="<? echo stripslashes($newInterest[$i]); ?>"></td>
			</tr>
	  <? } ?>
	  <tr>
		<td>
			<a href="../help.php" target="_blank" onClick="window.open('../help.php?helpcat=Edit Author', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a>
		</td>
		<td colspan="2" width="75%" align="left">
			<input type="SUBMIT" name="Submit" value="Edit Author" class="text" onClick="return verify();">&nbsp;&nbsp;<input type="RESET" name="Reset" value="Reset" class="text" onClick="resetAll();">
			<input type="hidden" name="author_id" value="<?php echo $author_id ?>">
			<input type="hidden" name="numInterests" value="<? echo ($counter + 1) ?>">
		</td>
	  </tr>
	</table>
</form>
<? back_button(); ?>
</body>
</html>

<?
    /* Free resultset */
    mysql_free_result($author_result);
    mysql_free_result($int_result);
    mysql_free_result($interest_result);

    /* Closing connection */
    disconnect_db($link);
?>
