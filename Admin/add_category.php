<?php ;

// $Id: add_category.php,v 1.13 2006/06/21 05:34:22 aicmltec Exp $

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

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdInfoList.php';
require_once 'includes/pdCategory.php';

/**
 * Renders the whole page.
 */
class add_category extends pdHtmlPage {
    function add_category() {
        global $logged_in;

        parent::pdHtmlPage('add_category');

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }

        $db =& dbCreate();
        $category = new pdCategory();

        if (isset($_GET['cat_id']) && ($_GET['cat_id'] != '')) {
            $this->cat_id = intval($_GET['cat_id']);
            $result = $category->dbLoad($db, $this->cat_id);

            if (!$result) {
                $db->close();
                $this->pageError = true;
                return;
            }
        }

        $form = new HTML_QuickForm('catForm');

        $form->addElement('text', 'catname', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('catname', 'category name cannot be empty',
                       'required', null, 'client');

        // info list
        $info_list = new pdInfoList($db);
        foreach ($info_list->list as $info_id => $name) {
            $form->addElement('advcheckbox', 'info[' . $info_id . ']',
                              null, $name, null, array('', $name));
        }

        if (isset($_GET['numNewFields']) && ($_GET['numNewFields'] != ''))
            $newFields = intval($_GET['numNewFields']);
        else if (isset($_POST['numNewFields'])
                 && ($_POST['numNewFields'] != ''))
            $newFields = intval($_POST['numNewFields']);
        else
            $newFields = 0;

        for ($i = 0; $i < $newFields; $i++) {
            $form->addElement('text', 'new_fields[' . $i . ']', null,
                              array('size' => 50, 'maxlength' => 250));
        }

        $form->addElement('button', 'add_field', 'Add Field',
                          array('onClick' => 'dataKeep('
                                . ($newFields + 1) . ');'));
        $form->addElement('hidden', 'numNewFields', $newFields);
        $form->addElement('submit', 'submit', 'Add Category');
        $form->addElement('reset', 'reset', 'Reset');

        if ($form->validate()) {
            $values = $form->exportValues();

            $category->category = $values['catname'];

            foreach (array_merge($values['info'], $values['new_fields'])
                     as $infoname) {
                if ($infoname == '') continue;

                $obj = new stdClass;
                $obj->name = $infoname;
                $category->info[] = $obj;
            }
            $category->dbSave($db);

            $this->contentPre .= 'Category "' . $category->category
                . '" succesfully added to the database.'
                . '<p/>'
                . '<a href="' . $_SERVER['PHP_SELF'] . '">'
                . 'Add another new category</a>';
        }
        else {
            $form->setDefaults($_GET);
            $defaults['catname'] = $category->category;

            if (isset($category->info) && (count($category->info) > 0)) {
                foreach ($category->info as $info_id => $name) {
                    $defaults['info['.$info_id.']'] = $name;
                }
                $form->setDefaults($defaults);
            }

            $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
            $form->accept($renderer);

            $table = new HTML_Table(array('width' => '600',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));
            $table->setAutoGrow(true);

            $table->addRow(array('Category Name:',
                                 $renderer->elementToHtml('catname')));
            $table->updateCellAttributes($table->getRowCount() - 1, 1,
                                         array('colspan' => 2));
            $countDiv2 = intval((count($info_list->list) + 1) /2);

            // assign info to the 2 columns
            $count = 0;
            foreach ($info_list->list as $info_id => $name) {
                if ($count < $countDiv2)
                    $col1[] = 'info[' . $info_id . ']';
                else
                    $col2[] = 'info[' . $info_id . ']';
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
                                     $renderer->elementToHtml(
                                         'new_fields['.$i.']')));
                $table->updateCellAttributes($table->getRowCount() - 1, 1,
                                             array('colspan' => 2));
            }

            $table->addRow(array('',
                                 $renderer->elementToHtml('submit')
                                 . '&nbsp;'.$renderer->elementToHtml('reset')));

            $table->updateCellAttributes($table->getRowCount() - 1, 1,
                                         array('colspan' => 2));
            $table->updateColAttributes(0, array('id' => 'emph',
                                                 'width' => '25%'));
            $table->updateCellAttributes(1, 0, array('rowspan' => 2));


            $this->contentPre .= '<h3>'
                . $this->helpTooltip('Add Category', 'addCategoryPageHelp')
                . '</h3>';

            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->table =& $table;
            $this->javascript();
        }
        $db->close();
    }

    function javascript() {
        $this->js = <<< JS_END
            <script language="JavaScript" type="text/JavaScript">

            var addCategoryPageHelp =
            "This window is used to add a new category of papers to the "
            + "database. The category should be used to describe the type of "
            + "paper being submitted. Examples of paper types include: "
            + "journal entries, book chapters, etc. <br/><br/> "
            + "When you add a category you can also select related field(s) "
            + "by clicking on the selection boxes. If you do not see the "
            + "appropriate related field(s) you can add field(s) by clicking "
            + "on the Add Field button to bring up additional fields where "
            + "you can type in the name of the related field you wish to add.";

        function dataKeep(num) {
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < document.forms["catForm"].elements.length; i++) {
                var element = document.forms["catForm"].elements[i];

                if ((element.type != "submit") && (element.type != "reset")
                    && (element.type != "button") && (element.name != "")
                    && (element.value != "") && (element.value != null)) {

                    if (element.type == 'checkbox') {
                        if (element.checked) {
                            qsArray.push(element.name + "=" + element.value);
                        }
                    }
                    else if (element.name == 'numNewFields') {
                        qsArray.push(element.name + "=" + num);
                    }
                    else {
                        qsArray.push(element.name + "=" + element.value);
                    }
                }
            }

            if (qsArray.length > 0) {
                qsString = qsArray.join("&");
                qsString.replace(" ", "%20");
            }

            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + qsString;
        }

        </script>
JS_END;
    }
}

$page = new add_category();
echo $page->toHtml();

?>
