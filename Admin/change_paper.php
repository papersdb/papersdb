<html>
<head>
<title>Change Paper</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<?
	/* change_paper.php
		This page is necessary to change a paper
		that is has been already uploaded and is
		linked to an existing publication. It is 
		a pop-up that appears when  the "change paper"
		link is clicked on add_publication.php. It
		takes in the new file, and makes the necessary
		changes.
	
	*/
	
	require('../functions.php');

	if ($changePaper == "true") {
		/* Connecting, selecting database */
		$link = connect_db();
		
		$paper_query = "SELECT paper FROM publication WHERE pub_id=$pub_id";
		$paper_result = mysql_query($paper_query) or die("Query failed : " . mysql_error());
		$paper_array = mysql_fetch_array($paper_result, MYSQL_ASSOC);
		
		if($nopaper == "true"){
			$pub_update_query = "UPDATE publication SET paper=\"No paper\" WHERE pub_id=$pub_id";
			$pub_update_result = query_db($pub_update_query);
			if($paper_array[paper] != "No paper"){
				$file_name = $paper_array[paper];
				$file_name = $absolute_path . $file_name;
				unlink($file_name);
			}
		}
		else {
			if($paper_array[paper] == "No paper"){
				$dir_path = $absolute_files_path . $pub_id . "/";
				if (!(file_exists($dir_path))) {
					mkdir($dir_path, 0777);
				}
			}
			else {
				$file_name = $paper_array[paper];
				$file_name = $absolute_path . $file_name;
				unlink($file_name);
			}
			/* Save the binary files, and their paths (which will be saved in the DB) */
			$paper_path = $absolute_files_path . $pub_id . "/paper_" . $uploadpaper_name;
			$paper_link ="/". $relative_files_path . $pub_id . "/paper_" . $uploadpaper_name;
					
			// Check if there is actually a file uploaded at uploadadditional[$i]
			if (is_uploaded_file($uploadpaper)) {
				copy($uploadpaper, $paper_path);
			}
			
			$pub_update_query = "UPDATE publication SET paper=\"$paper_link\" WHERE pub_id=$pub_id";
			$pub_update_result = mysql_query($pub_update_query) or die("Query failed : " . mysql_error());
		}
		echo "<script language='javascript'>window.opener.location.reload(true);</script>";
		echo "<script language='javascript'>document.write(location.href='./add_publication.php?" . $_SERVER['QUERY_STRING'] . "'); close();</script>";
	}
?>
<script language="JavaScript" type="text/JavaScript">
function closewindow()  {window.close();}
//function verify() {window.refresh(); closewindow();}
</script>


<body>
<h3>Change Paper</h3>
<form name="changePaperForm" action="change_paper.php<? echo "?" . $_SERVER['QUERY_STRING'] ?>" method="POST" target="_parent" enctype="multipart/form-data">
	<table width="475" border="0" cellspacing="0" cellpadding="6">
	  <tr>
	  	<td>
			<input type="radio" name="nopaper" value="false" checked>
		</td>
		<td>
			<font face="Arial, Helvetica, sans-serif" size="2" color="#990000"><b>Paper: </b></font></td>
		<td>
			<input type="file" name="uploadpaper" size="60" maxlength="250"><br>
     	</td>
	  </tr>
	  <tr>
	  	<td colspan="3">
			<input type="radio" name="nopaper" value="true"> No paper at this time.
		</td>
	  </tr>
	  <tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	  </tr>
	  <tr>
		<td width="25%">&nbsp;</td>
		<td width="75%" align="left" colspan="2">
			<input type="SUBMIT" name="Submit" value="Change Publication" class="text" onClick="return verify();">&nbsp;&nbsp;
			<input type="RESET" name="Cancel" value="Cancel" class="text" onClick="closewindow();"></td>
			<input type="hidden" name="changePaper" value="true">
			<input type="hidden" name="pub_id" value="<? echo $pub_id ?>">
	  </tr>
	</table>
</form>
</body>
</html>
