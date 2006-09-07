<?php ;

// $Id: add_pub4.php,v 1.3 2006/09/07 22:21:41 aicmltec Exp $

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
require_once 'includes/pdAttachmentTypesList.php';

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

        //$this->contentPre .= '<pre>' . print_r($this, true) . '</pre>';
        //$this->contentPre .= 'sess<pre>' . print_r($_SESSION, true) . '</pre>';

        $form = new HTML_QuickForm('add_pub4');
        $this->form =& $form;
        $this->formAddAttachments();
        $this->formAddWebLinks();

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'submit', 'prev_step', '<< Previous Step'),
                HTML_QuickForm::createElement(
                    'submit', 'next_step', 'Next Step >>')),
            'buttons', '', '&nbsp', false);


        if ($form->validate()) {
            $this->processForm();
        }
        else {
            $this->renderForm();
        }
        $this->db->close();
    }

    function formAddAttachments() {
        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        $this->contentPre .= 'sess<pre>' . print_r($_SESSION, true) . '</pre>';
        $num_att = count($_SESSION['attachments']);

        if ($num_att == 0) {
            if (count($pub->additional_info) > 0)
                $num_att = count($pub->additional_info);
        }

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
                    'submit', 'remove_paper', 'Remove Paper')
                    ),
                null, $this->helpTooltip('Current Paper', 'paperAtt') . ':',
                '&nbsp', false);
        }
        else {
            unset($_SESSION['paper']);
            $form->addElement('file', 'uploadpaper',
                              $this->helpTooltip('Paper', 'paperAtt') . ':',
                              array('size' => 45));
        }

        $att_types = new pdAttachmentTypesList($db);
        $form->addElement('hidden', 'num_att', $num_att);

        for ($i = 0; $i < $num_att; $i++) {
            unset($filename);

            if (isset($_SESSION['attachments'][$i])) {
                $filename = $_SESSION['attachments'][$i]['name'];
                $att_type = $_SESSION['att_types'][$i];
            }
            else if ($pub->additional_info[$i] != '') {
                $filename = $pub->additional_info[$i]->location;
                $att_type = $_SESSION['att_types'][$i]->type;
            }

            if ($filename != '') {
                $form->addGroup(
                    array(
                        HTML_QuickForm::createElement(
                            'static', 'att_type' . $i, null, '['.$att_type.']'),
                        HTML_QuickForm::createElement(
                            'static', 'attachment' . $i, null, $filename),
                        HTML_QuickForm::createElement(
                            'submit', 'remove_att' . $i, 'Remove Attachment')
                        ),
                    null, 'Attachment ' . ($i + 1) . ':', '&nbsp;', false);
            }
        }

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'select', 'new_att_type', null, $att_types->list),
                HTML_QuickForm::createElement(
                    'file', 'new_att', null, array('size' => 35))
                ),
            'new_att_group', 'Attachment ' . ($num_att + 1) . ':', '&nbsp;',
            false);

        $form->addElement('submit', 'add_att', 'Add Attachment');
    }

    function formAddWebLinks() {
        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        $options = array('num_web_links', 'num_pub_links');
        foreach ($options as $opt) {
            if (isset($_SESSION[$opt]) && is_numeric($_SESSION[$opt]))
                $$opt = $_SESSION[$opt];
            else
                $$opt = 0;
        }

        $form->addElement('header', 'link_info', 'Links', null);

        $label = $this->helpTooltip('Web Links', 'extLinks') . ':';

        // get here only if the publication already has links assigned
        if (count($pub->extPointer) == 0) {
            $form->addElement('static', 'web_link_label', $label, 'none');
        }
        else {
            $c = 0;
            foreach ($pub->extPointer as $text => $link) {
                if (strpos($link, 'http://') !== false)
                    $value = '<a href="' . $link . '">' . $text . '</a>';
                else
                    $value = $link;
                $form->addGroup(
                    array(
                        HTML_QuickForm::createElement(
                            'static', 'curr_web_links[' . $text
                            . ':' . $link . ']', $label,
                            $value),
                        HTML_QuickForm::createElement(
                            'advcheckbox',
                            'remove_curr_web_links[' . $text
                            . ':' . $link . ']',
                            null, 'check to remove',
                            null, array('no', 'yes'))),
                    'curr_web_links_group', $label, '&nbsp;', false);
                $label = '';
                $c++;
            }
        }

        $form->addElement('submit', 'add_web_links', 'Add Web Link');
        $form->addElement('hidden', 'num_web_links', $num_web_links);

        // publication links
        $label = $this->helpTooltip('Publication Links', 'pubLinks') . ':';

        if (count($pub->intPointer) == 0) {
            $form->addElement('static', 'pub_link_label', $label, 'none');
        }
        else {
            $c = 0;
            foreach ($pub->intPointer as $int) {
                $intPub = new pdPublication();
                $result = $intPub->dbLoad($db, $int->value);
                if ($result) {
                    $pubLinkstr = '<a href="' . $url
                        . 'view_publication.php?pub_id=' . $int->value
                        . '">' . $intPub->title . '</a>';

                    $form->addGroup(
                        array(
                            HTML_QuickForm::createElement(
                                'static', 'curr_pub_links['
                                . $int->value, $label . ']',
                                $pubLinkstr),
                            HTML_QuickForm::createElement(
                                'advcheckbox',
                                'remove_curr_pub_links[' . $int->value . ']',
                                null, 'check to remove',
                                null, array('no', 'yes'))),
                        'curr_pub_links_group', $label, '<br/>', false);
                    $label = '';
                    $c++;
                }
            }
        }

        $form->addElement('submit', 'add_pub_links', 'Add Publication Link');
        $form->addElement('hidden', 'num_pub_links', $num_pub_links);
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

        $group =& $form->getElement('new_att_group');
        $elements =& $group->getElements();
        foreach ($elements as $element) {
            if (($element->getName() == 'new_att')
                && $element->isUploadedFile()) {
                $_SESSION['attachments'][] =  $element->getValue();
                $_SESSION['att_types'][] =  $values['new_att_type'];
            }
        }

        for ($i = 0; $i < $values['num_att']; $i++) {
            if (isset($values['remove_att' . $i])) {
                unset($_SESSION['attachments'][$i]);
                unset($_SESSION['att_types'][$i]);

                // reindex
                $_SESSION['attachments']
                    = array_values($_SESSION['attachments']);
                $_SESSION['att_types'] = array_values($_SESSION['att_types']);
                header('Location: add_pub4.php');
                return;
            }
        }

        //$this->contentPre .= 'element<pre>' . print_r($form, true) . '</pre>';
        //$this->contentPre .= 'sess<pre>' . print_r($_SESSION, true) . '</pre>';
        //$this->contentPre .= 'values<pre>' . print_r($values, true) . '</pre>';
        //return;

        if (isset($values['add_att'])) {
            header('Location: add_pub4.php');
        }
        else if (isset($values['remove_paper'])) {
            unset($_SESSION['paper']);
            header('Location: add_pub4.php');
        }
        else if (isset($values['add_web_links'])) {
            $_SESSION['num_web_links'] = $values['num_web_links'] + 1;
            header('Location: add_pub4.php');
        }
        else if (isset($values['add_pub_links'])) {
            $_SESSION['num_pub_links'] = $values['num_pub_links'] + 1;
            header('Location: add_pub4.php');
        }
        else if (isset($values['prev_step']))
            header('Location: add_pub3.php');
        else
            header('Location: add_pub5.php');
    }

    function javascript() {
        $this->js = <<<JS_END
            <script language="JavaScript" type="text/JavaScript">

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
