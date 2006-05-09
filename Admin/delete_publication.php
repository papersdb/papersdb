<?php
	include 'header.php';
?>

<html>
<head>
<title>Delete Publication</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<?
	/* delete_publication.php
		This page confirms that the user would
		like to delete the following publication and 
		then removes it from the database once confirmation
		has been given.
	
	*/
	
	require('../functions.php');
	echo "</head>";
	/* Connecting, selecting database */
	$link = connect_db();

	if (!isset($pub_id) || pub_id=="") {
		die("Error: publication id not set.");
	}
	
	$pub_query = "SELECT title FROM publication WHERE pub_id=$pub_id";
	$pub_result = query_db($pub_query);
	$pub_array = mysql_fetch_array($pub_result, MYSQL_ASSOC);
	$title = $pub_array['title'];
	
	/* This is where the actual deletion happens. */
	if (isset($confirm) && $confirm == "yes") {
		$query = "DELETE FROM pub_cat_info WHERE pub_id = $pub_id";
		query_db($query);
		$query = "DELETE FROM pub_cat WHERE pub_id = $pub_id";
		query_db($query);
		$query = "DELETE FROM pub_add WHERE pub_id = $pub_id";
		query_db($query);
		$query = "DELETE FROM pub_author WHERE pub_id = $pub_id";
		query_db($query);
		$query = "DELETE FROM publication WHERE pub_id = $pub_id";
		query_db($query);
		echo "<body>You have successfully removed the following from the database: <b>$title</b>";
		echo "<br><a href=\"../list_publication.php?type=view&admin=true\"><b>Back to Publications</b></a>";
		echo "<br><a href=\"./\"><B>Back to Admin Page</b></a>";
		echo "<br><br></body></html>";
		system("rm -rf " . $absolute_files_path . "/" . $pub_id . "/");
		disconnect_db($link);
		exit();
	}

	/* Performing SQL query */
	
	$cat_query = "SELECT category.category FROM category, pub_cat WHERE category.cat_id=pub_cat.cat_id AND pub_cat.pub_id=$pub_id";
	$cat_result = query_db($cat_query);
	$cat_array = mysql_fetch_array($cat_result, MYSQL_ASSOC);
	
	$author_query = "SELECT author.author_id, author.name FROM author, pub_author WHERE author.author_id=pub_author.author_id AND pub_author.pub_id=$pub_id";
	$author_result = query_db($author_query);
	
?>

<body><h3>Delete Publication</h3><br>
	<table width="750" border="0" cellspacing="0" cellpadding="6">
	<tr>
		<td width="100%" colspan="2"><font face="Arial, Helvetica, sans-serif" size="2"><b>Delete the following paper?</b></font></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Title: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><b><? echo $pub_array[title] ?></b></font></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Category: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $cat_array[category] ?></font></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2" color="#990000"><b>Paper: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><a href=".<? echo $pub_array[paper] ?>">View Paper</a></font></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Authors: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2">
			<?
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
					echo "<a href=\"./view_author.php?popup=true&author_id=$author_line[author_id]\" target=\"_blank\" onClick=\"window.open('./view_author.php?popup=true&author_id=$author_line[author_id]', 'Help', 'width=500,height=250,scrollbars=yes,resizable=yes'); return false\">";
					echo $author_line[name];
					echo "</a><br>";
				}
			?>
			</font>
		</td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Date Published: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $pub_array[published] ?></font></td>
	  </tr>
	  <tr>
	  <form name="deleter" action="./delete_publication.php?pub_id=<?php echo $pub_id; ?>&confirm=yes" method="POST" enctype="application/x-www-form-urlencoded" target="_self">
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
	mysql_free_result($pub_result);
	mysql_free_result($cat_result);
	mysql_free_result($author_result);

	/* Closing connection */
	disconnect_db($link);
?>