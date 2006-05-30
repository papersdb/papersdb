<?php ;

// $Id: view_publication.php,v 1.17 2006/05/30 00:09:07 aicmltec Exp $

/**
 * \file
 *
 * \brief View Publication
 *
 * Given a publication id number this page shows most of the information about
 * the publication. It does not display the extra information which is hidden
 * and used only for the search function. It provides links to all the authors
 * that are included. If a user is logged in, then there is an option to edit
 * or delete the current publication.
 */

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/navMenu.php';
require_once 'includes/pdPublication.php';

require_once 'HTML/Table.php';

htmlHeader('View Publication');
echo "<body>\n";
pageHeader();
navMenu();
echo "<div id='content'>\n";

if (!isset($_GET['pub_id'])) {
    errorMessage();
}

$db =& dbCreate();

$pub_id = $_GET['pub_id'];
isValid($pub_id);

$pub = new pdPublication();
$pub->dbLoad($db, $pub_id);

if ($pub->paper == "No paper")
    $paperstring = "No Paper at this time.";
else {
    $paperstring = "<a href=\".".$pub->paper;
    $papername = split("paper_", $pub->paper);
    $paperstring .= "\"> Paper:<i><b>$papername[1]</b></i></a>";
}

$tableAttrs = array('width' => '600',
                    'border' => '0',
                    'cellpadding' => '6',
                    'cellspacing' => '0');
$table = new HTML_Table($tableAttrs);
$table->setAutoGrow(true);

$table->addRow(array('Title:', $pub->title));
$table->updateCellAttributes($table->getRowCount() - 1, 1,
                             array('id' => 'emph'));
$table->addRow(array('Category:', $pub->category));
$table->addRow(array('Paper:', $paperstring));

if(isset($pub->additional_info)) {
    $table->addRow(array('Additional Materials:',
                         additional2Html($pub)));
}

$table->addRow(array('Author(s):', author2Html($pub)));

$table->addRow(array('Abstract:', stripslashes($pub->abstract)));

venueRowsAdd($pub, $table);
extPointerRowsAdd($pub, $table);
intPointerRowsAdd($db, $pub, $table);

$table->addRow(array('Keywords:', keywordsGet($pub)));
infoRowsAdd($pub, $table);

$pubDate = publishDateGet($pub);
if ($pubDate != "") {
    $table->addRow(array('Date Published:', $pubDate));
}

$updateStr = lastUpdateGet($pub);
if ($updateStr != "") {
    $updateStr ='Last Updated: ' . $updateStr . '<br/>';
}
$updateStr .= 'Submitted by ' . $pub->submit;

$table->addRow(array('&nbsp;', $updateStr));
$table->updateCellAttributes($table->getRowCount() - 1, 1,
                             array('id' => 'footer'));

$table->updateColAttributes(0, array('id' => 'emph', 'width' => '25%'));

$pub_id = $_GET['pub_id'];
isValid($pub_id);

echo $table->toHtml();

if ($logged_in) {
    echo "<br><b><a href=\"Admin/add_publication.php?pub_id="
        . quote_smart($pub_id)
        . "\">Edit this publication</a>&nbsp;&nbsp;&nbsp;"
        . "<a href=\"Admin/delete_publication.php?pub_id="
        . quote_smart($pub_id)
        . "\">Delete this publication</a></b><br><BR>";
}
echo "</div>\n";

pageFooter();

echo "</body></html>";

$db->close();

function additional2Html(&$pub) {
    if(!isset($pub->additional_info)) return "No Additional Materials";

    $additionalMaterials = "";
    $add_count = 0;
    $temp = "";
    foreach ($pub->additional_info as $info) {
        $temp = split("additional_", $info->location);
        $additionalMaterials .= "<a href=./" . $info->location . ">";
        if($info->type != "") {
            $additionalMaterials
                .= $info->type.": <i><b>".$temp[1]."</b></i>";
        }
        else {
            $additionalMaterials .= "Additional Material "
                . ($add_count + 1) . ":<i><b>".$temp[1]."</b></i>";
        }

        $additionalMaterials .= "</a><br>";
        $add_count++;
    }
    return $additionalMaterials;
}

