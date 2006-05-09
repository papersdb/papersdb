<?php

require_once "DB.php";

class Publication {
    
}

function db_connect() {

    // Connect to the DB via PEAR
	$dsn = "mysql://papersdb@tcp(abee.cs.ualberta.ca:3306)/pubDB";
	$db = DB::connect($dsn);
	
	// With DB::isError you can differentiate between an error or
    // a valid connection.
    if (DB::isError($db)) {
	die ($db->getMessage());
    }
	echo "worked";
    return $db;
}

function generate_select_body ($start, $end, $compare) {
    for ($i = $start; $i <= $end; $i++) {
	echo "  <option value='$i' ";
	if ($compare == $i) echo "selected";
	echo "> $i </option> \n";
    }
}

function generate_select($name, $start, $end, $compare) {
    echo "<select name='$name'> \n";
    generate_select_body ($start, $end, $compare);
    echo "</select> \n";
}

function generate_select_date($name, $start, $end, $compare = NULL) {
    echo "<select name='$name'> \n";
	echo " <option value='--'"; 
	if($compare == NULL) echo "selected"; 
	echo "> -- </option>";
	
	for ($i = $start; $i <= $end; $i++) {
		if($compare == $i)
			echo "  <option value='$i' selected> $i </option> \n";	
		else 
			echo "  <option value='$i'> $i </option> \n";	
		}
    echo "</select> \n";
}

function generate_select_month($name, $start, $end, $compare = NULL) {
    echo "<select name='$name'> \n";
	echo " <option value='--'"; 
	if($compare == NULL) echo "selected"; 
	echo "> -- </option>";
    for ($i = $start; $i <= $end; $i++) {
	echo "  <option value='$i' ";
	if ($compare == $i) echo "selected";
	echo "> " . date ("F", mktime (0,0,0,$i)) . " </option> \n";
    }
    echo "</select> \n";
}


function get_num_db_materials ($pubID) {
    global $db;

    if ($db == NULL) {
	return;
    }

    $rval = NULL;
    
    $sql = "select * from pub_add where pub_id = $pubID";
    $res = $db->query($sql);

    // Check for error in query
    if (DB::isError($res)) {
	// If there's an error we'll assume there are zero additional materials
	return 0;
    }
    
    // Return the number of rows in the result set.
    return $res->numRows();
}


function get_publication_info ($pubID) {
    global $db;
    $rval = NULL;
    
    $sql = "select * from publication where pub_id = $pubID";
    $res = $db->query($sql);
    // Check for error in query
    if (DB::isError($result)) {
	
    }

    // Make sure there's one and only one publication with this ID
    if ($res->numRows() != 1) {
	echo "Error: There is no publication with this ID!";
	return $rval;
    }


    // Fetch the row
    $rval = $res->fetchRow(DB_FETCHMODE_ASSOC);
    return $rval;
}

function get_category ($pubID) {
    global $db;
    if ($db == NULL) {
	return;
    }

    $rval = NULL;
    

    $sql = "select B.category, B.cat_id from pub_cat A, category B where pub_id = $pubID AND A.cat_id = B.cat_id";
    $res = $db->query($sql);

    if (DB::isError($result)) {
	echo "Error: Couldn't locate category for paper.";
	return $rval;
    }

    $rval = $res->fetchRow(DB_FETCHMODE_ASSOC);
    return $rval;
}


function get_authors ($pubID) {
    global $db;
    if ($db == NULL) {
	return;
    }

    $rval = NULL;
    

    $sql = "select B.name from pub_author A, author B where pub_id = $pubID AND A.author_id = B.author_id";
    $res = $db->query($sql);

    if (DB::isError($result)) {
	echo "Error: Couldn't locate authors for paper.";
	return $rval;
    }


    // Use a hash to return the existence of a author with a paper.
    // This makes checking in the form very easy.
    while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$rval[$row['name']] = 1;
    }

    return $rval;
}


