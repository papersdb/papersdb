<?php ;

// $Id: cv.php,v 1.6 2006/05/25 01:36:18 aicmltec Exp $

/**
 * \file
 *
 * \brief  This file outputs all the search results given to it in a CV format.
 *
 * This is mainly for the authors needing to publish there CV.  Given the ID
 * numbers of the publications, it extracts the information from the database
 * and outputs the data in a certain format.  Input: $_POST['pub_ids'] - a
 * string file of the publication ids seperated by commas Output: CV Format
 */

require_once 'includes/functions.php';
require_once 'includes/pdPublication.php';

htmlHeader('CV Formatted Results');
echo "</head>\n<body>";

if (!isset($_POST['pub_ids'])) {
    errorMessage();
}

$db =& dbCreate();
$pub_count = 0;
foreach (split(",", $_POST['pub_ids']) as $pub_id) {
    $pub_count++;
    echo "<b>[" . $pub_count . "]</b> ";

    $pub = new pdPublication();
    $pub->dbLoad($db, $pub_id);

    // AUTHORS - Outputs the Authors for the ID
    unset($authors);
    foreach ($pub->authors as $auth) {
        $temp = split(",", $auth->name);
        $authors[] = substr(trim($temp[1]), 0, 1) . ". " . $temp[0];
    }

    echo implode(", ", array_slice($authors, 0, count($authors) - 1))
        . " and " . $authors[count($authors) - 1];

    //  Output the Title (if this doesn't exist we have a problem)
    echo ", \"" . $pub->title . "\"";

    //  VENUE - Checks to see if its unique or an ID and takes the right
    //  action for each
    if (is_object($pub->venue_info)) {
        if (($pub->venue_info->name != null)
            && ($pub->venue_info->data != null)) {
            echo ", " . $pub->venue_info->type.": " .
                $pub->venue_info->name . ", " . $pub->venue_info->data;
        }
    }
    else  {
        // If no ID exist output the unique venue
        echo ", ".strip_tags($pub->venue);
    }

    // Additional Information - Outputs the category specific information
    // if it exists
    foreach ($pub->info as $info) {
        if($info->value != null)
            echo ", " . $info->value;
    }


    // DATE - Parses the date file, and the outputs it
    $string = "";
    $published = split("-", $pub->published);
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

    echo "\n<br/><br/>\n"
        . "</body>\n</html>\n";
}

?>

