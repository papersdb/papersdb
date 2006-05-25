<?php ;

// $Id: add_author.php,v 1.4 2006/05/25 01:36:18 aicmltec Exp $

/**
 * \file
 *
 * \brief This is the form portion for adding an author.
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

require('includes/functions.php');
require('includes/pdAuthInterests.php');

require_once 'HTML/QuickForm.php';
require_once 'HTML/Table.php';

htmlHeader('Add Author');

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
    if ((document.forms["authorForm"].elements["firstname"].value).search(",")
        !=-1){
        alert("Please do not use commas in author's first name");
        return false;
    }
    if ((document.forms["authorForm"].elements["lastname"].value).search(",")
        !=-1){
        alert("Please do not use commas in author's last name");
        return false;
    }
    return true;
}

function dataKeep(num) {
    var temp_qs = "";
    var info_counter = 0;

    for (i = 0; i < document.forms["authorForm"].elements.length; i++) {
        if ((document.forms["authorForm"].elements[i].value != "")
            && (document.forms["authorForm"].elements[i].value != null)) {
            if (info_counter > 0) {
                temp_qs = temp_qs + "&";
            }

            if (document.forms["authorForm"].elements[i].name == "interests[]") {
                interest_array
                    = document.forms["authorForm"].elements['interests[]'];
                var interest_list = "";
                var interest_count = 0;

                for (j = 0; j < interest_array.length; j++) {
                    if (interest_array[j].selected == 1) {
                        if (interest_count > 0) {
                            interest_list = interest_list + "&";
                        }
                        interest_list = interest_list
                            + "interests[" + j + "]="
                            + interest_array[j].value;
                        interest_count++;
                    }
                }

                temp_qs = temp_qs + interest_list;
            }
            else {
                temp_qs = temp_qs
                    + document.forms["authorForm"].elements[i].name + "="
                    + document.forms["authorForm"].elements[i].value;
            }

            info_counter++;
        }
    }

    temp_qs = temp_qs.replace(" ", "%20");
    temp_qs = temp_qs.replace("\"", "?");
    location.replace("./add_author.php?<? echo $_SERVER['QUERY_STRING'] ?>&newInterests=" + num + "&" + temp_qs);
}
function closewindow() {window.close();}

function gotoAuthors() {
    location.replace("../list_author.php?type=view&admin=true&newauthor=true");
}

function resetAll() {
    location.replace("./add_author.php?<? echo $_SERVER['QUERY_STRING'] . "&newInterests=0" ?>");
}
</script>

<?php ;

echo "<body>\n";
pageHeader();
navigationMenu();
echo "<div id='content'>\n";

/* Connecting, selecting database */
$db =& dbCreate();

if (isset($_GET['newInterests'])) {
    $newInterests =  $_GET['newInterests'] + 1;
}
else {
    $newInterests = 0;
}

/* Performing SQL query */
$interest_query = "SELECT * FROM interest";
$interest_result = mysql_query($interest_query)
    or die("Query failed : " . mysql_error());
$num_rows = mysql_num_rows($interest_result);


echo "<h3>Add Author "
. "<a href='../help.php' target='_blank' "
. "onClick=\"window.open('../help.php?helpcat=AddAuthor', 'Help', "
. "'width=400,height=400'); return false\">"
. "<img src='./question_mark_sm.JPG' border='0' alt='help'></a></h3>";

$formAttr = array();
if($_GET['popup'] != "false")
    $formAttr = array('onsubmit' => "setTimeout('self.close()',0);");

$form = new HTML_QuickForm('authorForm', 'post',
                           "./add_publication.php?" . $_SERVER['QUERY_STRING'],
                           "add_publication.php",
                           $formAttr);

$form->addElement('text', 'firstname', null,
                  array('size' => 50, 'maxlength' => 250));
$form->addElement('text', 'lastname', null,
                  array('size' => 50, 'maxlength' => 250));
$form->addElement('text', 'auth_title', null,
                  array('size' => 50, 'maxlength' => 250));
$form->addElement('text', 'email', null,
                  array('size' => 50, 'maxlength' => 250));
$form->addElement('text', 'organization', null,
                  array('size' => 50, 'maxlength' => 250));
$form->addElement('text', 'webpage', null,
                  array('size' => 50, 'maxlength' => 250));

$interests = new pdAuthInterests();
$interests->dbLoad($db);
assert('is_array($interests->list)');
foreach($interests->list as $intr) {
    $options[$intr->interest_id] = $intr->interest;
}

$form->addElement('select', 'interests', null, $options,
                      array('multiple' => 'multiple', 'size' => 4));

$form->addElement('submit', 'Submit', 'Add Author');
    $form->addElement('reset', 'Reset', 'Reset',
                      array('class' => 'text',
                          'onClick' => 'resetAll();'));

if ($_GET['popup'])
    $form->addElement('reset', 'Cancel', 'Cancel',
                      array('class' => 'text',
                            'onClick' => 'closeWindow();'));
else
    $form->addElement('reset', 'Cancel', 'Cancel',
                      array('class' => 'text',
                            'onClick' => 'history.back();'));

$form->addElement('hidden', 'newAuthorSubmitted', 'true');
$form->addElement('hidden', 'numInterests', $counter + 1);
$form->addElement('hidden', 'fromauthorspage', 'true');

for ($i = 0; $i < $newInterests; $i++) {
    $form->addElement('text', 'newInterest' . $i, null,
                      array('size' => 50, 'maxlength' => 250));
}

$renderer =& new HTML_QuickForm_Renderer_QuickHtml();
$form->accept($renderer);


$tableAttrs = array('width' => '100%',
                    'border' => '0',
                    'cellpadding' => '6',
                    'cellspacing' => '0');
$table = new HTML_Table($tableAttrs);
$table->setAutoGrow(true);

$table->addRow(array('First Name:', $renderer->elementToHtml('firstname')));
$table->addRow(array('Last Name:', $renderer->elementToHtml('lastname')));
$table->addRow(array('Title:'
                     . '<a href="../help.php" target="_blank" '
                     . 'onClick="window.open(\'../help.php?'
                     . 'helpcat=Author Title\', \'Help\','
                     . '\'width=400,height=400\'); return false">'
                     . '<img src="./question_mark_sm.JPG" border="0" '
                     . 'alt="help"></a>',
                     $renderer->elementToHtml('auth_title')));
$table->addRow(array('Email:', $renderer->elementToHtml('email')));
$table->addRow(array('Organization:',
                     $renderer->elementToHtml('organization')));
$table->addRow(array('Webpage:', $renderer->elementToHtml('webpage')));

$ref = '<br/><div id="small"><a href="javascript:dataKeep(' . $newInterests
    .')">[Add Interest]</a></div>';

$table->addRow(array('Interests:' . $ref,
                     $renderer->elementToHtml('interests')));

for ($i = 0; $i < $newInterests; $i++) {
$table->addRow(array('Interest Name:',
                     $renderer->elementToHtml('newInterest' . $i)));
}

echo $renderer->toHtml($table->toHtml()) . "</div>";

$db->close();
pageFooter();
echo "</body>\n</html>\n";

?>
