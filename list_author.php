<?
 /* list_author.php
   Lists all the authors in database. Makes each author a link to it's own seperate page. 
   If a user is logged in, he/she has the option of adding a new author, editing any of the
   authors and deleting any of the authors.
 */
		
		if($admin =="true")
			include 'headeradmin.php';
		else
			include 'header.php';
?>

<html>
<head>
<title>Authors</title>
<link rel="stylesheet" href="style.css">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	require('functions.php');
    /* Connecting, selecting database */
    $link = connect_db();

    /* Performing SQL query */
    $author_query = "SELECT * FROM author ORDER BY name ASC";
    $author_result = query_db($author_query);
?>

<body>
<h2><b><u>Authors</u></b></h2>
<? /* This portion is used for when a new author has been added. It either says it was successful or not
      and then brings the user to this full page.
	*/
if ($admin == "true"){ 
	if($newauthor == "true")
		{
			echo "Author submitted successfully. You will be transported back to the author page in 0.01 seconds.";		
			 ?> <script language="JavaScript" type="text/JavaScript">
					location.replace('./list_author.php?type=view&admin=true');
				</script>
			<? 
			exit();
		}
	if($repeat == "true")
		{ ?>
			<script language="Javascript">
				alert ("Author already exists.")
			</script>
		<? }
//-------Full page starts now-------------
?>
<h3><a href="Admin/add_author.php?popup=false"><b>Add New Author</b></a></h3>
<? } ?>
	<table id="listtable" width="450" border="0" cellspacing="0" cellpadding="6">
		<? 
			$count = 0;
				
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
				    // Odd and even are for the different color backgrounds of each entry
					echo "<tr class=\""; 
						if($count%2 == 0) 
							echo "odd"; 
					     else echo "even"; 
					echo "\"><td class=\"standard\"><li><a href=\"view_author.php?"; 
					if($admin == "true") 
						echo "admin=true&"; 
					echo "author_id=" . $author_line[author_id] . "\">" . $author_line[name] . "</a></font></td>";
					$count++;
					
					if($admin == "true"){
						echo "<td class=\"small\"> <a href=\"Admin/edit_author.php?author_id="
							.$author_line[author_id]
							."\"><b> Edit </b></a></td>";
						
						echo "<td class=\"small\"> <a href=\"Admin/delete_author.php?author_id="
							.$author_line[author_id]
							."\"><b> Delete </b></a></td>";
					}
					echo "</tr> \n";
				}

	   	?>
	</table>
<? back_button(); ?>
</body>
</html>

<?
    /* Free resultset */
    mysql_free_result($author_result);

    /* Closing connection */
    disconnect_db($link);
?>
