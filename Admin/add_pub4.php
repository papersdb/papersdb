<?php ;

// $Id: add_pub4.php,v 1.1 2006/09/06 22:36:58 aicmltec Exp $

/**
 * \file
 *
 * \brief This is the form portion for adding or editing author information.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdAuthor.php';
require_once 'includes/pdExtraInfoList.php';

/**
 * Renders the whole page.
 */
class add_pub4 extends pdHtmlPage {
    function add_pub4() {
        global $access_level;

        parent::pdHtmlPage('add_publication', 'Select Authors',
                           'Admin/add_pub4.php',
                           PD_NAV_MENU_LEVEL_ADMIN);

        if ($access_level <= 1) {
            $this->loginError = true;
            return;
        }

        if ($_SESSION['state'] != 'pub_add') {
            $this->pageError = true;
            return;
        }

        $this->navMenuItemEnable('add_publication', 0);
        $this->navMenuItemDisplay('add_author', 0);
        $this->navMenuItemDisplay('add_category', 0);
        $this->navMenuItemDisplay('add_venue', 0);

        $this->db =& dbCreate();
        $db =& $this->db;
        $pub =& $_SESSION['pub'];

        $options = array('remove_paper', 'num_att', 'remove_att');
        foreach ($options as $opt) {
            if (isset($_GET[$opt]) && ($_GET[$opt] != ''))
                $$opt = $_GET[$opt];
            else if (isset($_POST[$opt]) && ($_POST[$opt] != ''))
                $$opt = $_POST[$opt];
            else if ($opt == 'num_att')
                $$opt = 0;
            else
                $$opt = null;
        }

        //$this->contentPre .= '<pre>' . print_r($this, true) . '</pre>';

        $form = new HTML_QuickForm('add_pub4');

        $form->addElement('header', null, 'Attachments');

        if (isset($_SESSION['paper']))
            $filename = $_SESSION['paper']['name'];
        else if ($pub->paper != 'No Paper')
            $filename = $pub->paper;

        if (($remove_paper != 'yes') && ($filename != '')) {
            $form->addGroup(
                array(
                HTML_QuickForm::createElement(
                    'static', 'assigned_paper', null, $filename),
                HTML_QuickForm::createElement(
                    'button', 'remove_paper', 'Remove Paper',
                    array('onclick' => 'removePaper();'))
                    ),
                null, 'Current Paper:', '&nbsp', false);
        }
        else {
            unset($_SESSION['paper']);
            $form->addElement('file', 'uploadpaper', 'Paper:',
                              array('size' => 45));
        }

        $form->addElement('hidden', 'num_att', $num_att);

        // remove the attachments first
        if (count($remove_att) > 0) {
            foreach (array_keys($remove_att) as $key) {
                if (isset($_SESSION['attachments'][$key]))
                    unset($_SESSION['attachments'][$key]);
            }

            // reindex
            $_SESSION['attachments'] = array_values($_SESSION['attachments']);
        }

        for ($i = 0; $i < $num_att; $i++) {
            unset($filename);

            if (isset($_SESSION['attachments'][$i]))
                $filename = $_SESSION['attachments'][$i]['name'];
            else if ($pub->additional_info[$i] != '')
                $filename = $pub->additional_info[$i];

            if ($filename != '') {
                $form->addGroup(
                    array(
                        HTML_QuickForm::createElement(
                            'static', 'assigned_att' . $i, null, $filename),
                        HTML_QuickForm::createElement(
                            'button', 'remove_att' . $i, 'Remove Attachment',
                            array('onclick'
                                  => 'removeAttachment(' . $i . ');'))
                        ),
                    null, 'Attachment ' . ($i + 1) . ':', '&nbsp', false);
            }
            else {
                unset($_SESSION['attachments'][$i]);
                $form->addElement('file', 'attachment' . $i,
                                  'Attachment ' . ($i + 1) . ':',
                                  array('size' => 45));
            }
        }

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'submit', 'prev_step', '<< Previous Step'),
                HTML_QuickForm::createElement(
                    'button', 'add_att' . $i, 'Add Attachment',
                    array('onclick' =>
                          'dataKeep(' . ($num_att + 1) . ');')),
                HTML_QuickForm::createElement(
                    'submit', 'next_step', 'Next Step >>')),
            'buttons', '', '&nbsp', false);

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
        assert('isset($_SESSION["pub"])');

        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        $defaults = array();

        $form->setDefaults($defaults);

        $this->contentPre .= '<h3>Publication Information</h3>'
            . $pub->getCitationHtml('', false) . '<p/>';

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
        assert('isset($_SESSION["pub"])');

        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        $values = $form->exportValues();

        $element =& $form->getElement('uploadpaper');
        if (!isset($element->message) && ($element->isUploadedFile())) {
            $_SESSION['paper'] =  $element->getValue();
        }

        for ($i = 0; $i < $values['num_att']; $i++) {
            $element =& $form->getElement('attachment' . $i);

            if (!isset($element->message) && ($element->isUploadedFile())) {
                echo "here<br/>";
                $_SESSION['attachments'][$i] =  $element->getValue();
            }
        }

        $this->contentPre .= 'att<pre>' . print_r($_SESSION, true) . '</pre>';
        //$this->contentPre .= 'values<pre>' . print_r($values, true) . '</pre>';

        //if (isset($values['prev_step']))
        //    header('Location: add_pub3.php');
        //else
        //    header('Location: add_pub5.php');
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

        function dataKeep(num) {
            var form =  document.forms["add_pub4"];
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
                    } else if (element.name == "num_att") {
                        qsArray.push(form.elements[i].name + "=" + num);
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

        function removePaper() {
            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + "remove_paper=yes";
        }

        function removeAttachment(num) {
            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + "remove_att[" + num + "]=yes";
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

session_start();
$access_level = check_login();
$page = new add_pub4();
echo $page->toHtml();


?>
