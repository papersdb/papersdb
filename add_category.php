<html>
<head>
<title>Add Category</title></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	require('lib_dbfunctions.php');
	/* Connecting, selecting database */
    $link = connect_db();

    /* Performing SQL query */
    $info_query = "SELECT name FROM info";
    $info_result = mysql_query($info_query) or die("Query failed : " . mysql_error());
	$num_rows = mysql_num_rows($info_result);
?>

<script language="JavaScript" type="text/JavaScript">

function verify() {
	if (document.forms["catForm"].elements["catname"].value == "") {
		alert("Please enter a name for the new category.");
		return false;
	}
	
	return true;
}

function dataKeep(num) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["catForm"].elements.length; i++) {
		if ((document.forms["catForm"].elements[i].value != "") && (document.forms["catForm"].elements[i].value != null)) {
			if (info_counter > 0) {
				temp_qs = temp_qs + "&";
			}
			
			if (document.forms["catForm"].elements[i].type == 'checkbox') {
				if (document.forms["catForm"].elements[i].checked != false) {
					temp_qs = temp_qs + document.forms["catForm"].elements[i].name + "=" + document.forms["catForm"].elements[i].value;
				}
			}
			else {
				temp_qs = temp_qs + document.forms["catForm"].elements[i].name + "=" + document.forms["catForm"].elements[i].value;
			}
			
			info_counter++;
		}
	}
	
	temp_qs = temp_qs.replace(" ", "%20");
	//location.reload("./add_category.php?<? echo $_SERVER['QUERY_STRING'] . "&newFields=" ?>" + num + "&" + temp_qs);
	window.open("./add_category.php?<? echo $_SERVER['QUERY_STRING'] ?>&newFields=" + num + "&" + temp_qs, "Add");
}

function resetAll() {
	//location.reload("./add_category.php?<? echo $_SERVER['QUERY_STRING'] . "&newFields=0" ?>");
	window.open("./add_category.php?<? echo $_SERVER['QUERY_STRING'] ?>&newFields=0", "Add");
}
</script>

<body>
<? //echo $_SERVER['QUERY_STRING']
?>
<form name="catForm" action="./add_publication.php?<? echo $_SERVER['QUERY_STRING'] ?>" method="POST" enctype="application/x-www-form-urlencoded" target="add_publication.php" onsubmit="setTimeout('self.close()',0);">
	<table width=""600 border="0" cellspacing="0" cellpadding="6">
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Category Name: </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="catname" size="50" maxlength="250" value="<? echo stripslashes($catname); ?>"></td>
	  </tr>
	  <tr>
		<td width="25%">
			<font face="Arial, Helvetica, sans-serif" size="2"><b>Related Field(s): </b></font><br>
			<font face="Arial, Helvetica, sans-serif" size="1"><a href="javascript:dataKeep(<? echo ($newFields + 1) ?>)">[Add Field]</a></font>
		</td>
		<td width="50%" align="left">
			<? 
				$counter = 0;
				while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)) {
					echo "<input name=\"related[" . $counter . "]\" type=\"checkbox\" value=\"" . ($counter + 1) . "\"";
					if ($related[$counter]) echo " checked";
					echo ">" . $info_line[name] . "</input><br>";
					$counter++;
					if ($counter == ceil(($num_rows / 2))) echo "</td><td width=\"55%\" align=\"left\" valign=\"top\">";
				}
			?>
		</td>
	  </tr>
	  <? for ($i = 0; $i < $newFields; $i++) { ?>
	 		<tr>
				<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Field Name: </b></font></td>
				<td colspan="2" width="75%"><input type="text" name="newField[<? echo $i ?>]" size="50" maxlength="250" value="<? echo stripslashes($newField[$i]); ?>"></td>
			</tr>
	  <? } ?>
	  <tr>
		<td width="25%">
			<a href="./help.php" target="_blank" onClick="window.open('./help.php?helpcat=Add Category', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a>
	  	</td>
		<td colspan="2" width="75%" align="left">
			<input type="SUBMIT" name="Submit" value="Add Category" class="text" onClick="return verify();">&nbsp;&nbsp;
			<input type="RESET" name="Reset" value="Reset" class="text" onClick="resetAll();">
			<input type="hidden" name="newCatSubmitted" value="true">
			<input type="hidden" name="numInfo" value="<? echo ($counter + 1) ?>">
		</td>
	  </tr>
	</table>
</form>

</body>
</html>

<?
    /* Free resultset */
    mysql_free_result($info_result);

    /* Closing connection */
    disconnect_db($link);
?>
