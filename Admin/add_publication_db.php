<html>
<head>
<title>Add Publication</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?php
	/* add_publication_db.php
		This takes add_publication.php as the input, and
		adds the publication to the database. It takes the
		file(s) that need to be uploaded, and copies them to
		a designated directory, inside a folder where the
		folders name is the publication id.

		It displays a confirmation that the publication has been
		added, and then provides links to where the user would
		like to go next.
	*/

	require('../functions.php');

	function singlequery($query){$result= mysql_query($query) or die("Query failed: " . mysql_error());}

	echo "</head>";
	echo "<body>";
	include('header.php');
	$link = connect_db();

	/* Format the keywords string */
		//Changes Commas to semi-colons
		while ((strpos($keywords, ",")) != false) {
			$keywords = str_replace(",", ";", $keywords);
		}//If there is two semicolons beside eachother, removes one to prevent blank keywords
		while ((strpos($keywords, ";;")) != false) {
			$keywords = str_replace(";;", ";", $keywords);
		}//adds semicolon to the end if there isnt one.
		if((substr($keywords, strlen($keywords)-1, 1)) != ";"){
			$keywords = $keywords.";";}

		/* Current date */
		$currdate = date("Y-m-d");

		/* Publish date */
		if($year == "--")
			$year = "0000";
		if($month == "--")
			$month = "00";
		if($day == "--")
			$day = "00";
		$pubdate = $year . "-" . $month . "-" . $day;

		for($w = 0; $w < $numMaterials; $w++)
			for($y = 0; $y < $numMaterials; $y++)
				if($w != $y){
				$temp1 = "uploadadditional" . $w . "_name";
				$temp2 = "uploadadditional" . $y . "_name";
				if($$temp1 == $$temp2){
					echo "<h3>";
				    if($Save) echo "Update"; else echo "Addition";
					echo " Failed</h3>";
					echo "You have more then one additional materials with the same filename \"<b>".$$temp1."</b>\".<br><br>";
					back_button();
					exit;
					}

				}



	if ($Save) {
		$title = str_replace("\"","'",$title);
		$abstract = str_replace("\"","'",$abstract);
		$venue = str_replace("\"","'",$venue);
		$extra_info = str_replace("\"","'",$extra_info);
		$keywords = str_replace("\"","'",$keywords);
		/* Performing SQL query */
		$pub_query = "UPDATE publication SET title = \"$title\", abstract = \"$abstract\", ";
		 if($venue_id == -1) $pub_query .= "venue = \"\", ";
		 else if(($venue_id != "")&&($venue_id != -2)) $pub_query .= "venue = \"venue_id:<$venue_id>\", ";
		 else $pub_query .= "venue = \"$venue\", ";
		 $pub_query .= "extra_info = \"$extra_info\", keywords = \"$keywords\",
			published = \"$pubdate\", updated = \"$currdate\" WHERE pub_id = $pub_id";
		$pub_result = query_db($pub_query);

		// Pointers
		singlequery("DELETE FROM pointer WHERE pub_id=$pub_id");
		// External
		for($a = 0; $a < $ext; $a++){
		    $tempname = "extname".$a;
			$tempvalue = "extvalue".$a;
			singlequery("INSERT INTO pointer (pub_id, type, name, value) VALUES ($pub_id, \"ext\", \"".$$tempname."\", \"".$$tempvalue."\")");
		}
		// Internal
		for($a = 0; $a < $intpoint; $a++){
			$tempvalue = "intpointer".$a;
		    singlequery("INSERT INTO pointer (pub_id, type, name, value) VALUES ($pub_id, \"int\", \"-\", \"".$$tempvalue."\")");
		}

		// Check if there is actually a file uploaded at uploadadditional[$i]
		if (is_uploaded_file($uploadpaper)) {
				copy($uploadpaper, $paper_path);
				chmod($paper_path, 0777);
		}

		//$pub_update_query = "UPDATE publication SET paper=\"$paper_link\" WHERE pub_id=$pub_id";
		//$pub_update_result = query_db($pub_update_query);

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

			$temp_type = "type".$i;
			if($$temp_type == "")
				$$temp_type = "Additional Material ".$i;

			if ($$temp_size > 0) {		// Make sure there's actually a file uploaded

				// Store the path
				$additional_path[$i] = $absolute_files_path . $pub_id . "/additional_" . $$temp_name;
				$additional_link[$i] = "/" . $relative_files_path . $pub_id . "/additional_" . $$temp_name;

				// Check if there is actually a file uploaded at uploadadditional[$i]
				if (is_uploaded_file($$temp)) {
					copy($$temp, $additional_path[$i]);
					chmod($additional_path[$i], 0777);
				}

				$add_query = "INSERT INTO additional_info (add_id, location, type) VALUES (NULL, \"$additional_link[$i]\", \"".$$temp_type."\")";
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
		// Adding publication/author relationship(s)
			$authors = split(",",$selected_authors);
			$temp = "";
			for ($i = 0; $i < count($authors); $i++) {
				if ($authors[$i] != "") {
					$temp .= " (" . $pub_id . "," . $authors[$i] . "," . $i . "),";
				}
			}
		if ($temp != "") {
			$pub_query = "SELECT * FROM publication WHERE pub_id=$pub_id";
			$pub_result = query_db($pub_query);

			$query = "DELETE FROM pub_author WHERE pub_id = $pub_id";
			$result = query_db($query);

			$temp = substr_replace($temp, "", (strlen($temp) - 1), strlen($temp));
			$pub_author_query = "INSERT INTO pub_author (pub_id, author_id, rank) VALUES $temp";
			$pub_author_result = mysql_query($pub_author_query) or die("Query failed: " . mysql_error());
		}

		//CATERGORY START
		$cat_id_query = "SELECT cat_id from category WHERE category= \"$category\"";
		$cat_id_result = mysql_query($cat_id_query) or die("Query failed : " . mysql_error());
		$cat_id_array = mysql_fetch_array($cat_id_result, MYSQL_ASSOC);
		$cat_id = $cat_id_array['cat_id'];

		// Adding publicaiton/category relationship
		$pub_cat_query = "UPDATE pub_cat SET cat_id = $cat_id WHERE pub_id=$pub_id";
		$pub_cat_result = mysql_query($pub_cat_query) or die("Query failed: " . mysql_error());

		$pub_cat_info_query1 = "DELETE FROM pub_cat_info WHERE pub_id=$pub_id";
		$pub_cat_info_result1 = mysql_query($pub_cat_info_query1) or die("Query failed: " . mysql_error());

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
			if($value != ""){
				$pub_cat_info_query = "INSERT INTO pub_cat_info (pub_id, cat_id, info_id, value) VALUES ($pub_id, $cat_id, $info_id, \"$value\")";
				$pub_cat_info_result = mysql_query($pub_cat_info_query) or die("Query failed: " . mysql_error());
			}
		}
		//CAT END

		/* Free resultset */
		echo "<h3> Publication Updated: $title </h3>";
	    echo "You have successfully made changes to the publication: <B>$title</B>.";
		echo "<br><a href=\"../view_publication.php?admin=true&pub_id=$pub_id\">View your recently changed publication</a>";
		echo "<br><a href=\"../list_publication.php?type=view&admin=true\">Back to Publications</a>";
		echo "<br><a href=\"./\">Administrator Page</a>";
	    /*echo "<script language='javascript'>alert('Publication successfully modified.');document.write(location.href='./')</script> \n";*/
	    echo "</body></html>";
	    exit;
	}/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$user_query = "SELECT name FROM user WHERE login=\"".$_SERVER['PHP_AUTH_USER']."\"";
	$user_result = query_db($user_query);
	$user_array = mysql_fetch_array($user_result, MYSQL_ASSOC);

		$title = str_replace("\"","'",$title);
		$abstract = str_replace("\"","'",$abstract);
		$venue = str_replace("\"","'",$venue);
		$extra_info = str_replace("\"","'",$extra_info);
		$keywords = str_replace("\"","'",$keywords);

	$check_query = "SELECT pub_id FROM publication WHERE title=\"$title\"";
	$check_result = query_db($check_query);
	$check_array = mysql_fetch_array($check_result, MYSQL_ASSOC);
	if($check_array[pub_id] != ""){
		echo "Failed: The publication <B>$title</B> already exists.";
		echo "<br><a href=\"../view_publication.php?admin=true&pub_id=".$check_array[pub_id]."\">Click here to view this publication</a>";
		echo "<br><a href=\"../list_publication.php?type=view&admin=true\">Back to Publications</a>";
		echo "<br><a href=\"./\">Administrator Page</a>";
		exit;
	}

	/* Performing SQL query */
	$pub_query = "INSERT INTO publication (pub_id, title, paper, abstract, keywords, published, venue, extra_info, submit, updated)
	              VALUES (NULL, \"$title\", \"\", \"$abstract\", \"$keywords\", \"$pubdate\", ";
				    if($venue_id == -1) $pub_query .= "\"\", ";
					else if(($venue_id != "")&&($venue_id != -2)) $pub_query .= "\"venue_id:<$venue_id>\", ";
		 			else $pub_query .= "\"$venue\", ";
				$pub_query .= "\"$extra_info\", \"".$user_array['name']."\", \"$currdate\")";
	$pub_result = query_db($pub_query);

	$pub_id_query = "SELECT pub_id from publication WHERE title=\"$title\"";
	$pub_id_result = query_db($pub_id_query);
	$pub_id_array = mysql_fetch_array($pub_id_result, MYSQL_ASSOC);
	$pub_id = $pub_id_array[pub_id];

	// Pointers
		singlequery("DELETE FROM pointer WHERE pub_id=$pub_id");
		// External
		for($a = 0; $a < $ext; $a++){
		    $tempname = "extname".$a;
			$tempvalue = "extvalue".$a;
			$templink = "extlink".$a;
			$tempvalue = "<a href=\\\"".$$templink."\\\" target=\\\"_blank\\\">".$$tempvalue."</a>";
			singlequery("INSERT INTO pointer (pub_id, type, name, value) VALUES ($pub_id, \"ext\", \"".$$tempname."\", \"".$tempvalue."\")");
		}
		// Internal
		for($a = 0; $a < $intpoint; $a++){
			$tempvalue = "intpointer".$a;
		    singlequery("INSERT INTO pointer (pub_id, type, name, value) VALUES ($pub_id, \"int\", \"-\", \"".$$tempvalue."\")");
		}

	// Did the user choose a paper?
	if($nopaper == "true"){
		$pub_update_query = "UPDATE publication SET paper=\"No paper\" WHERE pub_id=$pub_id";
		$pub_update_result = query_db($pub_update_query);
	}
	else{
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
	}
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

		$temp_type = "type".$i;
			if($$temp_type == "")
				$$temp_type = "Additional Material ".$i;

		if ($$temp_size > 0) {		// Make sure there's actually a file uploaded

			// Store the path
			$additional_path[$i] = $absolute_files_path . $pub_id . "/additional_" . $$temp_name;
			$additional_link[$i] = "/" . $relative_files_path . $pub_id . "/additional_" . $$temp_name;

			// Check if there is actually a file uploaded at uploadadditional[$i]
			if (is_uploaded_file($$temp)) {
				copy($$temp, $additional_path[$i]);
				chmod($additional_path[$i], 0777);
			}
			$add_query = "INSERT INTO additional_info (add_id, location, type) VALUES (NULL, \"$additional_link[$i]\", \"".$$temp_type."\")";
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

	$temp = "";
	$authors = split(",",$selected_authors);
	for ($i = 0; $i < count($authors); $i++) {
		if ($authors[$i] != "") {
			$temp .= " (" . $pub_id . "," . $authors[$i] . "," . $i . "),";
		}
	}

	$temp = substr_replace($temp, "", (strlen($temp) - 1), strlen($temp));

	// Adding publication/author relationship(s)
	if ($temp != "") {
		$pub_author_query = "INSERT INTO pub_author (pub_id, author_id, rank) VALUES $temp";
		$pub_author_result = mysql_query($pub_author_query) or die("Query failed: " . mysql_error());
	}
	//CATERGORY START
	$cat_id_query = "SELECT cat_id from category WHERE category=\"$category\"";
	$cat_id_result = mysql_query($cat_id_query) or die("Query failed : " . mysql_error());
	$cat_id_array = mysql_fetch_array($cat_id_result, MYSQL_ASSOC);
	$cat_id = $cat_id_array[cat_id];

	// Adding publicaiton/category relationship
	$pub_cat_query = "INSERT INTO pub_cat (pub_id, cat_id) VALUES ($pub_id, $cat_id)";
	$pub_cat_result = mysql_query($pub_cat_query) or die("Query failed: " . mysql_error());

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
		if($value != ""){
		$pub_cat_info_query = "INSERT INTO pub_cat_info (pub_id, cat_id, info_id, value) VALUES ($pub_id, $cat_id, $info_id, \"$value\")";
		$pub_cat_info_result = mysql_query($pub_cat_info_query) or die("Query failed: " . mysql_error());
		}
	}
	//CAT END
    /* Free resultset */
    mysql_free_result($pub_id_result);
    mysql_free_result($cat_id_result);
    mysql_free_result($info_id_result);
	echo "<h3> Publication added: $title </h3>";
    echo "You have successfully added the following publication:<B>$title</b>.<br>";
	echo "<br><a href=\"../view_publication.php?admin=true&pub_id=$pub_id\">View your recently added publication</a>";
	echo "<br><a href=\"../list_publication.php?type=view&admin=true\">Back to Publications</a>";
	echo "<br><a href=\"./\">Administrator Page</a>";
    if ($Save){
		echo "<script language='javascript'>setTimeout(\"top.location.href = './'\",5000)</script>";
	}
    /* Closing connection */
    disconnect_db($link);

	echo "<script language='javascript'>alert('Publication successfully added.');document.write(location.href='/.)</script>";
?>

</body>
</html>
