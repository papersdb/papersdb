<html>
<head>
<title>Keywords</title></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	/* keywords.php
		This page was made for users who are to stuck
		to what to add to the "keywords" field
		in add_publication.php. It displays a list of all
		the keywords used previously in publications
		and a checkbox on which ones the user would like to add
		for his/her publication.
	*/

function add_to_array($entry, &$thearray){
	$entry = trim($entry);
	$found = false;
	for($a = 0; $a < count($thearray); $a++)
		if($thearray[$a] == $entry)
			$found = true;
	if(!$found)
		$thearray[count($thearray)] = $entry;
}
	require('../functions.php');
	/* Connecting, selecting database */
    $link = connect_db();

    /* Performing SQL query */
    $info_query = "SELECT DISTINCT keywords FROM publication";
    $info_result = mysql_query($info_query) or die("Query failed : " . mysql_error());
	$num_rows = mysql_num_rows($info_result);
	while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)){
		$temp_array = split(";",$info_line[keywords]);
		for($a = 0; $a < count($temp_array); $a++)
			if($temp_array[$a] != "")
				add_to_array($temp_array[$a], $keywordarray); 
	}
	sort($keywordarray);
?>

<script language="JavaScript" type="text/JavaScript">

function dataKeep(num) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["keywordsForm"].elements.length; i++) {
		if ((document.forms["keywordsForm"].elements[i].value != "") && (document.forms["keywordsForm"].elements[i].value != null)) {
			if (info_counter > 0) {
				temp_qs = temp_qs + "&";
			}
			
			if (document.forms["keywordsForm"].elements[i].type == 'checkbox') {
				if (document.forms["keywordsForm"].elements[i].checked != false) {
					temp_qs = temp_qs + document.forms["keywordsForm"].elements[i].name + "=" + document.forms["keywordsForm"].elements[i].value;
				}
			}
			else {
				temp_qs = temp_qs + document.forms["keywordsForm"].elements[i].name + "=" + document.forms["keywordsForm"].elements[i].value;
			}
			
			info_counter++;
		}
	}
	
	temp_qs = temp_qs.replace(" ", "%20");
	//location.reload("./add_category.php?<? echo $_SERVER['QUERY_STRING'] . "&newFields=" ?>" + num + "&" + temp_qs);
	window.open("./keywords.php?<? echo $_SERVER['QUERY_STRING'] ?>&" + temp_qs, "Add");
}
function closewindow(){ window.close();}
function resetAll() {
	//location.reload("./add_category.php?<? echo $_SERVER['QUERY_STRING'] . "&newFields=0" ?>");
	window.open("./keywords.php?<? echo $_SERVER['QUERY_STRING'] ?>", "Add");
}

</script>

<body>
<h3>Keywords</h3>
<? //echo $_SERVER['QUERY_STRING']
?>
<form name="keywordsForm" action="./add_publication.php?<? echo $_SERVER['QUERY_STRING'] ?>&#keywords" method="POST" enctype="application/x-www-form-urlencoded" target="add_publication.php" onsubmit="setTimeout('self.close()',0);">
	Select the keywords you would like to use in your publication: <a href="../help.php" target="_blank" onClick="window.open('../help.php?helpcat=Add Category', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a>
	<table border="0" cellspacing="0" cellpadding="6">
	  <tr>
		<td align="left">
			<? 
				for($b = 0; $b < count($keywordarray); $b++)  {
					echo "<input name=\"keyword[" . $b . "]\" type=\"checkbox\" value=\"" . $keywordarray[$b] . "\"";
					echo ">" . $keywordarray[$b] . "</input><br>";
					if (($b+1)%30 == 0) echo "</td><td  align=\"left\" valign=\"top\">";
				}
			?>
		</td>
	  </tr>
	 </table>
	 <center>
			<input type="SUBMIT" name="Submit" value="Use Keywords" class="text">&nbsp;&nbsp;&nbsp;
			<input type="RESET" name="Reset" value="Reset" class="text" onClick="resetAll();">&nbsp;&nbsp;&nbsp;
			<input type="RESET" name="Cancel" value="Cancel" class="text" onClick="closewindow();">
			<input type="hidden" name="keywordsSubmitted" value="true">
			<input type="hidden" name="keywordcount" value="<? echo count($keywordarray); ?>">
	</center>

	
</form>

</body>
</html>

<?
    /* Free resultset */
    mysql_free_result($info_result);

    /* Closing connection */
    disconnect_db($link);
?>
