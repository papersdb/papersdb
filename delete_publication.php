<?php
	include 'header.php';
?>

<html>
<head>
<title>Delete Publication</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<?
	require('lib_dbfunctions.php');
	if (isset($confirm) && $confirm == "yes"){
		echo "<script language='javascript'>setTimeout(\"top.location.href = './'\",5000)</script>";
	}
	echo "</head>";
	/* Connecting, selecting database */
	$link = connect_db();

	if (!isset($pub_id) || pub_id=="") {
		die("Error: publication id not set.");
	}
	
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
		echo "<body>You have successfully removed the following publication from the database: $title.";
		echo "<br><br>You will be transported to the main page in 5 seconds.</body></html>";
		system("rm -rf " . $absolute_files_path . "/" . $pub_id . "/");
		disconnect_db($link);
		exit();
	}

	/* Performing SQL query */
	$pub_query = "SELECT * FROM publication WHERE pub_id=$pub_id";
	$pub_result = query_db($pub_query);
	$pub_array = mysql_fetch_array($pub_result, MYSQL_ASSOC);
	
	$cat_query = "SELECT category.category FROM category, pub_cat WHERE category.cat_id=pub_cat.cat_id AND pub_cat.pub_id=$pub_id";
	$cat_result = query_db($cat_query);
	$cat_array = mysql_fetch_array($cat_result, MYSQL_ASSOC);
	
	$author_query = "SELECT author.author_id, author.name FROM author, pub_author WHERE author.author_id=pub_author.author_id AND pub_author.pub_id=$pub_id";
	$author_result = query_db($author_query);
	
?>

<body><br>
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
		<td width="100%" colspan="2">&nbsp; &nbsp; &nbsp;<a href="./delete_publication.php?pub_id=<?php echo $pub_id; ?>&confirm=yes">Delete it</a> &nbsp; &nbsp; &nbsp;<a href="./">Don't Delete It</a></td>
	  </tr>
	</table>
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