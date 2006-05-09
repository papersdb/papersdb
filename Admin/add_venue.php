<html>
<head>
<title>Add Publication Venue</title>
<style type="text/css">
    #venuetable tbody tr.even td {
      background-color: #eee;
    }
    #venuetable tbody tr.odd  td {
      background-color: #fff;
    }
  
<!--
a:link {
	color: #000099;
}
a:visited {
	color: #000099;
}
a:hover {
	color: #0066FF;
}
-->
</style>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	/* add_venue.php
		This page, displays, edits and adds venues. I prefer
		this structure over the way categories and authors was handled.
		
		Depending on a the varaible passed is what segment of the page is used.
		If $status == view then, it displays a list of all the venues and some
		information about them. It links to whether you would like to add a new
		venue, or edit/delete an existing one.
		
		If there is no variable passed, it displays the form to add a new venue.
		
		If $status == change, then it displays the same form, but with the values
		already filled in, as the user is editing a venue.
		
		If $submit == true then it takes the input it passed itself and adds it
		to the database. If it is passed a venue_id then it replaces the information,
		if it is not passed a venue_id, it adds a new id to the database and puts the
		information there.
	
	
	*/

	require('../functions.php');
		$link = connect_db();
	/* Connecting, selecting database */	
if($submit == "true"){
		if($year == "--")
			$year = "0000";
		if($month == "--")
			$month = "00";
		if($day == "--")
			$day = "00";
			
		 //add http:// to webpage address if needed
	    if(strpos($venue_url, "http") === FALSE)
			{
		    $venue_url = "http://".$venue_url;
			}
		
		$date = $year . "-" . $month . "-" . $day;
			
		$venue = str_replace("\"","'",$venue);
		
		if($venue_id == ""){
			$venue_query = "INSERT INTO venue (venue_id, title, name, url, type, data, editor, date) VALUES (NULL, \"$venue_title\", \"$venue_name\", \"$venue_url\", \"$venue_type\", \"$venue_data\", \"$venue_editor\", \"$date\")";
			query_db($venue_query);  
			echo "<body onLoad=\"window.opener.location.reload(); window.close();\">";
			include 'header.php';
			echo "You have successfully added the venue $venue_title.";
			echo "<br><a href=\"./add_venue.php\">Add another venue</a>";
		}
		else{
			$venue_query = "UPDATE venue SET title = \"$venue_title\", name = \"$venue_name\", url = \"$venue_url\", type = \"$venue_type\", data = \"$venue_data\", editor = \"$venue_editor\", date = \"$date\" WHERE venue_id = $venue_id"; 
			$venue_result = query_db($venue_query);
			echo "<body>";
			include 'header.php';
			echo "You have successfully edited the venue $venue_title.";
			echo "<br><a href=\"./add_venue.php?status=edit\">Edit another venue</a>";
		}
		echo "<br><a href=\"./\">Administrator Page</a></body></html>";
		disconnect_db($link);
		exit();
	}
?>

<script language="JavaScript" type="text/JavaScript">

function verify() {
	if (document.forms["venueForm"].elements["title"].value == "") {
		alert("Please enter a title for this venue.");
		return false;
	}	
	
	if (document.forms["venueForm"].elements["name"].value == "") {
		alert("Please enter information for this venue.");
		return false;
	}

	return true;
}
function closewindow(){ window.close();}
function dataKeep() {
	var temp_qs = "";
	var info_counter = 0;
	var is_edit = 0;

	for (i = 0; i < document.forms["venueForm"].elements.length; i++) {
		if ((document.forms["venueForm"].elements[i].value != "") && 
                    (document.forms["venueForm"].elements[i].value != null)) {
			if (info_counter > 0) {
			 temp_qs = temp_qs + "&";
			}
			if(document.forms["venueForm"].elements[i].name == "venue_id")
				is_edit = 1;
			
			if(document.forms["venueForm"].elements[i].name == "venue_type"){
					if(document.forms["venueForm"].elements[i].checked == true)
						temp_qs = temp_qs + document.forms["venueForm"].elements[i].name + "=" + document.forms["venueForm"].elements[i].value.replace("\"","'");
			}
			
			else
				temp_qs = temp_qs + document.forms["venueForm"].elements[i].name + "=" + document.forms["venueForm"].elements[i].value;
			info_counter++;
		}
	}
	temp_qs = temp_qs.replace("\"", "?");
	temp_qs = temp_qs.replace(" ", "%20");
	if(is_edit == 1)
		location.href = "http://" + "<? echo $_SERVER["HTTP_HOST"]; echo $PHP_SELF; ?>?status=change&" + temp_qs;
	else
		location.href = "http://" + "<? echo $_SERVER["HTTP_HOST"]; echo $PHP_SELF; ?>?" + temp_qs;
	
		
}
</script>

