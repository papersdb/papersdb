<?
	include 'header.php';
?>
<html>
<head>
<title>Login Information</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<? /*	login.php
		This page displays/edits the users information.
		This includes fields like name, email and favorite
		collaborators. These authors will be people the user will 
		most likely be using a lot.

	*/
?>
<SCRIPT LANGUAGE="JavaScript" SRC="OptionTransfermax.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript">
	var opt = new OptionTransfer("authors[]","authorslist[]");
	opt.setAutoSort(true);
	opt.saveRemovedLeftOptions("removedLeft");
	opt.saveAddedLeftOptions("addedLeft");
	opt.saveNewLeftOptions("selected_authors");
</SCRIPT>
<?		
	require('../functions.php');
	$link = connect_db();
	$user_query = "SELECT * FROM user WHERE login LIKE " . quote_smart($_SERVER['PHP_AUTH_USER']);
	$user_result = query_db($user_query);
	$user_array = mysql_fetch_array($user_result, MYSQL_ASSOC);
	if($user_array['login'] == "" && $status != "add")
		$new = true;
	
if(($status == "edit")||($new))
	echo "<body  onLoad=\"opt.init(document.forms[0])\">";
else
	echo "<body>";



	/* Connecting, selecting database */

if(($status == "change")||($status == "add"))
	{
		if($status == "add"){
			$query = "INSERT INTO user (login, name, email, comments) VALUES (" . quote_smart($login) . ", " . quote_smart($name) . ", " . quote_smart($email) . ", NULL)";
			query_db($query);
		}
		
		if($status == "change"){
			$query = "UPDATE user SET name=" . quote_smart($name) . ", email=" . quote_smart($email) . " WHERE login=" . quote_smart($login);
			query_db($query);
		}
		
		//we pulled old data from the DB, so we need to set the columns to the new data
		$user_array['login'] = $login;
		$user_array['name'] = $name;
		$user_array['email'] = $email;
		
		$authors = split(",",$selected_authors);
			$temp = "";
			for ($i = 0; $i < count($authors); $i++) {
				if ($authors[$i] != "") {
					$temp .= " (" . quote_smart($login) . "," . quote_smart($authors[$i]) . "),";
				}
			}
		
		$query = "DELETE FROM user_author WHERE login=" . quote_smart($login);
		$result = query_db($query); 
		if ($temp != "") {
			$temp = substr_replace($temp, "", (strlen($temp) - 1), strlen($temp));
			$pub_author_query = "INSERT INTO user_author (login, author_id) VALUES $temp";
			$pub_author_result = mysql_query($pub_author_query) or die("Query failed: " . mysql_error());
		}
	
	}
	/* Performing SQL query */

	$user_query = "SELECT author.author_id FROM user_author, author".
	       " WHERE user_author.login LIKE " . quote_smart($_SERVER['PHP_AUTH_USER']) .
		   " AND author.author_id = user_author.author_id ORDER BY author.name";
	$user_result = query_db($user_query);
	$count = 0;
	while($user_author_array = mysql_fetch_array($user_result, MYSQL_ASSOC))
	{
		$author_ids[$count++] = $user_author_array['author_id'];
	}
	
	if($count == 0){

		}
?>

<h2><b><u>Login Information</u></b></h2>
<? 
if($status == "edit")
	echo "<form name=\"authorForm\" action=\"login.php?status=change\" method=\"POST\" enctype=\"application/x-www-form-urlencoded\">";
else if($new)
	echo "<form name=\"authorForm\" action=\"login.php?status=add\" method=\"POST\" enctype=\"application/x-www-form-urlencoded\">";
