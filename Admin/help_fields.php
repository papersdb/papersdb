<html>
<head>
<title>Help Fields</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<?
	include("header.php");
	echo "<h2><u><b>Help Fields</b></u></h2>";
		require('../functions.php');
		$link = connect_db();
		$help_query = "SELECT * FROM help_fields";
		$help_result = mysql_query($help_query) or die("Query failed : " . mysql_error());
		if($submit == "true")
		{
			while($help_line = mysql_fetch_array($help_result, MYSQL_ASSOC)){
				$help_change = "UPDATE help_fields SET content = \"".$$help_line[name]."\" WHERE name = \"".$help_line[name]."\""; 
				query_db($help_change);
			}  
			echo "Submission successful.";
			exit;
		}
		else{
?>
<form name="helpForm" action="help_fields.php?submit=true" method="POST" enctype="application/x-www-form-urlencoded">
<table>
	<? while ($help_line = mysql_fetch_array($help_result, MYSQL_ASSOC)) { ?>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b><? echo $help_line[name]; ?>: </b></font></td>
		<td colspan="2" width="75%"><textarea name="<? echo $help_line[name]; ?>" cols="70" rows="10"><? echo $help_line[content] ?></textarea></td>
	 </tr>
	 
	 <? } ?>
	  <tr>
		<td colspan="2" width="75%" align="left">
			<input type="SUBMIT" name="Submit" value="Submit" class="text" onClick="return verify();">&nbsp;&nbsp;
		</td>
	  </tr>
	  
	</table>
</form>
<? } ?>
</body>
</html>

