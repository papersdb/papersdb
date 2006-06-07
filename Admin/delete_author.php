<?php ;

// $Id: delete_author.php,v 1.2 2006/06/07 23:15:47 aicmltec Exp $

/**
 * \file
 *
 * \brief Deletes an author from the database.
 *
 * This page first confirms that the user would like to delete the specified
 * author, and then makes the actual deletion. Before the author can removed
 * though, it must not be in any publications. This is checked, and displays
 * the titles of all the publications the author is in.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pageConfig.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdVenue.php';

require_once 'HTML/QuickForm.php';

htmlHeader('delete_author', 'Delete an Author');
pageHeader();
navMenu('delete_author');
echo '<body>';
echo "<div id='content'>\n";

if (!$logged_in) {
    loginErrorMessage();
}

$db =& dbCreate();

	if (!isset($author_id) || author_id=="") {
		die("Error: publication id not set.");
	}

	/* Performing SQL query */
	$author_query = "SELECT * FROM author WHERE author_id=$author_id";
	$author_result = mysql_query($author_query) or die("Query failed : " . mysql_error());
	$author_array = mysql_fetch_array($author_result, MYSQL_ASSOC);
	$authorname = $author_array['name'];

	$author_query = "SELECT pub_id FROM pub_author WHERE author_id=$author_id";
	$author_result = mysql_query($author_query) or die("Query failed : " . mysql_error());
	$i = 0;
	while($author_array2 = mysql_fetch_array($author_result, MYSQL_ASSOC))
	{
		$pub_id = $author_array2['pub_id'];
		$pub_query = "SELECT title FROM publication WHERE pub_id=$pub_id";
		$pub_result = mysql_query($pub_query) or die("Query failed : " . mysql_error());
		$pub_array = mysql_fetch_array($pub_result, MYSQL_ASSOC);
		$titles[$i] = $pub_array['title'];
		$i++;

	}
	if($titles[0] != null)
		{
			echo "<b>Deletion Failed</b><BR>";
			echo "The following publications currently have this author:<BR>";
			for($r=0; $r<$i; $r++)
				echo "<b>".$titles[$r]."</b><BR>";
			echo "You must change or remove the author of the following publication(s) in order to delete this author.";
			echo "<BR><a href=\"./\">Back to Admin Page</a>";
			$confirm = "no";
			disconnect_db($link);
			exit();
		}
	/* This is where the actual deletion happens. */
	if (isset($confirm) && $confirm == "yes") {
		$query = "DELETE FROM author WHERE author_id = $author_id";
		query_db($query);
		$query = "DELETE FROM author_interest WHERE author_id = $author_id";
		query_db($query);
		$query = "DELETE FROM pub_author WHERE author_id = $author_id";
		query_db($query);
		echo "<body>You have successfully removed the following category from the database: <b>$authorname</b>";
		echo "<b><br><a href=\"../list_author.php?type=view&admin=true\">Back to Author Page</a>";
		echo "<br><a href=\"./\">Back to Administrator Page</a></b>";
		echo "<br><br></body></html>";
		disconnect_db($link);
		exit();
	}





?>

<body><h3>Delete Author</h3><br>
	<table width="750" border="0" cellspacing="0" cellpadding="6">
	<tr>
		<td width="100%" colspan="2"><font face="Arial, Helvetica, sans-serif" size="2"><b>Delete the following author?</b></font></td>
	  </tr>
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
	  	  <form name="deleter" action="./delete_author.php?author_id=<?php echo $author_id; ?>&confirm=yes" method="POST" enctype="application/x-www-form-urlencoded" target="_self">
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
	mysql_free_result($author_result);

	/* Closing connection */
	disconnect_db($link);
?>