<html>
<head>
<title>Change Paper</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	require('lib_dbfunctions.php');

	if ($changePaper == "true") {
		/* Connecting, selecting database */
		$link = connect_db();
		
		$paper_query = "SELECT paper FROM publication WHERE pub_id=$pub_id";
		$paper_result = mysql_query($paper_query) or die("Query failed : " . mysql_error());
		$paper_array = mysql_fetch_array($paper_result, MYSQL_ASSOC);
		
		$file_name = $paper_array[paper];
		//$file_name = substr($file_name, (strrpos($file_name, "/") + 1));
		$file_name = "/usr/abee/cshome/loh/web_docs" . $file_name;
		unlink($file_name);
	
		/* Save the binary files, and their paths (which will be saved in the DB) */
		$paper_path = "/usr/abee/cshome/loh/web_docs/uploaded_files/" . $pub_id . "/paper_" . $uploadpaper_name;
		$paper_link = "/uploaded_files/" . $pub_id . "/paper_" . $uploadpaper_name;
				
		// Check if there is actually a file uploaded at uploadadditional[$i]
		if (is_uploaded_file($uploadpaper)) {
			copy($uploadpaper, $paper_path);
		}
		
		$pub_update_query = "UPDATE publication SET paper=\"$paper_link\" WHERE pub_id=$pub_id";
		$pub_update_result = mysql_query($pub_update_query) or die("Query failed : " . mysql_error());
		
		echo "<script language='javascript'>document.write(location.href='http://web.cs.ualberta.ca/~loh/add_publication.php?" . $_SERVER['QUERY_STRING'] . "')</script>";
	}
?>

<body>
<form name="changePaperForm" action="change_paper.php<? echo "?" . $_SERVER['QUERY_STRING'] ?>" method="POST" target="_parent" enctype="multipart/form-data">
	<table width="450" border="0" cellspacing="0" cellpadding="6">
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2" color="#990000"><b>Paper: </b></font></td>
		<td width="75%"><input type="file" name="uploadpaper" size="60" maxlength="250"></td>
	  </tr>
	  <tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	  </tr>
	  <tr>
		<td width="25%">&nbsp;</td>
		<td width="75%" align="left">
			<input type="SUBMIT" name="Submit" value="Change Publication" class="text" onClick="return verify();">&nbsp;&nbsp;<input type="RESET" name="Cancel" value="Cancel" class="text" onClick="javascript:close();"></td>
			<input type="hidden" name="changePaper" value="true">
			<input type="hidden" name="pub_id" value="<? echo $pub_id ?>">
	  </tr>
	</table>
</form>
</body>
</html>
