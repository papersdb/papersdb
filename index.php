<?php ;

// $Id: index.php,v 1.11 2006/05/19 19:41:20 aicmltec Exp $

/**
 * \file
 *
 * \brief Main page for application.
 *
 * Main page for public access, provides a login, and a function that selects
 * the most recent publications added.
 */


ini_set("include_path", ini_get("include_path") . ":.:./includes:./HTML");

require_once 'functions.php';
require_once 'check_login.php';
require_once 'pdPublication.php';

htmlHeader('Papers Database');
pageHeader();
navigationMenu();

$db =& dbCreate();
$pub_query = $db->select('publication', '*', '', "index.php",
                         array('ORDER BY' => 'updated DESC'));

$stringlength=0;
$row = $db->fetchObject($pub_query);

print "<div id='content'>"
. "Recent Additions:"
. "<ul>\n";

while ($row && ($stringlength <= 300)) {
    $pub = new pdPublication($row);

    if(strlen($pub->title) < 60) $stringlength += 60;
    else if(strlen($pub->title) <= 120) $stringlength += 120;
    else if(strlen($pub->title) > 120) $stringlength += 180;
    if($stringlength > 300) break;
    echo "<li><a href=\"view_publication.php?pub_id=".$pub->pub_id."\">";
    echo "<b>".$pub->title."</b></a></li>\n";
    $row = $db->fetchObject($pub_query);
}

print "<br/>&nbsp;\n"
. "<br/>&nbsp;\n"
. "</div>\n";

pageFooter();

echo "</body>\n</html>\n";

$db->close();

?>

