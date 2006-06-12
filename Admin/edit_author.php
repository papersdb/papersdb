<?php ;

/**
 * \file
 *
 * \brief This page is for editing an author.
 *
 * It is passed an author_id, and then fills the selected fields, and then
 * replaces the information in the database.
 */

ini_set("include_path", ini_get("include_path") . ":.:..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthor.php';

/**
 * Renders the whole page.
 */
class edit_author extends pdHtmlPage {
    function edit_author() {
        global $logged_in;

        parent::pdHtmlPage('edit_author');

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }

        if ($_GET['editAuthorSubmitted'] == 'true') {
            echo "<script language='javascript'>setTimeout(\"top.location.href = './'\",5000)</script>";
        }

        $db =& dbCreate();
        $author = new pdAuthor();

        // Are we editing data in the db?
        if ($_GET['editAuthorSubmitted'] == "true") {
            $author->author_id = $_POST['author_id'];
            $author->name = $_POST['lastname'] . ", " . $_POST['firstname'];
            $author->title = $_POST['auth_title'];
            $author->email = $_POST['email'];
            $author->organization = $_POST['organization'];
            $author->webpage = $_POST['webpage'];

            foreach ($_POST['interests'] as $interest) {
                $author->interest[] = $interest;
            }
            $author->dbSave($db);

            $this->contentPre
                .= '<body>You have successfully made changes to the author'
                . $author->name. '.';
            $db->close;
            return;
        }

        if (!isset($_GET['author_id']) || ($_GET['author_id'] == '')) {
            $this->contentPre .= 'No author id defined';
            $this->pageError = true;
            return;
        }

        $author->dbLoad($db, $_GET['author_id']);
    }

    function javascript() {
        $this->js = <<<JS_END
            <script language="JavaScript" type="text/JavaScript">

            function verify() {
            var elements = document.forms["authorForm"].elements;
            if (elements["firstname"].value == "") {
                alert("Please enter a complete name for the new author.");
                return false;
            }
            if (elements["lastname"].value == "") {
                alert("Please enter a complete name for the new author.");
                return false;
            }
            if ((elements["firstname"].value).search(",")!=-1) {
                alert("Please do not use commas in the author's first name");
                return false;
            }
            if ((elements["lastname"].value).search(",")!=-1) {
                alert("Please do not use commas in the author's last name");
                return false;
            }
            return true;
        }

        function dataKeep(num) {
            var temp_qs = "";
            var info_counter = 0;

            for (i = 0; i < document.forms["authorForm"].elements.length; i++) {
                var element = document.forms["authorForm"].elements;
                if ((element[i].value != "") && (element[i].value != null)) {
                    if (info_counter > 0) {
                        temp_qs += "&";
                    }

                    if (element[i].name == "interests[]") {
                        var interest_array = element;
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

                        temp_qs += interest_list;
                    }
                    else {
                        temp_qs += element[i].name + "=" + element[i].value;
                    }

                    info_counter++;
                }
            }

            temp_qs = temp_qs.replace(" ", "%20");
            location.replace("./edit_author.php?{$_SERVER['QUERY_STRING']}&newInterests=" + num + "&" + temp_qs);
        }

        function resetAll() {
            location.replace("./edit_author.php?<? echo $_SERVER['QUERY_STRING'] . "&newInterests=0" ?>");
        }
        </script>
JS_END;
    }

    function formCreate() {
        $form = new HTML_QuickForm('authorForm', 'post',
                                   '/edit_author.php?editAuthorSubmitted=true',
                                   '_self');

        $form->addElement('text', 'firstname', null,
                  array('size' => '50', 'maxlength' => '250'));
        $form->addElement('text', 'lastname', null,
                              array('size' => '50', 'maxlength' => '250'));
        $form->addElement('text', 'auth_title', null,
                              array('size' => '50', 'maxlength' => '250'));
        $form->addElement('text', 'email', null,
                              array('size' => '50', 'maxlength' => '250'));
        $form->addElement('text', 'organization', null,
                              array('size' => '50', 'maxlength' => '250'));
        $form->addElement('text', 'webpage', null,
                              array('size' => '50', 'maxlength' => '250'));
        $form->addElement('text', 'newInterest[<? echo $i ?>]', null,
                              array('size' => '50', 'maxlength' => '250'));

$form->addElement('RESET', 'Reset" value="Reset" class="text" onClick="resetAll();">
$form->addElement('hidden', 'author_id" value="<?php echo $author_id ?>">
$form->addElement('hidden', 'numInterests" value="<? echo ($counter + 1) ?>">
    }
}

<form name="authorForm" action="./edit_author.php?editAuthorSubmitted=true" method="POST" enctype="application/x-www-form-urlencoded" onsubmit="setTimeout('self.close()',0);">
	<table width="600" border="0" cellspacing="0" cellpadding="6">
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
<td colspan="2" width="75%"><input type="text" name="auth_title" size="50" maxlength="250" value="<? echo stripslashes($author_title); ?>"></td>
</tr>
<tr>
<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Email: </b></font></td>
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
    if (isset($interests[$counter]) && $interests[$counter] != "")
        echo " selected";
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
        <a href="../help.php" target="_blank" onClick="window.open('../help.php?helpcat=Edit Author', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a>
		</td>
		<td colspan="2" width="75%" align="left">
    <input type="SUBMIT" name="Submit" value="Edit Author" class="text" onClick="return verify();">&nbsp;&nbsp;<input type="RESET" name="Reset" value="Reset" class="text" onClick="resetAll();">
                                                                                                             <input type="hidden" name="author_id" value="<?php echo $author_id ?>">
                                                                                                             <input type="hidden" name="numInterests" value="<? echo ($counter + 1) ?>">
                                                                                                             </td>
    </tr>
	</table>
    </form>
    <? back_button(); ?>
    </body>
    </html>

    <?
      /* Free resultset */
    mysql_free_result($author_result);
mysql_free_result($int_result);
mysql_free_result($interest_result);

/* Closing connection */
disconnect_db($link);
?>
