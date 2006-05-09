<?
	include 'header.php';
?>

<html>
<head>
<title>Quick Edit</title>
<style type="text/css">
<!--
a:link {
	color: #000099;
}
a:visited {
	color: #000099;
}
a:hover {
	color: #0066FF;
}
-->
</style>




<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<META NAME= "ROBOTS" CONTENT="NOINDEX">
</head>

<?	/* quickedit.php
	
		This page was made to make changes to many publications at once.
		It still needs work if it is to be used practically. But mainly was
		only made so that users could easily add information to the fields,
		that I recently added, very easily.


	*/
	require('../functions.php');

	/* Connecting, selecting database */
	$link = connect_db();

	function output($query, $choice){
		$itran = false;
		$pub_result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($pub_line = mysql_fetch_array($pub_result, MYSQL_ASSOC)) {
			echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"3\"><li><a href=\"../view_publication.php?pub_id=" . $pub_line[pub_id] . "\"target=\"_blank\" onClick=\"window.open('../view_publication.php?pub_id=$pub_line[pub_id]', 'Help', 'scrollbars=yes,resizable=yes'); return false\">" . $pub_line[title] . "</a></font>";
			$itran = true;
			$author_query = "SELECT author.author_id, author.name FROM author, pub_author WHERE author.author_id=pub_author.author_id AND pub_author.pub_id=".$pub_line[pub_id];
			$author_result = query_db($author_query);
			while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
				echo "<a href=\"./view_author.php?popup=true&author_id=$author_line[author_id]\" target=\"_blank\" onClick=\"window.open('../view_author.php?popup=true&author_id=$author_line[author_id]', 'Help', 'scrollbars=yes,resizable=yes'); return false\">";
				$author = split(",",$author_line[name]);
				echo "<font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".$author[1]." ".$author[0]."</font>";
				echo "</a> ";
			}
			echo "<br><textarea name=\"thearray[".$pub_line[pub_id]."]\" cols=\"70\" rows=\"5\">$pub_line[$choice]</textarea></td></tr>";
		}
	return $itran;
	}


?>

<body>
<h2><b><u>Quick Editor</u></b></h2>
<? 
///////////////////////////////////////////////////////////
if($selection == "done"){
for($b=0; $b < $arraycount; $b++)
	{
	echo "<h3> Completed </h3> ";
	echo "<table>";
	$confirm_query = "SELECT $choice FROM publication WHERE pub_id=".$idarray[$b];
	$confirm_result = mysql_query($confirm_query) or die("Query failed : " . mysql_error());
	$confirm_line = mysql_fetch_array($confirm_result, MYSQL_ASSOC);
	$pub_query = "UPDATE publication SET $choice = \"".$contentarray[$b]."\" WHERE pub_id = ".$idarray[$b]; 
	
	if($confirm_line[$choice] != $contentarray[$b]){
		$pub_result = query_db($pub_query);
		echo "<TR><TD><b>\"".$confirm_line[$choice]."\"</b></td><td> to </td><td><b>\"".$contentarray[$b]."\"</b></td></tr>"; 
	}
	}
	echo "</TABLE>";
	echo "<BR> <a href=\"quickedit.php\"> Edit another subject </a>";
	echo "<BR> <a href=\"index.php\"> Back to Menu </a>";
}
/////////////////////////////////////////////////////
if($selection == "confirm"){
echo "<h4>Are you sure you want to make the following changes?</h4><BR>";
echo "<form name=\"quickedit\" action=\"quickedit.php?selection=done\" method=\"POST\" enctype=\"multipart/form-data\">";
$count = 0;
for($i = 0; $i < 1000; $i++)
	if($thearray[$i] != ""){
		$idarray[$count] = $i;
		$contentarray[$count++] = str_replace("\\","",$thearray[$i]);
	}
	echo "<TABLE BORDER=3 CELLPADDING=2>";
	$arraycount = 0;
	
for($b=0; $b < $count; $b++)
	{
	$confirm_query = "SELECT $choice FROM publication WHERE pub_id=".$idarray[$b];
	$confirm_result = mysql_query($confirm_query) or die("Query failed : " . mysql_error());
	$confirm_line = mysql_fetch_array($confirm_result, MYSQL_ASSOC);
	if($confirm_line[$choice] != $contentarray[$b]){
	
		echo "<TR><TD><b>\"".$confirm_line[$choice]."\"</b></td><td> to </td><td><b>\"".$contentarray[$b]."\"</b></td></tr>"; 
		echo "<input type=\"HIDDEN\" name=\"contentarray[$arraycount]\" value=\"$contentarray[$b]\">";
		echo "<input type=\"HIDDEN\" name=\"idarray[$arraycount]\" value=\"$idarray[$b]\">";
		$arraycount++;
			}
		echo "<input type=\"HIDDEN\" name=\"arraycount\" value=\"$arraycount\">";
		echo "<input type=\"hidden\" name=\"choice\" value=\"$choice\">";
	}
	echo "</TABLE>";

echo "<input type=\"SUBMIT\" name=\"submit\" value=\"Yes\" class=\"text\">";
echo "<input type=\"BUTTON\" name=\"cancel\" value=\"Cancel\" class=\"text\" onclick=\"location.href='index.php'\" >";


}
//////////////////////////////////////////////////////////////////////////
	if($selection == "") { ?>
<form name="quickedit" action="quickedit.php?selection=true" method="POST" enctype="multipart/form-data">
	Edit:<select name="choice">
			<option value="title">Title</option>
			<option value="abstract">Abstract</option>
			<option value="venue">Venue</option>
			<option value="extra_info">Extra Information</option>
			<option value="keywords">Keywords</option>
		</select>  
		<input type="SUBMIT" name="submit" value="Display" class="text">
   </form>
<? } ?>
 <? if($selection == "true"){?>
	<table width="750" border="0" cellspacing="0" cellpadding="6">
			<form name="editor" action="quickedit.php?selection=confirm" method="POST" enctype="multipart/form-data">

			
		<?  
			$pub_array = NULL;
			$itran = false;
			if($title){$choice = "title";}
			else if($abtract){$choice = "abstract";}
			else if($venue){$choice = "venue";}
			else if($extra_info){$choice = "extra_info";}
			else if($keywords){$choice = "keywords";}
			
			if($pubid != ""){
				$pub_array = split("," , $pubid);
				for($b = 0; $b < count($pub_array); $b++){
					$pub_query = "SELECT * FROM publication WHERE pub_id=".$pub_array[$b];
					output($pub_query, $choice);
					$itran = true;
				}
			}
			else{
				$pub_query = "SELECT * FROM publication ORDER BY title ASC";
				$itran = output($pub_query, $choice);
			}
			
			   if(!$itran)echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li>No publications.</font></td></tr>"; 	
			echo "<input type=\"SUBMIT\" name=\"submit\" value=\"Submit\" class=\"text\">";
			
	   	?>
		<br>
		<input type="hidden" name="choice" value="<? echo $choice; ?>">
		</table>
	<? 
	echo "<input type=\"SUBMIT\" name=\"submit\" value=\"Submit\" class=\"text\"></form>"; }
	back_button(); ?>
	


</body>
</html>

<?
    disconnect_db($link);
?>
