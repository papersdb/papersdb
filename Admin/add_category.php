<?php ;

// $Id: add_category.php,v 1.32 2007/03/14 02:58:47 loyola Exp $

/**
 * Creates a form for adding or editing a category.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdInfoList.php';
require_once 'includes/pdCategory.php';

/**
 * Allows the user to add a new category or edit a category in the
 * database.
 *
 * If the "cat_id" variable is part of the query string, then the user wishes
 * to edit the corresponding category. If not in the query string then allows
 * the user to add a new category.
 *
 * @package PapersDB
 */
class add_category extends pdHtmlPage {
    var $cat_id;
    var $numNewFields;

    function add_category() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('add_category');

        $this->loadHttpVars();

        if ($this->loginError) return;

        $category = new pdCategory();

        if (isset($this->cat_id)) {
            if (!is_numeric($this->cat_id)) {
                $this->pageError = true;
                return;
            }

            $result = $category->dbLoad($this->db, $this->cat_id);

            if (!$result) {
                $this->pageError = true;
                return;
            }
        }

        if (isset($this->numNewFields)) {
            if (!is_numeric($this->numNewFields)) {
                $this->pageError = true;
                return;
            }
        }
        else {
            $this->numNewFields = 0;
        }

        if ($category->cat_id != '')
            $label = 'Edit Category';
        else
            $label = 'Add Category';

        $this->pageTitle = $label;

        $form = new HTML_QuickForm('catForm');

        $form->addElement('header', null,
                          $this->helpTooltip($label,
                                             'addCategoryPageHelp',
                                             'helpHeading'));

        $form->addElement('text', 'catname', 'Category Name:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('catname', 'category name cannot be empty',
                       'required', null, 'client');

        // info list
        $label = 'Related Fields:';
        $info_list = new pdInfoList($this->db);
        foreach ($info_list->list as $info_id => $name) {
            $form->addElement('advcheckbox', 'info[' . $info_id . ']',
                              $label, $name, null, array('', $name));
            $label = '';
        }

        for ($i = 0; $i < $this->numNewFields; $i++) {
            $form->addElement('text', 'new_fields[' . $i . ']',
                              'New field ' . ($i + 1) . ':',
                              array('size' => 50, 'maxlength' => 250));
        }

        $form->addElement('hidden', 'numNewFields', $this->numNewFields);

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'reset', 'reset', 'Reset'),
                HTML_QuickForm::createElement(
                    'button', 'add_field', 'Add Related Field',
                    array('onClick' => 'dataKeep('
                          . ($this->numNewFields + 1) . ');')),
                HTML_QuickForm::createElement(
                    'submit', 'submit', 'Submit New Category')
                ),
            'submit_group', null, '&nbsp;');

        if ($form->validate()) {
            $values = $form->exportValues();

            $category->category = $values['catname'];

            if (isset($values['new_fields']))
                $values['info'] = array_merge($values['info'],
                                              $values['new_fields']);

            foreach ($values['info'] as $infoname) {
                if ($infoname == '') continue;

                $obj = new stdClass;
                $obj->name = $infoname;
                $category->info[] = $obj;
            }
            $category->dbSave($this->db);

            echo 'Category "' . $category->category
                . '" succesfully added to the database.'
                . '<p/>'
                . '<a href="' . $_SERVER['PHP_SELF'] . '">'
                . 'Add another new category</a>';
        }
        else {
            foreach (array_keys(get_class_vars(get_class($this))) as $member) {
                $defaults[$member] = $this->$member;
            }

            $defaults['catname'] = $category->category;

            if (isset($category->info) && (count($category->info) > 0)) {
                foreach ($category->info as $info_id => $name) {
                    $defaults['info['.$info_id.']'] = $name;
                }
            }

            $form->setDefaults($defaults);
            $renderer =& $form->defaultRenderer();

            $renderer->setFormTemplate(
                '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
                . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
            $renderer->setHeaderTemplate(
                '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
                . 'align="left" colspan="2"><b>{header}</b></td></tr>');

            $form->accept($renderer);
            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->javascript();
        }
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
