<?
	include 'header.php';
?>

<html>
<head>
<title><? if ($type == "view") echo "View"; else if ($type == "edit") echo "Edit"; else echo "Delete"; ?> Publication</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	require('lib_dbfunctions.php');

	/* Connecting, selecting database */
	$link = connect_db();

	/* Performing SQL query */
	if (isset($author_id)) {
		$pub_query = "SELECT p.pub_id, p.title, p.paper,
				p.abstract, p.keywords, p.published, p.updated
			FROM publication p, pub_author a
			WHERE a.author_id = " . $author_id . "
			AND a.pub_id = p.pub_id
			ORDER BY p.title ASC";
		$author_query = "SELECT name FROM author WHERE author_id = " . $author_id;
		$author_result = query_db($author_query);
		$author_line = mysql_fetch_array($author_result, MYSQL_ASSOC);
		$author_name = $author_line[name];
	}
	else
		$pub_query = "SELECT * FROM publication ORDER BY title ASC";
	$pub_result = mysql_query($pub_query) or die("Query failed : " . mysql_error());
?>

<body><br>
	<table width="750" border="0" cellspacing="0" cellpadding="6">
		<tr>
			<td><font face="Arial, Helvetica, sans-serif" size="2"><b><u>Please select the publication you wish to <? if ($type == "view") echo "view"; else if ($type == "edit") echo "edit"; else echo "delete"; if (isset($author_id)) echo " from author ". $author_name; ?>:</u></b></font></td>
		</tr>
		<? 
			if ($type == "view") {		
				while ($pub_line = mysql_fetch_array($pub_result, MYSQL_ASSOC)) {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li><a href=\"view_publication.php?pub_id=" . $pub_line[pub_id] . "\">" . $pub_line[title] . "</a></font></td></tr>";
			   } 
			}
			else if ($type == "edit") {
				if (!isset($author_id) || $author_id == "") {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"list_author.php?type=pubedit\">(List publications by specific author for editing)</a></font></td></tr>";
				}
				while ($pub_line = mysql_fetch_array($pub_result, MYSQL_ASSOC)) {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li><a href=\"add_publication.php?pub_id=" . $pub_line[pub_id] . "\">" . $pub_line[title] . "</a></font></td></tr>";
				}
			}
			else if ($type == "delete") {
				if (!isset($author_id) || $author_id == "") {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"list_author.php?type=pubdelete\">(List publications by specific author for deletion)</a></font></td></tr>";
				}
				while ($pub_line = mysql_fetch_array($pub_result, MYSQL_ASSOC)) {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li><a href=\"delete_publication.php?pub_id=" . $pub_line[pub_id] . "\">" . $pub_line[title] . "</a></font></td></tr>";
				}
			}
			else {		
				while ($pub_line = mysql_fetch_array($pub_result, MYSQL_ASSOC)) {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li><a href=\"add_publication.php?pub_id=" . $pub_line[pub_id] . "\">" . $pub_line[title] . "</a></font></td></tr>";
			   } 
			}
	   	?>
	</table>
</body>
</html>

<?
    /* Free resultset */
    mysql_free_result($pub_result);

    /* Closing connection */
    disconnect_db($link);
?>
