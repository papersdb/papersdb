<?php

  // $Id: view_publication.php,v 1.6 2006/05/12 16:55:49 aicmltec Exp $

  /**
   * \file
   *
   * \brief View Publication
   *
   * Given a publication id number this page shows most of the information
   * about the publication. It does not display the extra information which is
   * hidden and used only for the search function. It provides links to all the
   * authors that are included. If a user is logged in, then there is an option
   * to edit or delete the current publication.
   */

require_once('functions.php');
require_once('pdPublication.php');
include_once('header.php');

require_once 'HTML/Table.php';

isValid($pub_id);

makePage();

function additionalHtmlGet(&$pub) {
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

function authorHtmlGet(&$pub) {
    $authorsStr = "";
    if (is_array($pub->author)) {
        foreach ($pub->author as $author) {
            $authorsStr .= "<a href=\"./view_author.php?";
            if(isset($admin) && $admin == "true")
                $authorsStr .= "admin=true&";
            $authorsStr .= "popup=true&author_id=" . $author->author_id
                . "\" target=\"_self\"  'Help', "
                . "'width=500,height=250,scrollbars=yes,resizable=yes'); "
                . "return false\">"
                . $author->name
                . "</a><br>";
        }
    }
    return $authorsStr;
}

function venueRowsAdd(&$pub, &$table) {
    if(!is_null($pub->venue_info)) {
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
    }
    else{
        $table->addRow(array('Publication Venue:', stripslashes($pub->venue)));
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

function publisDateGet(&$pub) {
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

function makePage() {
    global $pub_id;

    print "<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' "
        . "lang='en'>\n"
        . "<head>\n"
        . "<title>" . $pub->title . "</title>\n"
        . "<meta http-equiv='Content-Type' content='text/html; "
        . "charset=iso-8859-1' />"
        . "<link rel='stylesheet' type='text/css' href='style.css' />\n"
        . "</head>\n"
        . "<body>\n";

    if (isset($admin) && $admin == "true")
        include 'headeradmin.php';

    $pub = new pdPublication();
    $pub->dbLoad($pub_id);

    if ($pub->paper == "No paper")
        $paperstring = "No Paper at this time.";
    else {
        $paperstring = "<a href=\".".$pub->paper;
        $papername = split("paper_", $pub->paper);
        $paperstring .= "\"> Paper:<i><b>$papername[1]</b></i></a>";
    }

    $tableAttrs = array('width' => '750');
    $table = new HTML_Table($tableAttrs);
    $table->setAutoGrow(true);
    $table->setAutoFill('n/a');

    $table->addRow(array('Title:',$pub->title));
    $table->addRow(array('Category:', $pub->category));
    $table->addRow(array('Paper:', $paperstring));

    if(isset($pub->additional_info)) {
        $table->addRow(array('Additional Materials:', additionalHtmlGet($pub)));
    }

    $table->addRow(array('Author(s):', authorHtmlGet($pub)));

    $table->addRow(array('Abstract:', stripslashes($pub->abstract)));

    venueRowsAdd($pub, $table);

    if (isset($pub->extPointer)) {
        foreach ($pub->extPointer as $ext) {
            $table->addRow(array($ext->name . ':', $ext->value));
        }
    }

    if (isset($pub->intPointer)) {
        foreach ($pub->intPointer as $int) {
            $intLinkStr = "<a href=\"view_publication.php?";
            if(isset($admin) && ($admin == "true"))
                $intLinkStr .= "admin=true&";

            $intPub = new pdPublication();
            $intPub->dbLoad($int->value);

            $intLinkStr .= "pub_id=" . $int->value . "\">"
                . $intPub->title . "</a>";

            $table->addRow(array('Connected with:', $intLinkStr));
        }
    }

    $table->addRow(array('Keywords:', keywordsGet($pub)));

    foreach ($pub->info as $info) {
        if(!is_null($info->value)) {
            $table->addRow(array($info->name, $info->value));
        }
    }

    $pubDate = publisDateGet($pub);
    if ($pubDate != "") {
        $table->addRow(array('Date Published:', $pubDate));
    }

    $updateDate = lastUpdateGet($pub);
    if ($pubDate != "") {
        $table->addRow(array('&nbsp;', 'Last Updated: ' . $updateDate));
    }

    $table->addRow(array('&nbsp;', 'Submitted By: ' . $pub->submit));

    $table->setColAttributes(0, array('width=\"25%\"'));

    if (isset($admin) && $admin == "true")
        ;
    else {
        pdHeader();
    }

    echo $table->toHtml();
}

if(isset($admin) && $admin == "true"){
    echo "<BR><b><a href=\"Admin/add_publication.php?pub_id=" . quote_smart($pub_id) . "\">Edit this publication</a>&nbsp;&nbsp;&nbsp;";
    echo "<a href=\"Admin/delete_publication.php?pub_id=" . quote_smart($pub_id) . "\">Delete this publication</a></b><br><BR>";
}
back_button();

print "</body></html>";

?>
