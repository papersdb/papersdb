<html>
<head>
<title>Add Publication</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?php>
	require('lib_dbfunctions.php');
	if ($Save){
		echo "<script language='javascript'>setTimeout(\"top.location.href = './'\",5000)</script>";
	}
	echo "</head>";
	echo "<body>";
	include('header.php');
	$link = connect_db();

	if ($Save) {

		/* Format the keywords string */
		//$keywords = str_replace(" ", "", $keywords);
		while (!(strpos($keywords, ";;") === FALSE)) { 
			$keywords = str_replace(";;", ";", $keywords);
		}

		/* Current date */
		$currdate = date("Y-m-d");

		/* Publish date */
		$pubdate = $year . "-" . $month . "-" . $day;

		/* Performing SQL query */
		$pub_query = "UPDATE publication SET title = \"$title\", abstract = \"$abstract\", keywords = \"$keywords\",
			published = \"$pubdate\", updated = \"$currdate\" WHERE pub_id = $pub_id"; 
		$pub_result = query_db($pub_query);

		/*$pub_id_query = "SELECT pub_id from publication WHERE title=\"$title\"";
		$pub_id_result = query_db($pub_id_query);
		$pub_id_array = mysql_fetch_array($pub_id_result, MYSQL_ASSOC);
		$pub_id = $pub_id_array[pub_id];*/

	    echo "You have successfully made changes to the publication: $title";
	    echo "<br><br>You will be transported to the main page in 5 seconds";
	    //echo "<script language='javascript'>alert('Publication successfully modified.');document.write(location.href='./')</script> \n";
	    echo "</body></html>";
	    exit;
	}

	/* Format the keywords string */
	//$keywords = str_replace(" ", "", $keywords);
	while (!(strpos($keywords, ";;") === FALSE)) { 
		$keywords = str_replace(";;", ";", $keywords);
	}
	
	/* Current date */
	$currdate = date("Y-m-d");
	
	/* Publish date */
	$pubdate = $year . "-" . $month . "-" . $day;

	/* Performing SQL query */
	$pub_query = "INSERT INTO publication (pub_id, title, paper, abstract, keywords, published, updated) 
	              VALUES (NULL, \"$title\", \"\", \"$abstract\", \"$keywords\", \"$pubdate\", \"$currdate\")"; 
	$pub_result = query_db($pub_query);
	
	$pub_id_query = "SELECT pub_id from publication WHERE title=\"$title\"";
	$pub_id_result = query_db($pub_id_query);
	$pub_id_array = mysql_fetch_array($pub_id_result, MYSQL_ASSOC);
	$pub_id = $pub_id_array[pub_id];
	
	/* Make a directory for the current publication's binary files */
	$dir_path = $absolute_files_path . $pub_id . "/";
	
	if (!(file_exists($dir_path))) {
		mkdir($dir_path, 0777);
	}

	/* Save the binary files, and their paths (which will be saved in the DB) */
	$paper_path = $absolute_files_path . $pub_id . "/paper_" . $uploadpaper_name;
	$paper_link = "/" . $relative_files_path . $pub_id . "/paper_" . $uploadpaper_name;
			
	// Check if there is actually a file uploaded at uploadadditional[$i]
	if (is_uploaded_file($uploadpaper)) {
		copy($uploadpaper, $paper_path);
		chmod($paper_path, 0777);
	}
	
	$pub_update_query = "UPDATE publication SET paper=\"$paper_link\" WHERE pub_id=$pub_id";
	$pub_update_result = query_db($pub_update_query);
	
	$additional_unique_counter = 0;
	
	// Potentially zero or multiple additional materials
	for ($i = 0; $i < $numMaterials; $i++) {
		// Get the name of the file
		$temp = "";
		$temp = "uploadadditional" . $i;
		$temp_name = "";
		$temp_name = "uploadadditional" . $i . "_name";
		$temp_size = 0;
		$temp_size = "uploadadditional" . $i . "_size";
	
		if ($$temp_size > 0) {		// Make sure there's actually a file uploaded
			
			// Store the path
			$additional_path[$i] = $absolute_files_path . $pub_id . "/additional_" . $$temp_name;
			$additional_link[$i] = "/" . $relative_files_path . $pub_id . "/additional_" . $$temp_name;
			
			// Check if there is actually a file uploaded at uploadadditional[$i]
			if (is_uploaded_file($$temp)) {
				copy($$temp, $additional_path[$i]);
				chmod($additional_path[$i], 0777);
			}
			
			$add_query = "INSERT INTO additional_info (add_id, location) VALUES (NULL, \"$additional_link[$i]\")";
			$add_result = query_db($add_query);
			
			$add_id_query = "SELECT add_id from additional_info WHERE location=\"$additional_link[$i]\"";
			$add_id_result = query_db($add_id_query);
			$add_id_temp_array = mysql_fetch_array($add_id_result, MYSQL_ASSOC);
			$add_id_array[$additional_unique_counter] = $add_id_temp_array[add_id];
		
			$additional_unique_counter++;
		}
	}
	
	$temp = "";
	
	for ($i = 0; $i < $additional_unique_counter; $i++) {
		$temp .= " (" . $pub_id . "," . $add_id_array[$i] . "),";
	}
		
	$temp = substr_replace($temp, "", (strlen($temp) - 1), strlen($temp));
	
	// Adding publication/additional information relationship(s)
	if ($temp != "") {
		$pub_add_query = "INSERT INTO pub_add (pub_id, add_id) VALUES $temp";
		$pub_add_result = mysql_query($pub_add_query) or die("Query failed: " . mysql_error());
	}
	
	$cat_id_query = "SELECT cat_id from category WHERE category=\"$category\"";
	$cat_id_result = mysql_query($cat_id_query) or die("Query failed : " . mysql_error());
	$cat_id_array = mysql_fetch_array($cat_id_result, MYSQL_ASSOC);
	$cat_id = $cat_id_array[cat_id];
	
	// Adding publicaiton/category relationship
	$pub_cat_query = "INSERT INTO pub_cat (pub_id, cat_id) VALUES ($pub_id, $cat_id)";
	$pub_cat_result = mysql_query($pub_cat_query) or die("Query failed: " . mysql_error());
	
	$temp = "";
	
	for ($i = 0; $i < count($authors); $i++) {
		if ($authors[$i] != "") {
			$temp .= " (" . $pub_id . "," . $authors[$i] . "),";
		}
	}
		
	$temp = substr_replace($temp, "", (strlen($temp) - 1), strlen($temp));
	
	// Adding publication/author relationship(s)
	if ($temp != "") {
		$pub_author_query = "INSERT INTO pub_author (pub_id, author_id) VALUES $temp";
		$pub_author_result = mysql_query($pub_author_query) or die("Query failed: " . mysql_error());
	}
	
	$info_id_query = "SELECT info_id from cat_info WHERE cat_id=$cat_id";
	$info_id_result = mysql_query($info_id_query) or die("Query failed : " . mysql_error());

	while ($info_id_array = mysql_fetch_array($info_id_result, MYSQL_ASSOC)) {
		$info_id = $info_id_array[info_id];
		
		$info_name_query = "SELECT name from info WHERE info_id=$info_id";
		$info_name_result = mysql_query($info_name_query) or die("Query failed : " . mysql_error());
		$info_name_array = mysql_fetch_array($info_name_result, MYSQL_ASSOC);
		
		$info_name = strtolower($info_name_array[name]);
		$info_name = str_replace(" ", "", $info_name);
		$value = $$info_name;
		
		$pub_cat_info_query = "INSERT INTO pub_cat_info (pub_id, cat_id, info_id, value) VALUES ($pub_id, $cat_id, $info_id, \"$value\")";
		$pub_cat_info_result = mysql_query($pub_cat_info_query) or die("Query failed: " . mysql_error());
	}
	
    /* Free resultset */
    mysql_free_result($pub_id_result);
    mysql_free_result($cat_id_result);
    mysql_free_result($info_id_result);
    echo "You have successfully added the following publication: $title.<br>";
    echo "Thank you for your submission.";
    
    /* Closing connection */
    disconnect_db($link);
	
	echo "<script language='javascript'>alert('Publication successfully added.');document.write(location.href='/.)</script>";
?>

</body>
</html>
