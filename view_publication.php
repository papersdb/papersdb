<?php

  // $Id: view_publication.php,v 1.5 2006/05/11 23:20:21 aicmltec Exp $

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

if (isset($admin) && $admin == "true")
    include 'headeradmin.php';
else
    include 'header.php';


isValid($pub_id);

$pub = new pdPublication();
$pub->dbLoad($pub_id);

if ($pub->paper == "No paper")
    $paperstring = "No Paper at this time.";
else {
    $paperstring = "<a href=\".".$pub->paper;
    $papername = split("paper_", $pub->paper);
    $paperstring .= "\"> Paper:<i><b>$papername[1]</b></i></a>";
}
?>

<html>
<head>
<title><? echo $pub->title ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    </head>

<body>
<table width="750" border="0" cellspacing="0" cellpadding="6">
    <tr>
<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2">
    <b>Title: </b></font></td>
<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2">
    <b><? echo $pub->title ?></b></font></td>
</tr>
<tr>
<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2">
    <b>Category: </b></font></td>
<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2">
    <? echo $pub->category ?></font></td>
</tr>
<tr>
<td width="25%"><div id="emph">Paper: </div></td>
<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $paperstring; ?></font></td>
</tr>

<?
if(isset($pub->additional_info)) {
    echo "<tr>";
    echo "<td width=\"25%\"><div id=\"emph\">Additional Materials:</div></td>";
    echo "<td width=\"75%\"><div id=\"emph\">";

    $add_count = 0;
    $temp = "";
    foreach ($pub->additional_info as $info) {
        $temp = split("additional_", $info->location);
        echo "<a href=./" . $info->location . ">";
        if($info->type != "")
            echo $info->type.":<i><b>".$temp[1]."</b></i>";
        else
            echo "Additional Material " . ($add_count + 1)
                . ":<i><b>".$temp[1]."</b></i>";

        echo "</a><br>";
        $add_count++;
    }

    echo "</div></td></tr>";
}
?>
<tr>
<td width="25%"><div id="emph">Authors:</div></td>
<td width="75%"><div id="emph">
    <?
foreach ($pub->author as $author) {
    echo "<a href=\"./view_author.php?";
    if(isset($admin) && $admin == "true")
        echo "admin=true&";
    echo "popup=true&author_id=" . $author->author_id
    . "\" target=\"_self\"  'Help', "
    . "'width=500,height=250,scrollbars=yes,resizable=yes'); return false\">";
    echo $author->name;
    echo "</a><br>";
}
?>
</div>
</td>
</tr>
<tr>
<td width="25%"><div id="emph">Abstract: </div></td>
<td width="75%"><? echo stripslashes($pub->abstract) ?></td>
</tr>
<?

if(!is_null($pub->venue_info)) {
    if(isset($pub->venue_info->type)) {
        echo "<tr><td width=\"25%\"><div id=\"emph\">";
        echo $pub->venue_info->type . ":&nbsp;</div></td><td width=\"75%\">";

        if(isset($pub->venue_info->url))
            echo " <a href=\"" . $pub->venue_info->url."\" target=\"_blank\">";

        echo $pub->venue_info->name;
        if(isset($pub->venue_info->url))
            echo "</a>";

        if($pub->venue_info->data != ""){
            echo "</td></tr><tr><td width=\"25%\"><div id=\"emph\">";
            if($pub->venue_info->type == "Conference")
                echo "Location:&nbsp;";
            else if($pub->venue_info->type == "Journal")
                echo "Publisher:&nbsp;";
            else if($pub->venue_info->type == "Workshop")
                echo "Associated Conference:&nbsp;";
            echo "</td><td width=\"75%\">" . $pub->venue_info->data;
        }
        echo "</td></tr>";
    }
}
else{
    print "<tr>"
        . "<td width=\"25%\"><div id=\"emph\">Publication Venue: </div></td>"
        . " <td width=\"75%\">" . stripslashes($pub->venue) . "</td>"
        . "</tr>";
}

if (isset($pub->extPointer)) {
    foreach ($pub->extPointer as $ext) {
        print "<tr>"
            . "<td width=\"25%\"><div id=\"emph\">" . $ext->name
            . ": </div></td>"
            . "<td width=\"75%\">" . $ext->value . "</td>"
            . "</tr>";
    }
}

if (isset($pub->intPointer)) {
    foreach ($pub->intPointer as $int) {
        print "<tr>"
            . "<td width=\"25%\"><div id=\"emph\">Connected with: </div></td>"
            . "<td width=\"75%\"><a href=\"view_publication.php?";
        if(isset($admin) && ($admin == "true"))
            echo "admin=true&";

        $intPub = new pdPublication();
        $intPub->dbLoad($int->value);

        echo "pub_id=" . $int->value . "\">" . $intPub->title . "</a>";
    }
}
?>
</td>
</tr>
<tr>
<td width="25%"><div id="emph">Keywords: </div></td>
<td width="75%"><div id="emph">
    <?
$keywords = explode(";", $pub->keywords);

// remove all keywords of length 0
foreach ($keywords as $key => $value) {
    if ($value == "")
        unset($keywords[$key]);
}
print implode(",", $keywords);

?>
</div>
</td>
</tr>
<?

foreach ($pub->info as $info) {
    if(!is_null($info->value)) {
		echo "<tr>"
            . "<td width=\"25%\"><div id=\"emph\">" . $info->name
            . ": </div></td>"
            . "<td width=\"75%\"><div id=\"emph\">" . $info->value
            . "</div></td>"
            . "</tr>";
    }
}


//PARSE DATES
$string = "";
$published = split("-",$pub->published);
if($published[1] != 00)
	$string .= date("F", mktime (0,0,0,$published[1]))." ";
if($published[2] != 00)
	$string .= $published[2].", ";
if($published[0] != 0000)
	$string .= $published[0];

if($string != ""){
    ?>
    <tr>
		<td width="25%"><div id="emph">Date Published: </b></font></td>
		<td width="75%"><div id="emph"><? echo $string ?></font></td>
        </tr>
        <tr><td></td><td align=right>
        <? }
$string = "";
$published = split("-",$pub->updated);
if($published[1] != 00)
	$string .= date("F", mktime (0,0,0,$published[1]))." ";
if($published[2] != 00)
	$string .= $published[2].", ";
if($published[0] != 0000)
	$string .= $published[0];

if($string != "") {
    print "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\">"
        . "Last updated ". $string . "</font><br>";
}
?>
<font face="Arial, Helvetica, sans-serif" size="1">
    <?
echo "Submitted by " . $pub->submit . "<br/>";
?>

</font>
</td></tr>
</table>
<?
if(isset($admin) && $admin == "true"){
    echo "<BR><b><a href=\"Admin/add_publication.php?pub_id=" . quote_smart($pub_id) . "\">Edit this publication</a>&nbsp;&nbsp;&nbsp;";
    echo "<a href=\"Admin/delete_publication.php?pub_id=" . quote_smart($pub_id) . "\">Delete this publication</a></b><br><BR>";
}
back_button(); ?>
</body>
</html>
