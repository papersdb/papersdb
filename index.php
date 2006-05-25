<?php ;

// $Id: index.php,v 1.14 2006/05/25 01:36:18 aicmltec Exp $

/**
 * \file
 *
 * \brief Main page for application.
 *
 * Main page for public access, provides a login, and a function that selects
 * the most recent publications added.
 */

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pdPublication.php';

$db= & dbCreate();
$pub_query= $db->select('publication', '*', '', "index.php", array (
                            'ORDER BY' => 'updated DESC'
                            ));

$row= $db->fetchObject($pub_query);

htmlHeader('Papers Database');
echo "<body>\n";
pageHeader();
navigationMenu();

echo "<div id='content'>" . "Recent Additions:" . "<ul>\n";

$stringlength= 0;
while ($row && ($stringlength <= 300)) {
    $pub= new pdPublication($row);

    if (strlen($pub->title) < 60)
        $stringlength += 60;
    else
        if (strlen($pub->title) <= 120)
            $stringlength += 120;
        else
            if (strlen($pub->title) > 120)
                $stringlength += 180;
    if ($stringlength > 300)
        break;
    echo "<li><a href=\"view_publication.php?pub_id=" . $pub->pub_id . "\">";
    echo "<b>" . $pub->title . "</b></a></li>\n";
    $row= $db->fetchObject($pub_query);
}

echo "<br/>&nbsp;\n" . "<br/>&nbsp;\n" . "</div>\n";

pageFooter();

echo "</body>\n</html>\n";

$db->close();

?>
