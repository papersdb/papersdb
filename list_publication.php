<?
 /* list_publication.php
   Lists all the publications in database. Makes each publication a link to it's own seperate page.
   If a user is logged in, he/she has the option of adding a new publication, editing any of the
   publications and deleting any of the publications.

   Pretty much identical to list_author.php
 */
		if($admin =="true")
			include 'headeradmin.php';
		else
			include 'header.php';
?>

<html>
<head>
<title>Publications</title>
<link rel="stylesheet" href="style.css">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<META NAME= "ROBOTS" CONTENT="NOINDEX">
</head>

<?
	require('functions.php');

	/* Connecting, selecting database */
	$link = connect_db();

	// If there exists an author_id, only extract the publications with that author
	// This is used when viewing an author.
	if (isset($author_id)) {
		$pub_query = "SELECT p.pub_id, p.title, p.paper,
				p.abstract, p.keywords, p.published, p.updated
			FROM publication p, pub_author a
			WHERE a.author_id = " . quote_smart($author_id) . "
			AND a.pub_id = p.pub_id
			ORDER BY p.title ASC";
		$author_query = "SELECT name FROM author WHERE author_id = " . quote_smart($author_id);
		$author_result = query_db($author_query);
		$author_line = mysql_fetch_array($author_result, MYSQL_ASSOC);
		$author_name = $author_line['name'];
	}
	// Otherwise just get all publications
	else
		$pub_query = "SELECT * FROM publication ORDER BY title ASC";
	$pub_result = query_db($pub_query);
?>

<body>
<h2><b><u>Publications <? if (isset($author_id)) echo "by ". $author_name; ?></u></b></h2>
<? if ($admin == "true"){ ?>
<h3><a href="Admin/add_publication.php"><b>Add New Publication</b></a></h3>
<? } ?>
	<table id="listtable" width="750" border="0" cellspacing="0" cellpadding="6">
		<?  $count = 0;

				$itran = false;
				while ($pub_line = mysql_fetch_array($pub_result, MYSQL_ASSOC)) {
					echo "<tr class=\"";
						if($count%2 == 0)
							echo "odd";
						else
							echo "even";
					echo "\"><td class=\"standard\"><li><a href=\"view_publication.php?"; if($admin == "true") echo "admin=true&"; echo "pub_id=" . $pub_line[pub_id] . "\">" . $pub_line[title] . "</a></td>";

					$count++;

					if($admin == "true"){
						echo "<td class=\"small\"> <a href=\"Admin/add_publication.php?pub_id="
							.$pub_line[pub_id]
							."\"><b> Edit </b></a></td>";

						echo "<td class=\"small\"> <a href=\"Admin/delete_publication.php?pub_id="
							.$pub_line[pub_id]
							."\"><b> Delete </b></a></td>";
					}
					echo "</tr> \n";
			   		$itran = true;
			   }
			   // If no publications exist, let the user know.
			   if(!$itran)
			   	echo "<tr><td class=\"standard\"><li>No publications.</td></tr>";

	   	?>
	</table>
<? back_button(); ?>
</body>
</html>

<?
    /* Free resultset */
    mysql_free_result($pub_result);

    /* Closing connection */
    disconnect_db($link);
?>