function author2Html(&$pub) {
    $authorsStr = "";
    if (is_array($pub->author)) {
        foreach ($pub->author as $author) {
            $authorsStr .= "<a href=\"./view_author.php?";
            if($logged_in)
                $authorsStr .= "admin=true&";
            $authorsStr .= "author_id=" . $author->author_id
                . "\" target=\"_self\"  'Help', "
                . "'width=500,height=250,scrollbars=yes,resizable=yes'); "
                . "return false\">" . $author->name . "</a><br>";
        }
    }
    return $authorsStr;
}

function venueRowsAdd(&$pub, &$table) {
    if($pub->venue == "")  return;

    if(isset($pub->venue_info->type)) {
        $venueStr = "";
        if(isset($pub->venue_info->url))
            $venueStr .= " <a href=\"" . $pub->venue_info->url
                . "\" target=\"_blank\">";

        $venueStr .= $pub->venue_info->name;
        if(isset($pub->venue_info->url))
            $venueStr .= "</a>";
        $table->addRow(array($pub->venue_info->type . ':', $venueStr));

        if($pub->venue_info->data != ""){
            $venueStr .= "</td></tr><tr><td width=\"25%\"><div id=\"emph\">";
            if($pub->venue_info->type == "Conference")
                $venueStr = "Location:";
            else if($pub->venue_info->type == "Journal")
                $venueStr = "Publisher:";
            else if($pub->venue_info->type == "Workshop")
                $venueStr = "Associated Conference:";
            $table->addRow(array($venueStr, $pub->venue_info->data));
        }
    }
    else{
        $table->addRow(array('Publication Venue:', stripslashes($pub->venue)));
    }
}

function extPointerRowsAdd(&$pub, &$table) {
    if (!isset($pub->extPointer)) return;

    foreach ($pub->extPointer as $ext) {
        $table->addRow(array($ext->name . ':', $ext->value));
    }
}

function intPointerRowsAdd(&$db, &$pub, &$table) {
    if (!isset($pub->intPointer)) return;

    foreach ($pub->intPointer as $int) {
        $intLinkStr = "<a href=\"view_publication.php?";
        if($logged_in)
            $intLinkStr .= "admin=true&";

        $intPub = new pdPublication();
        $intPub->dbLoad($db, $int->value);

        $intLinkStr .= "pub_id=" . $int->value . "\">"
            . $intPub->title . "</a>";

        $table->addRow(array('Connected with:', $intLinkStr));
    }
}

function keywordsGet(&$pub) {
    $keywords = explode(";", $pub->keywords);

    // remove all keywords of length 0
    foreach ($keywords as $key => $value) {
        if ($value == "")
            unset($keywords[$key]);
    }
    return implode(",", $keywords);
}

function infoRowsAdd(&$pub, &$table) {
    if (!isset($pub->info)) return;

    foreach ($pub->info as $info) {
        if($info->value != "") {
            $table->addRow(array($info->name . ":", $info->value));
        }
    }
}

function publishDateGet(&$pub) {
    $string = "";
    $published = split("-",$pub->published);
    if($published[1] != 00)
        $string .= date("F", mktime (0,0,0,$published[1]))." ";
    if($published[2] != 00)
        $string .= $published[2].", ";
    if($published[0] != 0000)
        $string .= $published[0];
    return $string;
}

function lastUpdateGet(&$pub) {
    $string = "";
    $published = split("-",$pub->updated);
    if($published[1] != 00)
        $string .= date("F", mktime (0,0,0,$published[1]))." ";
    if($published[2] != 00)
        $string .= $published[2].", ";
    if($published[0] != 0000)
        $string .= $published[0];
    return $string;
}

?>
