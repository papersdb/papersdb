<?php ;

// $Id: add_author.php,v 1.3 2006/05/19 22:43:02 aicmltec Exp $

/**
 * \file
 *
 * \brief This is the form portion for adding/editing an author.
 *
 * The changes in the database actually are made in add_publication.php. This
 * is so when the author is added to the database the publication a user is
 * working in is then updated with that author available to them.
 *
 * If the user chooses the "add author to database" link while editing/adding
 * a publication, then it will be a pop-up and when submitted will return to
 * add_publication page with the fields restored and author added to the list.
 *
 * If the user chooses "add new publication" from the list_authors.php
 * page. Then the information will be sent to add_publication and the author
 * will be added to the database, the user will be given confirmation and the
 * option to return to the authors page or go the admin menu(index.php).
 *
 */

ini_set("include_path", ini_get("include_path") . ":..");

require('include/functions.php');

htmlHeader('Add Author');

/* Connecting, selecting database */
$link = connect_db();

/* Performing SQL query */
$interest_query = "SELECT * FROM interest";
$interest_result = mysql_query($interest_query)
    or die("Query failed : " . mysql_error());
$num_rows = mysql_num_rows($interest_result);
if($popup == "false"){
    include("header.php");
}

?>

<script language="JavaScript" type="text/JavaScript">

    function verify() {
	if (document.forms["authorForm"].elements["firstname"].value == "") {
        alert("Please enter a complete name for the new author.");
        return false;
	}
	if (document.forms["authorForm"].elements["lastname"].value == "") {
        alert("Please enter a complete name for the new author.");
        return false;
	}
	if ((document.forms["authorForm"].elements["firstname"].value).search(",")!=-1){
        alert("Please do not use commas in author's first name");
        return false;
	}
	if ((document.forms["authorForm"].elements["lastname"].value).search(",")!=-1){
        alert("Please do not use commas in author's last name");
        return false;
	}
	return true;
}

function dataKeep(num) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["authorForm"].elements.length; i++) {
        if ((document.forms["authorForm"].elements[i].value != "") && (document.forms["authorForm"].elements[i].value != null)) {
            if (info_counter > 0) {
                temp_qs = temp_qs + "&";
            }

            if (document.forms["authorForm"].elements[i].name == "interests[]") {
                interest_array = document.forms["authorForm"].elements['interests[]'];
                var interest_list = "";
                var interest_count = 0;

                for (j = 0; j < interest_array.length; j++) {
                    if (interest_array[j].selected == 1) {
                        if (interest_count > 0) {
                            interest_list = interest_list + "&";
                        }
                        interest_list = interest_list + "interests[" + j + "]=" + interest_array[j].value;
                        interest_count++;
                    }
                }

                temp_qs = temp_qs + interest_list;
            }
            else {
                temp_qs = temp_qs + document.forms["authorForm"].elements[i].name + "=" + document.forms["authorForm"].elements[i].value;
            }

            info_counter++;
        }
	}

	temp_qs = temp_qs.replace(" ", "%20");
	temp_qs = temp_qs.replace("\"", "?");
	location.replace("./add_author.php?<? echo $_SERVER['QUERY_STRING'] ?>&newInterests=" + num + "&" + temp_qs);
	//window.open("./add_author.php?<? echo $_SERVER['QUERY_STRING'] ?>&newInterests=" + num + "&" + temp_qs, "Add");
}
function closewindow() {window.close();}

function gotoAuthors() {
    location.replace("../list_author.php?type=view&admin=true&newauthor=true");
}

function resetAll() {
	location.replace("./add_author.php?<? echo $_SERVER['QUERY_STRING'] . "&newInterests=0" ?>");
	//window.open("./add_author.php?<? echo $_SERVER['QUERY_STRING'] ?>&newInterests=0", "Add");
}
</script>

<body>
<h3>Add Author <a href="../help.php" target="_blank" onClick="window.open('../help.php?helpcat=AddAuthor', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></h3>
<?
echo "<form name=\"authorForm\" action=\"./add_publication.php?".$_SERVER['QUERY_STRING']."\" method=\"POST\" enctype=\"application/x-www-form-urlencoded\" target=\"add_publication.php\" ";
if($popup != "false") echo "onsubmit=\"setTimeout('self.close()',0);\"";
echo ">";