function get_info_field_value ($pubID, $catID, $infoID) {
    global $db;
    if ($db == NULL) {
	return;
    }

    $rval = NULL;
    

    $sql = "select value from pub_cat_info where "
	 . "pub_id = $pubID AND "
	 . "cat_id = $catID AND "
	 . "info_id = $infoID";

    $res = $db->query($sql);

    if (DB::isError($res)) {
	return $rval;
    }

    $row = $res->fetchRow(DB_FETCHMODE_ASSOC);
    return $row['value'];
}


function get_info_id ($catID, $infoName) {
    global $db;

    if ($db == NULL) {
	return;
    }

    $rval = NULL;
    
    $info_id_get = "SELECT info_id FROM info WHERE name = \"$infoName\"";
    $info_id_result = mysql_query($info_id_get) or die("Query failed: " . mysql_error());
	$info_id_line = mysql_fetch_array($info_id_result, MYSQL_ASSOC);
	
	return $info_id_line['info_id'];
}


/*function get_info_id ($catID, $infoName) {
    global $db;

    if ($db == NULL) {
	return;
    }

    $rval = NULL;
    
    $sql = "select info_id from info where name = \"$infoName\"";
    $res = $db->query($sql);

    if (DB::isError($res)) {
	echo "Error: Couldn't locate info category with name $infoName.";
	return $rval;
    }

    // andy_note: We'll just take the first one that matches - this
    // needs to be fixed.
    while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$infoID = $row['info_id'];

	// andy_note: The following is what should be fixed!
	$sql = "select * from cat_info where cat_id = $catID and info_id = $infoID";
	$res2 = $db->query($sql);
	while($row2 = $res2->fetchRow(DB_FETCHMODE_ASSOC)) {
	    return $row2['info_id'];
	}

    }

    return $rval;
}*/

function removematerial ($pubID, $i) {
    global $db;
 
 	if ($db == NULL) {
	
	return "Did not delete succesfully, DB is NULL";
    }

    $rval = NULL;
    
    $sql = "select B.location, B.add_id from pub_add A, additional_info B where A.pub_id = $pubID AND A.add_id = B.add_id ORDER BY B.add_id";
    $res = $db->query($sql);

    if (DB::isError($res)) {
	echo "Error: Couldn't locate additional material for pub $pubID and item number $i.";
    }
	$row = $res->fetchRow(DB_FETCHMODE_ASSOC, $i);
	
	$location = $row['location'];
	
	$query = "SELECT * FROM additional_info WHERE location = \"$location\"";

	$result = query_db($query);
	$value = mysql_fetch_array($result, MYSQL_ASSOC);

	$add_id = $value['add_id'];
	
	
	$pub_query = "SELECT * FROM publication WHERE pub_id=$pubID";
	$pub_result = query_db($pub_query);
	
	$query = "DELETE FROM additional_info WHERE add_id = $add_id";
	$result = query_db($query); 
	
	$query = "DELETE FROM pub_add WHERE add_id = $add_id AND pub_id = $pubID";
	$result = query_db($query);
	
	
	$absolute_path = "/usr/abee4/cshome/paulsen/web_docs/paperdb";
	system("rm -rf " . $absolute_path . $location);
	$location = split("/",$location);
	$name = $location[3];
	return "Deleted $name Succesfully";
	
}
function get_additional_material ($pubID, $i) {
    global $db;
    if ($db == NULL) {
	return "Error";
    }

    $rval = NULL;
    
    $sql = "SELECT B.location, B.add_id, B.type FROM pub_add A, additional_info B WHERE A.pub_id = $pubID AND A.add_id = B.add_id ORDER BY B.add_id";
    $res = $db->query($sql);

    if (DB::isError($res)) {
	echo "Error: Couldn't locate additional material for pub $pubID and item number $i.";
	return $rval;
    }

    $row = $res->fetchRow(DB_FETCHMODE_ASSOC, $i);
	$temp_string = $row['location'];
	$temp_string2 = split("/additional_",$temp_string);
	$temparray[0] = $temp_string2[1];
	$temparray[1] = $row['type'];
    return $temparray;
}



?>
