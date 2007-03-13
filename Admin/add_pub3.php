<?php ;

// $Id: add_pub3.php,v 1.16 2007/03/13 14:03:31 loyola Exp $

/**
 * This is the form portion for adding or editing author information.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'Admin/add_pub_base.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdAuthor.php';
require_once 'includes/pdExtraInfoList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub3 extends add_pub_base {
    var $author_id = null;

    function add_pub3() {
        session_start();
        $this->pub =& $_SESSION['pub'];

        parent::add_pub_base();

        if ($this->loginError) return;

        $options = array('cat_id');
        foreach ($options as $opt) {
            if (isset($_GET[$opt]) && ($_GET[$opt] != ''))
                $$opt = stripslashes($_GET[$opt]);
            else
                $$opt = null;
        }

        if (isset($cat_id))
            $this->pub->addCategory($this->db, $cat_id);
        else if (is_object($this->pub->category))
            $cat_id = $this->pub->category->cat_id;

        $this->addPubDisableMenuItems();

        $form = new HTML_QuickForm('add_pub3');

        $form->addElement('header', null, 'Category Information');

        // category
        $category_list = new pdCatList($this->db);
        $form->addElement(
            'select', 'cat_id',
            $this->helpTooltip('Category', 'categoryHelp') . ':',
            array('' => '--- Please Select a Category ---')
            + $category_list->list,
            array('onchange' => 'dataKeep();'));

        if ($cat_id > 0) {
            if ($this->pub->category->info != null) {
                foreach (array_values($this->pub->category->info) as $name) {
                    $element = preg_replace("/\s+/", '', $name);
                    $form->addElement('text', $element, ucfirst($name) . ':',
                                      array('size' => 50, 'maxlength' => 250));
                }
            }
        }

        $form->addElement('header', 'other_info', 'Other information', null);

        $form->addElement('textarea', 'extra_info',
                          $this->helpTooltip('Extra Information',
                                             'extraInfoHelp') . ':',
                          array('cols' => 60, 'rows' => 5));

        $extra_info = new pdExtraInfoList($this->db);

        if (count($extra_info) > 0) {
            unset($options);
            foreach ($extra_info->list as $info) {
                if ($this->pub != null) {
                    // only make it an option if not already assigned for this
                    // pub
                    if (strpos($this->pub->extra_info, $info) === false) {
                        $options[$info] = $info;
                    }
                }
                else {
                    $options[$info] = $info;
                }
            }
            $extraInfoSelect =& $form->addElement(
                'advmultiselect', 'extra_info_from_list', null, $options,
                array('class' => 'pool', 'style' => 'width:150px;height:180px;'),
                SORT_ASC);

            $extraInfoSelect->setLabel(
                array('Commonly Used:', 'Selected', 'Available'));

            $extraInfoSelect->setButtonAttributes('add',
                                             array('value' => 'Add',
                                                   'class' => 'inputCommand'));
            $extraInfoSelect->setButtonAttributes('remove',
                                             array('value' => 'Remove',
                                                   'class' => 'inputCommand'));
            $extraInfoSelect->setButtonAttributes('moveup',
                                             array('class' => 'inputCommand'));
            $extraInfoSelect->setButtonAttributes('movedown',
                                             array('class' => 'inputCommand'));

            // template for a dual multi-select element shape
            $extraInfoSelect->setElementTemplate($this->templateGet());
        }

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'prev_step', '<< Previous Step');
        $buttons[] = HTML_QuickForm::createElement(
            'button', 'cancel', 'Cancel',
            array('onclick' => "location.href='" . $url . "';"));
        $buttons[] = HTML_QuickForm::createElement(
            'reset', 'reset', 'Reset');
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'next_step', 'Next Step >>');

        if ($this->pub->pub_id != '')
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'finish', 'Finish');

        $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

        $this->form =& $form;

        if ($form->validate()) {
            $this->processForm();
        }
        else {
            $this->renderForm();
        }
        $this->db->close();
    }

    function renderForm() {
        $form =& $this->form;

        $defaults = $_GET;

        echo '<h3>Adding Following Publication</h3>'
            . $this->pub->getCitationHtml('..', false) . '<p/>'
            . add_pub_base::similarPubsHtml();

        if (is_object($this->pub->category))
            $defaults['cat_id'] = $this->pub->category->cat_id;

        $defaults['extra_info'] = $this->pub->extra_info;

        // assign category info items
        if (count($this->pub->info) > 0)
            foreach (array_values($this->pub->category->info) as $name) {
                $element = preg_replace("/\s+/", '', $name);
                $defaults[$element] = $this->pub->info[$name];
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
        $this->renderer =& $renderer;
        $this->javascript();
    }

    function processForm() {
        $form =& $this->form;

        $values = $form->exportValues();

        if ($values['cat_id'] > 0)
            $this->pub->addCategory($this->db, $values['cat_id']);

        if ($this->pub->category->info != null) {
            foreach (array_values($this->pub->category->info) as $name) {
                $element = preg_replace("/\s+/", '', $name);
                if (isset($values[$element]))
                    $this->pub->info[$name] = $values[$element];
            }
        }

        $extra_info_arr = array();
        if ($values['extra_info'] != '')
            $extra_info_arr = array_merge($extra_info_arr,
                                          array($values['extra_info']));

        if (isset($values['extra_info_from_list'])
            && (count($values['extra_info_from_list']) > 0))
            $extra_info_arr = array_merge($extra_info_arr,
                                          $values['extra_info_from_list']);

        $this->pub->extraInfoSet($extra_info_arr);

        if (isset($values['prev_step']))
            header('Location: add_pub2.php');
        else if (isset($values['finish']))
            header('Location: add_pub_submit.php');
        else
            header('Location: add_pub4.php');
    }

    function javascript() {
        $this->js = <<<JS_END
            <script language="JavaScript" type="text/JavaScript">

        var categoryHelp=
            "Category describes the type of document that you are submitting "
            + "to the site. For examplethis could be a journal entry, a book "
            + "chapter, etc.<br/><br/>"
            + "Please use the drop down menu to select an appropriate "
            + "category to classify your paper. If you cannot find an "
            + "appropriate category you can select 'Add New Category' from "
            + "the drop down menu and you will be asked for the new category "
            + "information on a subsequent page.<br/><br/>";

        var paperAtt =
            "Attach a postscript, PDF, or other version of the publication.";

        var otherAtt =
            "In addition to the primary paper attachment, attach additional "
            + "files to this publication.";

        var extraInfoHelp=
            "Specify auxiliary information, to help classify this "
            + "publication. Eg, &quot;with student&quot; or &quot;best "
            + "paper&quot;, etc. Note that, by default, this information will "
            + "NOT be shown when this document is presented. Separate using "
            + "semiolons(;).";

        var extLinks=
            "Used to link this publication to an outside source such as a "
            + "website or a publication that is not in the current database.";

        var pubLinks =
            "Used to link other publications in the database to this "
            + "publication.";

        function dataKeep() {
            var form =  document.forms["add_pub3"];
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < form.elements.length; i++) {
                var element = form.elements[i];

                if ((element.type != "submit") && (element.type != "reset")
                    && (element.type != "button")
                    && (element.value != "") && (element.value != null)) {

                    if (element.type == "checkbox") {
                        if (element.checked) {
                            qsArray.push(element.name + "=" + element.value);
                        }
                    } else if (element.type != "hidden") {
                        qsArray.push(form.elements[i].name + "="
                                     + form.elements[i].value);
                    }
                }
            }

            if (qsArray.length > 0) {
                qsString = qsArray.join("&");
                qsString.replace(" ", "%20");
                qsString.replace("\"", "?");
            }

            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + qsString;
        }

        </script>
JS_END;
    }

    function templateGet() {
        $template = <<<END
{javascript}
<table{class}>
<tr>
  <th>&nbsp;</th>
  <!-- BEGIN label_2 --><th>{label_2}</th><!-- END label_2 -->
  <th>&nbsp;</th>
  <!-- BEGIN label_3 --><th>{label_3}</th><!-- END label_3 -->
</tr>
<tr>
  <td valign="middle">{moveup}<br/>{movedown}<br/>{remove}</td>
  <td valign="top">{selected}</td>
  <td valign="middle">{add}</td>
  <td valign="top">{unselected}</td>
</tr>
</table>
{javascript}
END;
       return $template;
    }
}

$page = new add_pub3();
echo $page->toHtml();


?>