?>
<table width="590" border="0" cellspacing="0" cellpadding="6">
    <tr>
<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>First Name: </b></font></td>
<td colspan="2" width="75%"><input type="text" name="firstname" size="50" maxlength="250" value="<? echo stripslashes($firstname); ?>"></td>
</tr>
<tr>
<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Last Name: </b></font></td>
<td colspan="2" width="75%"><input type="text" name="lastname" size="50" maxlength="250" value="<? echo stripslashes($lastname); ?>"></td>
</tr>
<tr>
<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Title: </b></font><a href="../help.php" target="_blank" onClick="window.open('../help.php?helpcat=Author Title', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>
<td colspan="2" width="75%"><input type="text" name="auth_title" size="50" maxlength="250" value="<? echo stripslashes($auth_title); ?>"></td>
</tr>
<tr>
<tr>
<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>E-mail: </b></font></td>
<td colspan="2" width="75%"><input type="text" name="email" size="50" maxlength="250" value="<? echo stripslashes($email); ?>"></td>
</tr>
<tr>
<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Organization: </b></font></td>
<td colspan="2" width="75%"><input type="text" name="organization" size="50" maxlength="250" value="<? echo stripslashes($organization); ?>"></td>
</tr>
<tr>
<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Webpage: </b></font></td>
<td colspan="2" width="75%"><input type="text" name="webpage" size="50" maxlength="250" value="<? echo stripslashes($webpage); ?>"></td>
</tr>
<tr>
<td width="25%">
    <font face="Arial, Helvetica, sans-serif" size="2"><b>Interest(s): </b></font><br>
    <font face="Arial, Helvetica, sans-serif" size="1"><a href="javascript:dataKeep(<? echo ($newInterests + 1) ?>)">[Add Interest]</a></font>
    </td>
    <td width="20%" align="left">
    <select name="interests[]" size="5" multiple>
    <?
    $counter = 0;
while ($interest_line = mysql_fetch_array($interest_result, MYSQL_ASSOC)) {
    echo "<option value=\"" . $interest_line[interest_id] . "\"" . "";
    if ($interests[$counter] != "") echo " selected";
    echo ">" . $interest_line[interest] . "</option>";
    $counter++;
}
?>
</select>
</td>
</tr>
<? for ($i = 0; $i < $newInterests; $i++) { ?>
    <tr>
        <td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Interest Name: </b></font></td>
        <td colspan="2" width="75%"><input type="text" name="newInterest[<? echo $i ?>]" size="50" maxlength="250" value="<? echo stripslashes($newInterest[$i]); ?>"></td>
        </tr>
        <? } ?>
        <tr>
        <td>

        </td>
        <td colspan="2" width="75%" align="left">
    <input type="SUBMIT" name="Submit" value="Add Author" class="text" onClick="return verify();">&nbsp;&nbsp;
<input type="RESET" name="Reset" value="Reset" class="text" onClick="resetAll();">&nbsp;&nbsp;
<input type="RESET" name="Cancel" value="Cancel" class="text" onClick="<? if($popup == "false")echo "history.back()"; else echo "closewindow();"; ?>">
<input type="hidden" name="newAuthorSubmitted" value="true">
                                                                                                                                                   <input type="hidden" name="numInterests" value="<? echo ($counter + 1) ?>">
                                                                                                                                                   <? if($popup == "false") { ?>
                                                                                                                                                                              <input type="hidden" name="fromauthorspage" value="true">
                                                                                                                                                                              <? } ?>
                                                                                                                                                                              </td>
                                                                                                                                                                              </tr>
                                                                                                                                                                              </table>
                                                                                                                                                                              </form>

                                                                                                                                                                              </body>
                                                                                                                                                                              </html>

                                                                                                                                                                              <?
                                                                                                                                                                                /* Free resultset */
                                                                                                                                                                              mysql_free_result($interest_result);

/* Closing connection */
disconnect_db($link);
?>