<body>
<? if($status == "view"){
include 'header.php';
$venue_query = "SELECT * FROM venue";
$venue_result = mysql_query($venue_query) or die("Query failed : " . mysql_error());


?>
<h2><b><u>Publication Venues</u></b></h2>
<h3><a href="add_venue.php?popup=false"><b>Add New Publication Venue</b></a></h3>
<table id="venuetable" cellpadding="6">
<? $count = 0;
	while ($venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC)) {
					$venue_query2 = "SELECT * FROM venue WHERE venue_id=".$venue_line[venue_id];
    				$venue_result2 = mysql_query($venue_query2) or die("Query failed : " . mysql_error());
					$venue_line2 = mysql_fetch_array($venue_result2, MYSQL_ASSOC);
					$venue_name = $venue_line[name];
					$venue_url = $venue_line[url];
					$venue_type = $venue_line[type];
					$venue_data = $venue_line[data];
					$venue_editor = $venue_line[editor];
					echo "<tr class=\""; if($count%2 == 0) echo "odd"; else echo "even"; echo "\"><td><b>".$venue_line[title]."</b><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
					echo "<b>".$venue_type.":&nbsp;</b></font>";
					if($venue_url != "")
						echo " <a href=\"".$venue_url."\" target=\"_blank\">";
					echo $venue_name;
					if($venue_url != "")
						echo "</a>";
					if($venue_data != ""){
						echo "<br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
						if($venue_type == "Conference")
							echo "<b>Location:&nbsp;</b>";
						else if($venue_type == "Journal")
							echo "<b>Publisher:&nbsp;</b>";
						else if($venue_type == "Workshop")
							echo "<b>Associated Conference:&nbsp;</b>";
						echo $venue_data;
					}
					if($venue_editor != "")
						echo "<br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><B>Editor:&nbsp;</b>".$venue_editor;
					echo "</font></td><td align\"right\"><b><a href=\"add_venue.php?status=change&venue_id=".$venue_line[venue_id]."\">Edit</a><BR><a href=\"delete_venue.php?confirm=check&venue=".$venue_line[venue_id]."\">Delete</a></b></td></tr>";
					$count++;
	}
?>
</table>
<h3><a href="add_venue.php?popup=false"><b>Add New Publication Venue</b></a></h3>
<? back_button();
}
else { 

if($status == "change")
{		
		$venue_query = "SELECT * FROM venue WHERE venue_id=$venue_id";
		$venue_result = mysql_query($venue_query) or die("Query failed : " . mysql_error());
		$venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC);
		if($venue_title == "")$venue_title = $venue_line[title];
		if($venue_name == "")$venue_name = $venue_line[name];
		if($venue_url == "")$venue_url = $venue_line[url];
		if($venue_type == "")$venue_type = $venue_line[type];
		if($venue_data == "")$venue_data = $venue_line[data];
		if($venue_editor == "")$venue_editor = $venue_line[editor];
		$date = $venue_line[date];
		$date = split("-", $date);
		$year = $date[0];
		$month = $date[1];
		$day = $date[2];
}
if($popup == "false") include 'header.php'; 
if(($status == "change")||($editmode == "true")){

?>
<h3>Edit Venue</h3>
<form name="venueForm" action="add_venue.php?submit=true" method="POST" enctype="application/x-www-form-urlencoded">
<input type="hidden" name="editmode" value="true">
<input type="hidden" name="venue_id" value="<? echo $venue_id; ?>">
<? } else{  ?>
<h3>Add Venue</h3>
<form name="venueForm" action="add_venue.php?submit=true" method="POST" enctype="application/x-www-form-urlencoded">
<? } 
	if($popup == "false") echo "<input type=\"hidden\" name=\"popup\" value=\"false\">";
