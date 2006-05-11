<?
		if (isset($admin) && $admin == "true")
			include 'headeradmin.php';
		else
			include 'header.php';
?>
<?
	require('functions.php');
/* view_publication.php
    Given a publication id number this page shows most of the
	information about the publication. It does not display the
	extra information which is hidden and used only for the
	search function. It provides links to all the authors
	that are included. If a user is logged in, then there is
	an option to edit or delete the current publication.
*/
	isValid($pub_id);

	/* Connecting, selecting database */
	$link = connect_db();

	/* Performing SQL query */
	$pub_query = "SELECT * FROM publication WHERE pub_id=" . quote_smart($pub_id);
	$pub_result = query_db($pub_query);
	$pub_array = mysql_fetch_array($pub_result, MYSQL_ASSOC);

	$cat_query = "SELECT category.category FROM category, pub_cat WHERE category.cat_id=pub_cat.cat_id AND pub_cat.pub_id=" . quote_smart($pub_id);
	$cat_result = query_db($cat_query);
	$cat_array = mysql_fetch_array($cat_result, MYSQL_ASSOC);

	$add_query = "SELECT additional_info.location, additional_info.type FROM additional_info, pub_add WHERE additional_info.add_id=pub_add.add_id AND pub_add.pub_id=" . quote_smart($pub_id);
	$add_result = query_db($add_query);

	$author_query = "SELECT author.author_id, author.name FROM author, pub_author WHERE author.author_id=pub_author.author_id AND pub_author.pub_id=" . quote_smart($pub_id) . " ORDER BY pub_author.rank";
	$author_result = query_db($author_query);

	$info_query = "SELECT info.info_id, info.name FROM info, cat_info, pub_cat WHERE info.info_id=cat_info.info_id AND cat_info.cat_id=pub_cat.cat_id AND pub_cat.pub_id=" . quote_smart($pub_id);
	$info_result = query_db($info_query);

	$intpoint_query = "SELECT value FROM pointer WHERE pub_id=" . quote_smart($pub_id) . " AND type=\"int\"";
	$intpoint_result = query_db($intpoint_query);

	$extpoint_query = "SELECT name, value FROM pointer WHERE pub_id=" . quote_smart($pub_id) . " AND type=\"ext\"";
	$extpoint_result = query_db($extpoint_query);

	if($pub_array['paper'] == "No paper")
		$paperstring = "No Paper at this time.";
	else {
		$paperstring = "<a href=\".".$pub_array['paper'];
		$papername = split("paper_", $pub_array['paper']);
		$paperstring .= "\"> Paper:<i><b>$papername[1]</b></i></a>";
	}
?>

<html>
<head>
<title><? echo $pub_array['title'] ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>