echo "<table cellpadding=\"6\">";
$edit = false;
if(($new)||($status == "edit")) {
	echo "<tr><td><b>Login: </b></td><td>".$_SERVER['PHP_AUTH_USER']."</td></tr>";
	echo "<input type=\"hidden\" name=\"login\" value=\"".$_SERVER['PHP_AUTH_USER']."\" >";
	echo "<tr><td><b>Name: </b></td><td><input type=\"text\" name=\"name\" size=\"50\" maxlength=\"100\" value=\"".$user_array['name']."\"></td></tr>";
	echo "<tr><td><b>E-mail: </b></td><td><input type=\"text\" name=\"email\" size=\"50\" maxlength=\"100\" value=\"".$user_array['email']."\"></td></tr>";
}
else {
	echo "<tr><td><b>Login: </b></td><td>".$user_array['login']."</td></tr>";
	echo "<tr><td><b>Name: </b></td><td>".$user_array['name']."</td></tr>";
	echo "<tr><td><b>E-mail: </b></td><td>".$user_array['email']."</td></tr>";
	
	for ($i = 0; $i < $count; $i++) {
		$query = "SELECT DISTINCT name FROM author WHERE author_id=" . quote_smart($author_ids[$i]);
		$result = query_db($query);
		$array = mysql_fetch_array($result, MYSQL_ASSOC);
		
		if($i == 0)
			echo "<tr><td><b>Favorite Collaborators: </b></td><td>".$array['name']."</td></tr>";
		else echo "<tr><td></td><td>".$array['name']."</td></tr>";
	}
}
	
if(($new)||($status == "edit")){
 ?>
<!-- Authors  -->
	  <tr>
		<td width="25%"><b>Favorite Collaborators: </b><BR> <font face="Arial, Helvetica, sans-serif" size="1" color="red">10 Maximum</font></td>
		<td width="75%">
		<TABLE>
		<tr>
		<td>
		<SELECT NAME="authors[]" MULTIPLE SIZE=10 onDblClick="opt.transferRight()">
		<? 			
				if($count != 0)
					for($i = 0; $i < $count; $i++){
						$query = "SELECT * FROM author WHERE author_id = " . quote_smart($author_ids[$i]);
						$result = query_db($query);
						$line = mysql_fetch_array($result, MYSQL_ASSOC);
						$name = $line['name'];
						echo "<option value=\"" . $author_ids[$i] . "\"" . "";
						echo ">" . $name . "</option>";
					}
		?>
		
		</SELECT>
		</td>
		<td>
			<INPUT TYPE="button" NAME="right" VALUE="&gt;&gt;" ONCLICK="opt.transferRight()"><BR><BR>
			<INPUT TYPE="button" NAME="left" VALUE="&lt;&lt;" ONCLICK="opt.transferLeft()"><BR><BR>
		</td>
		<td>
		<SELECT NAME="authorslist[]" MULTIPLE SIZE=10 onDblClick="opt.transferLeft()">
		<?
				$counter = 0;
				$author_query = "SELECT * FROM author ORDER BY name";
				$author_result = query_db($author_query);
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
					$found = false;
					for ($i = 0; $i < $count; $i++)
						if($author_ids[$i] == $author_line[author_id])
							$found = true;
					if(!$found)
						echo "<option value=\"" . $author_line[author_id] . "\"" . ">" . $author_line[name] . "</option>";
					
					$counter++;
				}
		?>
		</SELECT>
		<INPUT TYPE="HIDDEN" NAME="selected_authors" VALUE="">
			</td>
		</tr>
		</TABLE>
		</td>
	  </TR>
<? 
}
if(($new)||($status == "edit"))
	echo "<tr><td><input type=\"SUBMIT\" name=\"Submit\" value=\"Save\" class=\"text\"></td></tr>";
if(!(($new)||($status == "edit")))
	echo "<tr><td colspan=2><a href=\"login.php?status=edit\">Edit this information</a></td></tr>";
echo "<tr><td colspan=2>For general inquiries or to change your login or password:</td></tr>";
echo "<tr><td colspan=2>Please e-mail <a href=\"mailto:paulsen@cs.ualberta.ca\">Jason Paulsen</a> with your current information.</td></tr>";

echo "</table>";
if(($new)||($status == "edit"))
	echo "</form>";
	
	back_button();
?>
</body>
</html>

<?     disconnect_db($link); ?>

