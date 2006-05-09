<html>
<head>
<title>Help Fields</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<?
	/* help.php
		This is a dynamic help page. It
		takes in input for which field the user
		needs help on, then retrieves that information
		from the database and displays it.
	*/
	
	$title = str_replace("_"," ",$q);
	$array = split(" ", $title);
	$title = "";
	for($a = 0; $a < count($array); $a++)
		$title .= ucfirst($array[$a])." ";
	echo "<h3><b>Help : $title</b></h3>";
		require('../functions.php');
		$link = connect_db();
		$help_query = "SELECT * FROM help_fields WHERE name= \"".$q."\"";
		$help_result = mysql_query($help_query) or die("Query failed : " . mysql_error());
		$help_line = mysql_fetch_array($help_result, MYSQL_ASSOC);
		echo $help_line[content];
			
		
?>
</body>
</html>