<body>
	<table width="750" border="0" cellspacing="0" cellpadding="6">
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Title: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><b><? echo $pub_array['title'] ?></b></font></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Category: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $cat_array['category'] ?></font></td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2" color="#000000"><b>Paper: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $paperstring; ?></font></td>
	  </tr>
	 <?
	 	$add_checker = mysql_fetch_array($add_result, MYSQL_ASSOC);
		if($add_checker['location'] != null){
		$add_query = "SELECT additional_info.location, additional_info.type FROM additional_info, pub_add WHERE additional_info.add_id=pub_add.add_id AND pub_add.pub_id=$pub_id";
		$add_result = query_db($add_query);
		echo "<tr>";
		echo "<td width=\"25%\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\" color=\"#000000\"><b>Additional Materials: </b></font></td>";
		echo "<td width=\"75%\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";

				$add_count = 0;
				$temp = "";
				while ($add_line = mysql_fetch_array($add_result, MYSQL_ASSOC)) {
					$temp = split("additional_", $add_line[location]);
					echo "<a href=./" . $add_line['location'] . ">";
					if($add_line['type'] != "")
						echo $add_line['type'].":<i><b>".$temp[1]."</b></i>";
					else
						echo "Additional Material " . ($add_count + 1).":<i><b>".$temp[1]."</b></i>";

					echo "</a><br>";
					$add_count++;
				}

			echo "</font>";
		echo "</td>";
	  echo "</tr>";
	  }
	  ?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Authors: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2">
			<?
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
					echo "<a href=\"./view_author.php?"; if(isset($admin) && $admin == "true") echo "admin=true&"; echo "popup=true&author_id=$author_line[author_id]\" target=\"_self\"  'Help', 'width=500,height=250,scrollbars=yes,resizable=yes'); return false\">";
					echo $author_line['name'];
					echo "</a><br>";
				}
			?>
			</font>
		</td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Abstract: </b></font></td>
		<td width="75%"><? echo stripslashes($pub_array['abstract']) ?></td>
	  </tr>
	  <? if($pub_array[venue] != "") {
	  		$temp_array = split("venue_id:<", $pub_array[venue]);
				 if($temp_array[1] != ""){
				 	$temp_array = split(">", $temp_array[1]);
				 	$venue_id = $temp_array[0];
					$venue_query = "SELECT * FROM venue WHERE venue_id=$venue_id";
    				$venue_result = mysql_query($venue_query) or die("Query failed : " . mysql_error());
					$venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC);
					$venue_name = $venue_line['name'];
					$venue_url = $venue_line['url'];
					$venue_type = $venue_line['type'];
					$venue_data = $venue_line['data'];
					if($venue_type != ""){
					echo "<tr><td width=\"25%\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
					echo "<b>".$venue_type.":&nbsp;</b></font></td><td width=\"75%\">";
					if($venue_url != "")
						echo " <a href=\"".$venue_url."\" target=\"_blank\">";
					echo $venue_name;
					if($venue_url != "")
						echo "</a>";
					if($venue_data != ""){
						echo "</td></tr><tr><td width=\"25%\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
						if($venue_type == "Conference")
							echo "<b>Location:&nbsp;</b>";
						else if($venue_type == "Journal")
							echo "<b>Publisher:&nbsp;</b>";
						else if($venue_type == "Workshop")
							echo "<b>Associated Conference:&nbsp;</b>";
						echo "</td><td width=\"75%\">".$venue_data;
					}
					echo "</td></tr>";
					}
			}
			else{

	   ?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Publication Venue: </b></font></td>
		<td width="75%"><? echo stripslashes($pub_array['venue']) ?></td>
	  </tr>
	  <? }} ?>
	  <? while ($ext_line = mysql_fetch_array($extpoint_result, MYSQL_ASSOC)) { ?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b><? echo $ext_line[name] ?>: </b></font></td>
		<td width="75%"><? echo $ext_line['value'] ?></td>
	  </tr>
	  <? } ?>
	  <? while ($int_line = mysql_fetch_array($intpoint_result, MYSQL_ASSOC)) {
	     $pubpoint_query = "SELECT title FROM publication WHERE pub_id=".$int_line[value];
		 $pubpoint_result = query_db($pubpoint_query);
		 $pubpoint_line = mysql_fetch_array($pubpoint_result, MYSQL_ASSOC);
	?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Connected with: </b></font></td>
		<td width="75%"><? echo "<a href=\"view_publication.php?";
							if(isset($admin) && $admin == "true") echo "admin=true&";
							echo "pub_id=".$int_line['value']."\">".$pubpoint_line[title]."</a>";
						?>
		</td>
	  </tr>
	  <? } ?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Keywords: </b></font></td>
		<?
			$display_array = explode(";", $pub_array[keywords]);
		?>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2">
		<?
			$tempcount = count($display_array);
			for ($i = 0; $i < $tempcount; $i++)
				{
					if (($display_array[$i] != "")&& ($display_array[$i] != null))
					{ 	echo $display_array[$i];
						if($i < $tempcount-2)
							{echo ", ";}
					}
				}
		?>
		</font>
		</td>
	  </tr>
<?	while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)) {
		$info_id = $info_line['info_id'];
		$value_query = "SELECT pub_cat_info.value FROM pub_cat_info, pub_cat WHERE pub_cat.pub_id=$pub_id AND pub_cat.cat_id=pub_cat_info.cat_id AND pub_cat_info.pub_id=$pub_id AND pub_cat_info.info_id=$info_id";
		$value_result = mysql_query($value_query) or die("Query failed : " . mysql_error());
		$value_line = mysql_fetch_array($value_result, MYSQL_ASSOC);
		if($value_line['value'] != null){
		echo "<tr>";
			echo "<td width=\"25%\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><b>" . $info_line['name'] . ": </b></font></td>";
			echo "<td width=\"75%\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">" . $value_line['value'] . "</font></td>";
		echo "</tr>";
		}
    	mysql_free_result($value_result);
	}


//PARSE DATES
$string = "";
$published = split("-",$pub_array[published]);
if($published[1] != 00)
	$string .= date("F", mktime (0,0,0,$published[1]))." ";
if($published[2] != 00)
	$string .= $published[2].", ";
if($published[0] != 0000)
	$string .= $published[0];

if($string != ""){
?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Date Published: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $string ?></font></td>
	  </tr>
<? }
$string = "";
$published = split("-",$pub_array[updated]);
if($published[1] != 00)
	$string .= date("F", mktime (0,0,0,$published[1]))." ";
if($published[2] != 00)
	$string .= $published[2].", ";
if($published[0] != 0000)
	$string .= $published[0];
?>
	<tr><td></td><td align=right>
	<? if($string != "") { ?>
	<font face="Arial, Helvetica, sans-serif" size="1"><? echo "Last updated ". $string; ?></font><BR>
	<? } ?>
	<font face="Arial, Helvetica, sans-serif" size="1"><? echo "Submitted by " . $pub_array['submit'] . "<BR>"; ?></font>
	</td></tr>
	</table>
	<?
	if(isset($admin) && $admin == "true"){
		echo "<BR><b><a href=\"Admin/add_publication.php?pub_id=" . quote_smart($pub_id) . "\">Edit this publication</a>&nbsp;&nbsp;&nbsp;";
		echo "<a href=\"Admin/delete_publication.php?pub_id=" . quote_smart($pub_id) . "\">Delete this publication</a></b><br><BR>";
	}
	back_button(); ?>
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
