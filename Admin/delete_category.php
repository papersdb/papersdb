<?php
	include 'header.php';
?>

<html>
<head>
<title>Delete Category</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<?  /* delete_category.php
		Much like delete_author.php, this page
		confirms that the user would like to 
		delete the category. Then makes sure no
		current publications are using that 
		category, if some are, it lists them. If
		no publications are using that category, then it
		is removed from the database.
	*/
	require('../functions.php');
	echo "</head>";
	/* Connecting, selecting database */
	$link = connect_db();
	if ($confirm == "yes"){
	/* Performing SQL query */
	$cat_query = "SELECT cat_id FROM category WHERE category=\"$category\"";
	$cat_result = mysql_query($cat_query) or die("Query failed : " . mysql_error());
	$cat_array = mysql_fetch_array($cat_result, MYSQL_ASSOC);
	$cat_id = $cat_array[cat_id];
	
	$cat_query = "SELECT pub_id FROM pub_cat WHERE cat_id=$cat_id";
	$cat_result = mysql_query($cat_query) or die("Query failed : " . mysql_error());
	$i = 0;
	while($cat_array = mysql_fetch_array($cat_result, MYSQL_ASSOC))
	{
		$pub_id = $cat_array['pub_id'];
		$pub_query = "SELECT title FROM publication WHERE pub_id=$pub_id";
		$pub_result = mysql_query($pub_query) or die("Query failed : " . mysql_error());
		$pub_array = mysql_fetch_array($pub_result, MYSQL_ASSOC);
		$titles[$i] = $pub_array['title'];
		$i++;		
	
	}
	if($titles[0] != null)
		{
			echo "<b>Deletion Failed</b><BR>";
			echo "The following publications are currently using this category:<BR>";
			for($r=0; $r<$i; $r++)
				echo "<b>".$titles[$r]."</b><BR>";
			echo "You must change the category of the following publication(s) in order to delete this category.";
			echo "<BR><a href=\"./\">Back to Admin Page</a>";
			$cat_id = null;
			disconnect_db($link);
			exit();
		}
	/* This is where the actual deletion happens. */
	if ($cat_id != null) {
		$query = "DELETE FROM cat_info WHERE cat_id = $cat_id";
		query_db($query);
		$query = "DELETE FROM category WHERE cat_id = $cat_id";
		query_db($query);
		echo "<body>You have successfully removed the following category from the database: <b>$category</b>";
		echo "<br><a href=\"delete_category.php\">Delete another category</a>";
		echo "<br><a href=\"./\">Back to Admin Page</a>";
		echo "<br><br></body></html>";
		disconnect_db($link);
		exit();
	}
	}

$cat_query = "SELECT category FROM category";
$cat_result = mysql_query($cat_query) or die("Query failed : " . mysql_error());

	
	
?>


<body><h3>Delete Category</h3><br>
<form name="deleter" action="./delete_category.php?confirm=yes" method="POST" enctype="application/x-www-form-urlencoded" target="_self">
	<table width="750" border="0" cellspacing="0" cellpadding="6">
	<tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Select a category to delete: </b></font></td>
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
		</td>
	  </tr>
	  <tr>
	  	  
		<td width="100%" colspan="2">
		  <input type="SUBMIT" name="Confirm" value="Delete" class="text">
		  <input type="button" value="Cancel" onclick="history.back()">
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