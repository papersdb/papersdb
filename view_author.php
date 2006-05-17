<?php ;

// $Id: view_author.php,v 1.3 2006/05/17 23:08:30 aicmltec Exp $

/**
 * \file
 *
 * \brief Given a author id number, this displays all the info about
 * the author.
 *
 * If the author has only a few publications it will display the title and link
 * to them. If the author has more then 6 then it will link to a seperate page
 * of a list of publications by that author.
 *
 * if a user is logged in, they have the option of editing or deleting the
 * author.
 */

include_once('functions.php');
include_once('check_login.php');

htmlHeader('View Author');

/* Connecting, selecting database */
$link = connect_db();

/* Performing SQL query */
$author_query = "SELECT * FROM author WHERE author_id=" . quote_smart($author_id);
$author_result = mysql_query($author_query) or die("Query failed : " . mysql_error());
$author_array = mysql_fetch_array($author_result, MYSQL_ASSOC);

$int_query = "SELECT interest.interest FROM interest, author_interest WHERE interest.interest_id=author_interest.interest_id AND author_interest.author_id=" . quote_smart($author_id);
$int_result = mysql_query($int_query) or die("Query failed : " . mysql_error());
?>

<body><h3><? echo $author_array['name'] ?></h3><br>
<table width="525" border="0" cellspacing="0" cellpadding="6">
    <tr>
<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Name: </b></font></td>
<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><b><? echo $author_array['name'] ?></b></font></td>
</tr>
<?php if (isset($author_array['title']) && trim($author_array['title']) != "") {?>
                                                                                <tr>
                                                                                <td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Title: </b></font></td>
                                                                                <td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $author_array['title'] ?></font></td>
                                                                                </tr>
                                                                                <?php } ?>
                                                                                <tr>
                                                                                <td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Email: </b></font></td>
                                                                                <td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo "<a href=\"mailto:".$author_array['email']."\">".$author_array['email']."</a>"; ?></font></td>
                                                                                </tr>
                                                                                <tr>
                                                                                <td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Organization: </b></font></td>
                                                                                <td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $author_array['organization'] ?></font></td>
                                                                                </tr>
                                                                                <tr>
                                                                                <td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Webpage: </b></font></td>
                                                                                <td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? if (isset($author_array['webpage']) && trim($author_array['webpage']) != "") echo "<a href=\"" . $author_array['webpage'] . "\" target=\"_blank\">" . $author_array['webpage'] . "</a>"; else echo "none"; ?></font></td>
                                                                                </tr>
                                                                                <tr>
                                                                                <td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Interest(s): </b></font></td>
                                                                                <td width="75%"><font face="Arial, Helvetica, sans-serif" size="2">
    <?
                                                                                while ($int_line = mysql_fetch_array($int_result, MYSQL_ASSOC)) {
                                                                                    echo $int_line[interest] . "<br>";
                                                                                }
?>
</font>
</td>
</tr>

<?
$count_query = "SELECT COUNT(p.pub_id) as countid
			FROM publication p, pub_author a
			WHERE a.author_id = " . quote_smart($author_id) . "
			AND a.pub_id = p.pub_id
			ORDER BY p.title ASC";
$count_result = mysql_query($count_query) or die("Query failed : " . mysql_error());
$countid = mysql_fetch_array($count_result, MYSQL_ASSOC);
$count = $countid[countid];
if($count < 6){
    $pub_query = "SELECT p.pub_id, p.title, p.paper,
					p.abstract, p.keywords, p.published, p.updated
				FROM publication p, pub_author a
				WHERE a.author_id = " . quote_smart($author_id) . "
				AND a.pub_id = p.pub_id
				ORDER BY p.title ASC";
    $pub_result = mysql_query($pub_query) or die("Query failed : " . mysql_error());
    $itran = false;
    while ($pub_line = mysql_fetch_array($pub_result, MYSQL_ASSOC)) {
        if(!$itran)
            echo "<tr><td width=\"25%\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><b>Publications:</b></font></td>";
        else
            echo "<tr><td width =\"25%\"></td>";
        echo "<td width =\"75%\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"view_publication.php?"; if($admin == "true") echo "admin=true&"; echo "pub_id=" . $pub_line[pub_id] . "\">" . $pub_line[title] . "</a></font></td></tr>";

        $itran = true;
    }
    if(!$itran)echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><li>No publications by this author.</font></td></tr>";
}
else {
    ?>
    <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Publications:</b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><a href="./list_publication.php?<? if($admin == "true") echo "admin=true&"; ?>type=view&author_id=<?php echo $author_id; ?>">View All Publications</a></font></td>
        </tr>
        <? }

?>
</table>
<?
if($admin == "true"){
    echo "<BR><b><a href=\"Admin/edit_author.php?author_id=".$author_id."\">Edit this author</a>&nbsp;&nbsp;&nbsp;";
    echo "<a href=\"Admin/delete_author.php?author_id=".$author_id."\">Delete this author</a></b><br><BR>";
}
back_button(); ?>
</body>
</html>

<?
  /* Free resultset */
mysql_free_result($author_result);
mysql_free_result($int_result);

/* Closing connection */
disconnect_db($link);
?>
