<?php

/* DB connection is only hard coded here and in lib_functions. */

/* ***IMPORTANT***
	At present, the files are stored at:
		/compsci/abee/cshome/papersdb/web_docs/uploaded_files/{pub_id}
	The naming scheme is:
		{paper/additional}_{name_of_file}.file_extension
*/
$absolute_path = "/compsci/abee/cshome/papersdb/web_docs/";
$relative_files_path = "uploaded_files/";
$absolute_files_path = $absolute_path . $relative_files_path;

function connect_db() {
	$link = mysql_connect("abee.cs.ualberta.ca:3306", "papersdb", "")
		or die("Could not connect : " . mysql_error());
	mysql_select_db("pubDB") or die("Could not select database");
	return $link;
}

function query_db($query) {
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	return $result;	
}

function disconnect_db($link) {
	mysql_close($link);
}


?>
