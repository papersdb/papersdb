<?
	include 'header.php';
?>

<html>
<head>
<title>View Publication</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	require('lib_dbfunctions.php');
	/* Connecting, selecting database */
	$link = connect_db();

	/* Performing SQL query */
	$pub_query = "SELECT * FROM publication WHERE pub_id=$pub_id";
	$pub_result = query_db($pub_query);
	$pub_array = mysql_fetch_array($pub_result, MYSQL_ASSOC);
	
	$cat_query = "SELECT category.category FROM category, pub_cat WHERE category.cat_id=pub_cat.cat_id AND pub_cat.pub_id=$pub_id";
	$cat_result = query_db($cat_query);
	$cat_array = mysql_fetch_array($cat_result, MYSQL_ASSOC);
	
	$add_query = "SELECT additional_info.location FROM additional_info, pub_add WHERE additional_info.add_id=pub_add.add_id AND pub_add.pub_id=$pub_id";
	$add_result = query_db($add_query);
	
	$author_query = "SELECT author.author_id, author.name FROM author, pub_author WHERE author.author_id=pub_author.author_id AND pub_author.pub_id=$pub_id";
	$author_result = query_db($author_query);
	
	$info_query = "SELECT info.info_id, info.name FROM info, cat_info, pub_cat WHERE info.info_id=cat_info.info_id AND cat_info.cat_id=pub_cat.cat_id AND pub_cat.pub_id=$pub_id";
	$info_result = query_db($info_query);
	
?>

<body><br>
	<table width="750" border="0" cellspacing="0" cellpadding="6">
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
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2" color="#999999"><b>Additional Materials: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2">
			<?
				$add_count = 0;
				while ($add_line = mysql_fetch_array($add_result, MYSQL_ASSOC)) {
					echo "<a href=./" . $add_line[location] . ">";
					echo "Additional Material " . ($add_count + 1);
					echo "</a><br>";
					$add_count++;
				}
			?>
			</font>
		</td>
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
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Abstract: </b></font></td>
		<td width="75%"><textarea name="abstract" cols="70" rows="10"><? echo stripslashes($pub_array[abstract]) ?></textarea></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Keywords: </b></font></td>
		<?
			$display_array = explode(";", $pub_array[keywords]);
		?>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? for ($i = 0; $i < count($display_array); $i++) { if ($display_array[$i] != "") { echo $display_array[$i]; echo ", "; }} ?></font></td>
	  </tr>
<?	while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)) {
		$info_id = $info_line[info_id];
		$value_query = "SELECT pub_cat_info.value FROM pub_cat_info, pub_cat WHERE pub_cat.pub_id=$pub_id AND pub_cat.cat_id=pub_cat_info.cat_id AND pub_cat_info.pub_id=$pub_id AND pub_cat_info.info_id=$info_id";
		$value_result = mysql_query($value_query) or die("Query failed : " . mysql_error());
		$value_line = mysql_fetch_array($value_result, MYSQL_ASSOC);
		
		echo "<tr>";
			echo "<td width=\"25%\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><b>$info_line[name]: </b></font></td>";
			echo "<td width=\"75%\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$value_line[value]</font></td>";
		echo "</tr>";
		
    	mysql_free_result($value_result);	
	}  
?> 
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Date Published: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $pub_array[published] ?></font></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Updated: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $pub_array[updated] ?></font></td>
	  </tr>
	  <tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	  </tr>
	</table>
</body>
</html>

<?
    /* Free resultset */
    mysql_free_result($pub_result);
    mysql_free_result($cat_result);
    mysql_free_result($add_result);
    mysql_free_result($author_result);
    mysql_free_result($info_result);

    /* Closing connection */
    disconnect_db($link);
?>
