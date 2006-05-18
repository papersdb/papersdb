<?php

  // $Id: index.php,v 1.8 2006/05/18 15:06:52 aicmltec Exp $

  /**
   * \file
   *
   * \brief Main page for application.
   *
   * Main page for public access, provides a login, and a function that selects
   * the most recent publications added.
   */

include_once('functions.php');
include_once('check_login.php');
require_once('includes/pdPublication.php');

?>

<html>
<head>
<title>Papers Database</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <link rel='stylesheet' type='text/css' href='style.css' />
</head>
<body>

<?

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

$db->close();

print "<br/>&nbsp;\n"
. "<br/>&nbsp;\n"
. "</div>\n";

pageFooter();

echo "</body>\n</html>\n";

?>

