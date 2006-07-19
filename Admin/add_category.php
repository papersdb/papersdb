<?php ;

// $Id: add_category.php,v 1.15 2006/07/19 23:49:12 aicmltec Exp $

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

        $form->addElement('header', null,
                          $this->helpTooltip('Add Category',
                                             'addCategoryPageHelp'));

        $form->addElement('text', 'catname', 'Category Name:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('catname', 'category name cannot be empty',
                       'required', null, 'client');

        // info list
        $label = 'Related Fields:';
        $info_list = new pdInfoList($db);
        foreach ($info_list->list as $info_id => $name) {
            $form->addElement('advcheckbox', 'info[' . $info_id . ']',
                              $label, $name, null, array('', $name));
            $label = '';
        }

        if (isset($_GET['numNewFields']) && ($_GET['numNewFields'] != ''))
            $newFields = intval($_GET['numNewFields']);
        else if (isset($_POST['numNewFields'])
                 && ($_POST['numNewFields'] != ''))
            $newFields = intval($_POST['numNewFields']);
        else
            $newFields = 0;

        for ($i = 0; $i < $newFields; $i++) {
            $form->addElement('text', 'new_fields[' . $i . ']',
                              'New field ' . ($i + 1) . ':',
                              array('size' => 50, 'maxlength' => 250));
        }

        $form->addElement('hidden', 'numNewFields', $newFields);

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'submit', 'submit', 'Submit New Category'),
                HTML_QuickForm::createElement(
                    'reset', 'reset', 'Reset'),
                HTML_QuickForm::createElement(
                    'button', 'add_field', 'Add Related Field',
                    array('onClick' => 'dataKeep('
                          . ($newFields + 1) . ');'))
                ),
            'submit_group', null, '&nbsp;');

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

            $renderer =& $form->defaultRenderer();

            $renderer->setFormTemplate(
                '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
                . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
            $renderer->setHeaderTemplate(
                '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
                . 'align="left" colspan="2"><b>{header}</b></td></tr>');

            $renderer->setElementTemplate(
                '<tr><td><b>{label}</b></td><td>{element}'
                . '<br/><span style="font-size:10px;">seperate using semi-colon (;)</span>'
            . '</td></tr>',
                'keywords');

            $form->accept($renderer);
            $this->form =& $form;
            $this->renderer =& $renderer;
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

session_start();
$logged_in = check_login();
$page = new add_category();
echo $page->toHtml();

?>
