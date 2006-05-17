<html>
<body>
<?
require('functions.php');
/* CV.PHP
 This file outputs all the search results given to it in a CV format.
 This is mainly for the authors needing to publish there CV.
 Given the ID numbers of the publications, it extracts the information from
 the database and outputs the data in a certain format.
 Input: $_POST['pub_ids'] - a string file of the publication ids seperated by commas
 Output: CV Format
*/

$link = connect_db();
$ids = split(",", $_POST['pub_ids']);

for($a = 0; $a < count($ids); $a++){  //Query the database for most of the information, code runs for each ID
	echo "<b>[".($a+1)."]</b> ";
	$pub_id = $ids[$a];
	$pub_query = "SELECT * FROM publication WHERE pub_id=$pub_id";
	$pub_result = query_db($pub_query);
	$pub_array = mysql_fetch_array($pub_result, MYSQL_ASSOC);

	$cat_query = "SELECT category.category FROM category, pub_cat
		WHERE category.cat_id=pub_cat.cat_id
		AND pub_cat.pub_id=$pub_id";
	$cat_result = query_db($cat_query);
	$cat_array = mysql_fetch_array($cat_result, MYSQL_ASSOC);

	$add_query = "SELECT additional_info.location, additional_info.type FROM additional_info, pub_add
		WHERE additional_info.add_id=pub_add.add_id
		AND pub_add.pub_id=$pub_id";
	$add_result = query_db($add_query);

	$author_query = "SELECT author.author_id, author.name FROM author, pub_author
		WHERE author.author_id=pub_author.author_id
		AND pub_author.pub_id=$pub_id ORDER BY pub_author.rank";
	$author_result = query_db($author_query);

	$info_query = "SELECT info.info_id, info.name FROM info, cat_info, pub_cat
		WHERE info.info_id=cat_info.info_id
		AND cat_info.cat_id=pub_cat.cat_id AND pub_cat.pub_id=$pub_id";
	$info_result = query_db($info_query);

		 // AUTHORS - Outputs the Authors for the ID
				$authors = NULL;
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
					$temp = split(",",$author_line[name]);
					$authors[count($authors)] = substr(trim($temp[1]),0,1).". ".$temp[0];
				}
				for($author_count = 0; $author_count < count($authors)-1; $author_count++){
					echo $authors[$author_count];
					if($author_count == count($authors)-2)
						echo " and ".$authors[count($authors)-1];
					else
						echo ", ";

				}
		//  Output the Title (if this doesn't exist we have a problem)
				echo ", \"".$pub_array[title]."\"";
		//  VENUE - Checks to see if its unique or an ID and takes the right action for each
		if($pub_array[venue] != "") {
	  		$temp_array = split("venue_id:<", $pub_array[venue]);
				 if($temp_array[1] != ""){//If an ID exists, extract the information
				 	$temp_array = split(">", $temp_array[1]);
				 	$venue_id = $temp_array[0];
					$venue_query = "SELECT * FROM venue WHERE venue_id=$venue_id";
    				$venue_result = mysql_query($venue_query) or die("Query failed : " . mysql_error());
					$venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC);
					$venue_name = $venue_line[name];
					$venue_url = $venue_line[url];
					$venue_type = $venue_line[type];
					$venue_data = $venue_line[data];


					if(($venue_name != null)&&($venue_data != null))
						echo ",
						".$venue_type.": ".$venue_name.", ".$venue_data;


					}
				else  // If no ID exist output the unique venue
	    			echo ", ".strip_tags($pub_array[venue]);
	  	}
		// Additional Information - Outputs the category specific information if it exists
		while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)) {
			$info_id = $info_line[info_id];
			$value_query = "SELECT pub_cat_info.value FROM pub_cat_info, pub_cat
				WHERE pub_cat.pub_id=$pub_id
				AND pub_cat.cat_id=pub_cat_info.cat_id
				AND pub_cat_info.pub_id=$pub_id
				AND pub_cat_info.info_id=$info_id";
			$value_result = mysql_query($value_query) or die("Query failed : " . mysql_error());
			$value_line = mysql_fetch_array($value_result, MYSQL_ASSOC);
			if($value_line[value] != null)
				echo ", ".$value_line[value];
		}



// DATE - Parses the date file, and the outputs it
$string = "";
$published = split("-",$pub_array[published]);
if($published[1] != 00)
	$string .= date("F", mktime (0,0,0,$published[1]))." ";
if($published[2] != 00)
	$string .= $published[2].", ";
if($published[0] != 0000)
	$string .= $published[0];

if($string != "")
	echo ", ".$string.".";
else
	echo ".";

 echo "\n<BR><BR>\n";
} ?>

</body>
</html>
