<html>
<head>
<title>View Author</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	require('lib_dbfunctions.php');
	
	if ($popup != "true" ) {
		include 'header.php';
	}

    /* Connecting, selecting database */
	$link = connect_db();

	/* Performing SQL query */
	$author_query = "SELECT * FROM author WHERE author_id=$author_id";
	$author_result = mysql_query($author_query) or die("Query failed : " . mysql_error());
	$author_array = mysql_fetch_array($author_result, MYSQL_ASSOC);
	
	$int_query = "SELECT interest.interest FROM interest, author_interest WHERE interest.interest_id=author_interest.interest_id AND author_interest.author_id=$author_id";
	$int_result = mysql_query($int_query) or die("Query failed : " . mysql_error());
?>

<body><br>
	<table width="450" border="0" cellspacing="0" cellpadding="6">
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Name: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><b><? echo $author_array['name'] ?></b></font></td>
	  </tr>
	  <?php if (isset($author_array['title']) && trim($author_array['title']) != "") {?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Title: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $author_array['title'] ?></font></td>
	  </tr>
	  <?php } ?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Email: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $author_array['email'] ?></font></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Organization: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $author_array['organization'] ?></font></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Webpage: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? if (isset($author_array['webpage']) && trim($author_array['webpage']) != "") echo "<a href=\"" . $author_array['webpage'] . "\">" . $author_array['webpage'] . "</a>"; else echo "none"; ?></font></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Interest(s): </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2">
			<?
				while ($int_line = mysql_fetch_array($int_result, MYSQL_ASSOC)) {
					echo $int_line[interest] . "<br>";
				}
			?>
			</font>
		</td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><a href="./list_publication.php?type=view&author_id=<?php echo $author_id; ?>">View publications</a></font></td>
		<td width="75%"></td>
	  </tr>
	  <? if ($popup == "true") { ?>
	  <tr>
		<td>&nbsp;</td>
		<td align="right"><a href="javascript:close();">Close Window</a></td>
	  </tr>
	  <? } ?>
	</table>
</body>
</html>

<?
    /* Free resultset */
    mysql_free_result($author_result);
    mysql_free_result($int_result);

    /* Closing connection */
    disconnect_db($link);
?>