?>
	<table width="590" border="0" cellspacing="0" cellpadding="6">
	 <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Type:</b></font></td>
		<td colspan="2" width="75%">
			<INPUT TYPE=RADIO NAME="venue_type" VALUE="Journal" <? if($venue_type == "Journal") echo "CHECKED"; ?> onClick="javascript:dataKeep();">Journal<BR>
			<INPUT TYPE=RADIO NAME="venue_type" VALUE="Conference" <? if($venue_type == "Conference") echo "CHECKED"; ?> onClick="javascript:dataKeep();">Conference<BR>
			<INPUT TYPE=RADIO NAME="venue_type" VALUE="Workshop" <? if($venue_type == "Workshop") echo "CHECKED"; ?> onClick="javascript:dataKeep();">Workshop
		</td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Internal Title: </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="venue_title" size="50" maxlength="250" value="<? echo $venue_title; ?>"></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Venue Name: </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="venue_name" size="50" maxlength="250" value="<? echo $venue_name; ?>"></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Venue URL: </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="venue_url" size="50" maxlength="250" value="<? echo $venue_url; ?>"></td>
	  </tr>

	  
	
	  <?  if($venue_type != ""){ ?>
	  
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b> <? 
		if($venue_type == "Conference")
			echo "Location:";
		else if($venue_type == "Journal")
			echo "Publisher:";
		else if($venue_type == "Workshop")
			echo "Associated Conference:";
		  ?> </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="venue_data" size="50" maxlength="250" value="<? echo $venue_data; ?>"></td>
	  </tr>  
	  <? if($venue_type == "Workshop"){ ?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Editor: </b></font></td>
		<td colspan="2" width="75%"><input type="text" name="venue_editor" size="50" maxlength="250" value="<? echo $venue_editor; ?>"></td>
	 </tr>
	  
	   <? }
	  	 if(($venue_type == "Conference")||($venue_type == "Workshop")){ ?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Date: </b></font></td>

		<td width="75%">
			<?  
			
				if (($month == "")||($status != "change"))  
			   	   generate_select_month("month", 1, 12);
			   else 
			       generate_select_month("month", 1, 12, $month);   
            ?>&nbsp;&nbsp;<? 
			   if (($day == "")||($status != "change"))  
			       generate_select_date("day", 1, 31);
			   else  
			       generate_select_date("day", 1, 31, $day);
             ?>&nbsp;&nbsp;<? 
			 $today = getdate();
			   if (($year == "")||($status != "change"))  
                 generate_select_date("year", 1960, $today[year]);
			  else 	{
			     generate_select_date("year", 1960, $today[year], $year);
				 }     
             ?>
		</td>
	  </tr>
	  <? } ?>
	  <tr>
		
			
		<td colspan="2" width="75%" align="left">
			<? if(($status == "change")||($editmode == "true")) { ?>
			<input type="hidden" name="venue_id" value="<? echo $venue_id; ?>">
			<input type="SUBMIT" name="Submit" value="Edit Venue" class="text" onClick="return verify();">&nbsp;&nbsp;
			<? } else {?>
			<input type="SUBMIT" name="Submit" value="Add Venue" class="text" onClick="return verify();">&nbsp;&nbsp;
			<? } ?>
			<input type="RESET" name="Reset" value="Reset" class="text" onClick="resetAll();">&nbsp;&nbsp;
			<input type="RESET" name="Cancel" value="Cancel" class="text" onClick="<? if($popup == "false")echo "history.back()"; else echo "closewindow();"; ?>">
			
		</td>
	  </tr>
	  <? } ?>
	</table>
</form>
<? } 
if($popup == "false") back_button();
 ?>
</body>
</html>

<?
 

    /* Closing connection */
    disconnect_db($link);
?>
