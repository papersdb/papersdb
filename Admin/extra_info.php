<?php ;

// $Id: extra_info.php,v 1.2 2006/06/07 02:19:36 aicmltec Exp $

/**
 * \file
 *
 * \brief
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pageConfig.php';

htmlHeader('add_venue', 'Add Category');

if (!$logged_in) {
    echo '<body>';
    pageHeader();
    echo "<div id='content'>\n";
    loginErrorMessage();
}

$db =& dbCreate();

$form = new HTML_QuickForm('extrainfoForm', 'post',
                           './add_publication.php?'.$_SERVER['QUERY_STRING'],
                           'add_publication.php');

?>

<html>
<head>
<title>Extra Information Options</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	/* extra_info.php
		This page was made for users who are to stuck
		to what to add to the "extra information" field
		in add_publication.php. It displays a list of all
		the extra information used previously in publications
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
    $info_query = "SELECT DISTINCT extra_info FROM publication";
    $info_result = mysql_query($info_query) or die("Query failed : " . mysql_error());
	$num_rows = mysql_num_rows($info_result);
	while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)){
	    $temp_string = str_replace(";",",",$info_line[extra_info]);
		$temp_array = split(",",$temp_string);
		$temp_array2 = array();
		for($a = 0; $a < count($temp_array); $a++){
			$i = 0;
			if($temp_array[$a] != "")
				if(((strpos($temp_array[$a],"("))&&(!strpos($temp_array[$a],")")))&&($a < (count($temp_array)-1))){
					$tempstring = $temp_array[$a];
					$ran = false;
					do{ $a++;
						$tempstring .= ",".$temp_array[$a];
						}while(!strpos($temp_array[$a],")"));
						array_push($temp_array2, $tempstring);
				}
				else array_push($temp_array2, $temp_array[$a]);
		}
		for($a = 0; $a < count($temp_array2); $a++){
			$temp_array2[$a] = trim($temp_array2[$a]);
			if($temp_array2[$a] != "")
				add_to_array($temp_array2[$a], $extra_info_array);
		}
	}
	sort($extra_info_array);
?>

<script language="JavaScript" type="text/JavaScript">

function dataKeep(num) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["extrainfoForm"].elements.length; i++) {
		if ((document.forms["extrainfoForm"].elements[i].value != "") && (document.forms["extrainfoForm"].elements[i].value != null)) {
			if (info_counter > 0) {
				temp_qs = temp_qs + "&";
			}

			if (document.forms["extrainfoForm"].elements[i].type == 'checkbox') {
				if (document.forms["extrainfoForm"].elements[i].checked != false) {
					temp_qs = temp_qs + document.forms["extrainfoForm"].elements[i].name + "=" + document.forms["extrainfoForm"].elements[i].value;
				}
			}
			else {
				temp_qs = temp_qs + document.forms["extrainfoForm"].elements[i].name + "=" + document.forms["extrainfoForm"].elements[i].value;
			}

			info_counter++;
		}
	}

	temp_qs = temp_qs.replace(" ", "%20");
	//location.reload("./add_category.php?<? echo $_SERVER['QUERY_STRING'] . "&newFields=" ?>" + num + "&" + temp_qs);
	window.open("./extra_info.php?<? echo $_SERVER['QUERY_STRING'] ?>&" + temp_qs, "Add");
}
function closewindow(){ window.close();}
function resetAll() {
	//location.reload("./add_category.php?<? echo $_SERVER['QUERY_STRING'] . "&newFields=0" ?>");
	window.open("./extra_info.php?<? echo $_SERVER['QUERY_STRING'] ?>", "Add");
}

</script>

<body>
<h3>Extra Information Options</h3>
<? //echo $_SERVER['QUERY_STRING']
?>
<form name="extrainfoForm2" action="./add_publication.php?<? echo $_SERVER['QUERY_STRING'] ?>&#extra" method="POST" enctype="application/x-www-form-urlencoded" target="add_publication.php" onsubmit="setTimeout('self.close()',0);">
	Select the extra information that fits your publication: <a href="../help.php" target="_blank" onClick="window.open('../help.php?helpcat=Add Category', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a>
	<table border="0" cellspacing="0" cellpadding="6">
	  <tr>
		<td align="left">
			<?
				for($b = 0; $b < count($extra_info_array); $b++)  {
					echo "<input name=\"extra[" . $b . "]\" type=\"checkbox\" value=\"" . str_replace("\"","'",$extra_info_array[$b]) . "\"";
					echo ">" . $extra_info_array[$b] . "</input><br>";
					if (($b+1)%30 == 0) echo "</td><td  align=\"left\" valign=\"top\">";
				}
			?>
		</td>
	  </tr>
	 </table>
	 <center>
			<input type="SUBMIT" name="Submit" value="Use" class="text">&nbsp;&nbsp;&nbsp;
			<input type="RESET" name="Reset" value="Reset" class="text" onClick="resetAll();">&nbsp;&nbsp;&nbsp;
			<input type="RESET" name="Cancel" value="Cancel" class="text" onClick="closewindow();">
			<input type="hidden" name="extrainfoSubmitted" value="true">
			<input type="hidden" name="extracount" value="<? echo count($extra_info_array); ?>">
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
