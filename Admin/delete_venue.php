<?php
	include 'header.php';
?>

<html>
<head>
<title>Delete Venue</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<?
	/* delete_venue.php
		This page confirms that the user would
		like to delete the selected venue, and 
		then removes it from the database.
	*/
	require('../functions.php');
	echo "</head>";
	/* Connecting, selecting database */
	$link = connect_db();
	if ($confirm == "yes"){
	/* This is where the actual deletion happens. */
	if ($venue != null) {
		
		$venue_query = "SELECT title FROM venue WHERE venue_id=$venue";
		$venue_result = mysql_query($venue_query) or die("Query failed : " . mysql_error());
		$venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC);
		$title = $venue_line[title];
		$query = "DELETE FROM venue WHERE venue_id=$venue";
		query_db($query);
		
		echo "<body>You have successfully removed the following venue from the database: <b>$title </b>";
		echo "<b><br><a href=\"delete_venue.php\">Delete another venue</a>";
		echo "<br><a href=\"add_venue.php?status=view\">Back to Venues Page</a>";
		echo "<br><a href=\"./\">Back to Admin Page</a>";
		echo "<br><br></b></body></html>";
		disconnect_db($link);
		exit();
	}
	}
	if($confirm == "check")
	{
		$venue_query = "SELECT title FROM venue WHERE venue_id=$venue";
		$venue_result = mysql_query($venue_query) or die("Query failed : " . mysql_error());
		$venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC);
		$title = $venue_line[title];
		echo "<BR><h3>Are you sure you want to delete the venue <B>".$title."</B>?</h3><BR>";
		?>
		<form name="delete" action="./delete_venue.php?confirm=yes" method="POST" enctype="application/x-www-form-urlencoded" target="_self">
		<input type="hidden" name="venue" value="<? echo $venue; ?>">
		<input type="SUBMIT" value="Yes" class="text">
		<input type="button" value="No" onclick="javascript:backtoAdmin();">
		</form>
		<?
		exit();
	}

$venue_query = "SELECT * FROM venue";
$venue_result = mysql_query($venue_query) or die("Query failed : " . mysql_error());
?>

<SCRIPT LANGUAGE="JavaScript">
function backtoAdmin(){
location.href=("index.php");
}
</SCRIPT>



<body><h3>Delete Venue</h3><br>
<form name="deleter" action="./delete_venue.php?confirm=check" method="POST" enctype="application/x-www-form-urlencoded" target="_self">
	<table width="750" border="0" cellspacing="0" cellpadding="6">
	<tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Select a venue to delete: </b></font></td>
		<td width="75%">
			<select name="venue" onChange="dataKeep();">
				<option value="">--- Please Select a Venue ---</option>
				<? 
					while ($venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC)) {
						echo "<option value=\"" . $venue_line[venue_id] . "\"";

						if (stripslashes($venue) == $venue_line[venue_id])
							echo " selected";

						echo ">" . $venue_line[title] . "</option>";
				 	}
				?>
			</select>
		</td>
	  </tr>
	  <tr>
	  	  
		<td width="100%" colspan="2">
		  <input type="SUBMIT" name="Confirm" value="Delete" class="text">
		  <input type="button" value="Cancel" onclick="javascript:backtoAdmin();">
		 &nbsp; &nbsp; &nbsp;</td>
	  </form>
	  </tr>
	</table>
	<? back_button(); ?>
</body>
</html>

<?
	/* Free resultset */

	/* Closing connection */
	disconnect_db($link);
?>