<?
	include 'header.php';
?>

<html>
<head>
<title><? if ($type == "view") echo "View"; else if ($type == "edit") echo "Edit"; else if ($type == "pubedit") echo "Edit Publication From"; else echo "Delete Publication From" ?> Author</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	require('lib_dbfunctions.php');
    /* Connecting, selecting database */
    $link = connect_db();

    /* Performing SQL query */
    $author_query = "SELECT * FROM author ORDER BY name ASC";
    $author_result = query_db($author_query);
?>

<body><br>
	<table width="750" border="0" cellspacing="0" cellpadding="6">
		<tr>
			<td><font face="Arial, Helvetica, sans-serif" size="2"><b><u>Please select the author <? if ($type == "pubedit" || $type == "pubdelete") echo "whose publication you wish to "; if ($type == "view") echo "view"; else if ($type == "edit") echo "edit"; else echo "delete"; ?>:</u></b></font></td>
		</tr>
		<? 
			if ($type == "view") {		
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li><a href=\"view_author.php?author_id=" . $author_line[author_id] . "\">" . $author_line[name] . "</a></font></td></tr>";
				}
			}
			else if ($type == "edit") {
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li><a href=\"edit_author.php?author_id=" . $author_line[author_id] . "\">" . $author_line[name] . "</a></font></td></tr>";
			   } 
			}
			else if ($type == "pubedit") {
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li><a href=\"list_publication.php?author_id=" . $author_line[author_id] . "&type=edit\">" . $author_line[name] . "</a></font></td></tr>";
			   } 
			}
			else if ($type == "pubdelete") {
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li><a href=\"list_publication.php?author_id=" . $author_line[author_id] . "&type=delete\">" . $author_line[name] . "</a></font></td></tr>";
			   } 
			}
			/*else {		
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
					echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li><a href=\"add_publication.php?pub_id=" . $pub_line[pub_id] . "\">" . $pub_line[title] . "</a></font></td></tr>";
				} 
			}*/
	   	?>
	</table>
</body>
</html>

<?
    /* Free resultset */
    mysql_free_result($author_result);

    /* Closing connection */
    disconnect_db($link);
?>
