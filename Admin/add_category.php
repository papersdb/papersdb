<?php ;

// $Id: add_category.php,v 1.7 2006/06/08 22:44:42 aicmltec Exp $

/**
 * \file
 *
 * \brief This is the form portion of adding/editing a category.
 *
 * The changes in the database actually are made in add_publication.php.  This
 * is so when the category is added to the database the publication a user is
 * working in is then updated with that category available to them.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pageConfig.php';
require_once 'includes/pdInfoList.php';

$db =& dbCreate();

$form = new HTML_QuickForm('catForm', 'post',
                           './add_publication.php?'.$_SERVER['QUERY_STRING'],
                           'add_publication.php',
                           array('onsubmit'
                                 => 'setTimeout(\'self.close()\',0);'));
$form->addElement('text', 'catname', null,
                  array('size' => 50, 'maxlength' => 250));
//$form->addRule('catname', 'category name cannot be empty',
//               'required', null, 'client');

// info list
$info_list = new pdInfoList();
$info_list->dbLoad($db);
assert('is_array($info_list->list)');
foreach ($info_list->list as $info) {
    $form->addElement('advcheckbox', 'info[' . $info->info_id . ']', null,
                      $info->name, null, array('', $info->name));
}

if (isset($_GET['newFields']) && ($_GET['newFields'] != ''))
    $newFields = intval($_GET['newFields']);
else
    $newFields = 0;

for ($i = 0; $i < $newFields; $i++) {
    $form->addElement('text', 'new_field[' . $i . ']', null,
                      array('size' => 50, 'maxlength' => 250));
}

$form->addElement('button', 'add_field', 'Add Field',
                  array('onClick' => 'dataKeep(' . ($newFields + 1) . ');'));
$form->addElement('submit', 'Submit', 'Add Category',
                  array('onclick' => 'return verify();'));
$form->addElement('reset', 'Reset', 'Reset',
                  array('onclick' => 'resetAll();'));
$form->addElement('submit', 'Cancel', 'Cancel',
                  array('onClick' => 'closewindow();'));
$form->addElement('hidden', 'newCatSubmitted', 'true');

$renderer =& new HTML_QuickForm_Renderer_QuickHtml();
$form->accept($renderer);

$table = new HTML_Table(array('width' => '600',
                              'border' => '0',
                              'cellpadding' => '6',
                              'cellspacing' => '0'));
$table->setAutoGrow(true);

$table->addRow(array('Category Name:', $renderer->elementToHtml('catname')));
$table->updateCellAttributes($table->getRowCount() - 1, 1,
                             array('colspan' => 2));
$countDiv2 = intval((count($info_list->list) + 1) /2);

// assign info to the 2 columns
$count = 0;
foreach ($info_list->list as $info) {
    if ($count < $countDiv2)
        $col1[] = 'info[' . $info->info_id . ']';
    else
        $col2[] = 'info[' . $info->info_id . ']';
    $count++;
}

// display info in table
for ($i = 0; $i < $countDiv2; $i++) {
    $cell1 = '';
    if ($i == 0)
        $cell1 = 'Related Field(s):<br/>'
            . $renderer->elementToHtml('add_field');
    $cell2 = $renderer->elementToHtml($col1[$i]);
    $cell3 = '';
    if ($countDiv2 + $i < count($info_list->list))
        $cell3 = $renderer->elementToHtml($col2[$i]);
    $table->addRow(array($cell1, $cell2, $cell3));
}

for ($i = 0; $i < $newFields; $i++) {
    $table->addRow(array('Field Name:',
                         $renderer->elementToHtml('new_field[' . $i . ']')));
    $table->updateCellAttributes($table->getRowCount() - 1, 1,
                                 array('colspan' => 2));
}

$table->addRow(array('',
                     $renderer->elementToHtml('Submit')
                     . '&nbsp;' . $renderer->elementToHtml('Reset')
                     . '&nbsp;' . $renderer->elementToHtml('Cancel')),
               array('', 'colspan' => 2));

$table->updateCellAttributes($table->getRowCount() - 1, 1,
                             array('colspan' => 2));
$table->updateColAttributes(0, array('id' => 'emph', 'width' => '25%'));
$table->updateCellAttributes(1, 0, array('rowspan' => 2));


htmlHeader('add_venue', 'Add Category');

echo <<< JS_END
<script language="JavaScript" type="text/JavaScript">

    var addCategoryPageHelp=
    "This window is used to add a new category of papers to the database. "
    + "The category should be used to describe the type of paper being "
    + "submitted. Examples of paper types include: journal entries, book "
    + "chapters, etc. <br/><br/>"
    + "When you add a category you can also select related field(s) by "
    + "clicking on the selection boxes. If you do not see the appropriate "
    + "related field(s) you can add field(s) by clicking on the Add Field "
    + "button to bring up additional fields where you can type in the name "
    + "of the related field you wish to add.";

function verify() {
    if (document.forms["catForm"].elements["catname"].value == "") {
        alert("Please enter a name for the new category.");
        return false;
    }
}

function dataKeep(num) {
    var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["catForm"].elements.length; i++) {
        var element = document.forms["catForm"].elements[i];
		if ((element.value != "") && (element.value != null)) {
			if (info_counter > 0) {
				temp_qs = temp_qs + "&";
			}

			if (element.type == 'checkbox') {
				if (element.checked != false) {
					temp_qs = temp_qs + element.name + "=" + element.value;
				}
			}
			else {
				temp_qs = temp_qs + element.name + "=" + element.value;
			}

			info_counter++;
		}
	}

	temp_qs = temp_qs.replace(" ", "%20");
	window.open("./add_category.php?{$_SERVER['QUERY_STRING']}&newFields="
                + num + "&" + temp_qs, "Add");
}
function closewindow(){ window.close();}
function resetAll() {
        window.open("./add_category.php?{$_SERVER['QUERY_STRING']}&newFields=0", "Add");
}

</script>
JS_END;
//$_SERVER['QUERY_STRING']&newFields="

echo '<body>';

if (!$logged_in) {
    loginErrorMessage();
}

echo '<h3>' . helpTooltip('Add Category', 'addCategoryPageHelp') . '</h3>';
echo $renderer->toHtml(($table->toHtml()));
echo '<script language="JavaScript" type="text/javascript" src="../wz_tooltip.js"></script>';
echo "</body>\n</html>\n";
$db->close();

?>
