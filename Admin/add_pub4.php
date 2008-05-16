<?php

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
require_once 'includes/pdAuthor.php';
require_once 'includes/pdAttachmentTypesList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub4 extends add_pub_base {
    public $debug = 0;

    public function __construct() {
        parent::__construct();

        if ($this->loginError) return;
        $this->use_mootools = true;

        $this->pub =& $_SESSION['pub'];

        if (isset($this->pub->pub_id))
            $this->page_title = 'Edit Publication';

        // initialize attachments
        if (!isset($_SESSION['paper']) && !isset($_SESSION['attachments'])) {
            $_SESSION['paper'] = $this->pub->paperFilenameGet();
            if (count($this->pub->additional_info) > 0)
                for ($i = 0, $n = count($this->pub->additional_info);
                     $i < $n; $i++) {
                    $_SESSION['attachments'][$i]
                        = $this->pub->attFilenameGet($i);
                    $_SESSION['att_types'][$i]
                        = $this->pub->additional_info[$i]->type;
                }
        }

        $form = new HTML_QuickForm('add_pub4');
        $this->form =& $form;
        $this->formAddAttachments();
        $this->formAddWebLinks();
        $this->formRelatedPubs();

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'submit', 'prev_step', '<< Previous Step'),
                HTML_QuickForm::createElement(
                    'button', 'cancel', 'Cancel',
                    array('onclick' => "cancelConfirm();")),
                HTML_QuickForm::createElement(
                    'reset', 'reset', 'Reset'),
                HTML_QuickForm::createElement(
                    'submit', 'finish', 'Finish')),
            'buttons', null, '&nbsp;', false);

        if ($form->validate())
            $this->processForm();
        else
            $this->renderForm();
    }

    public function formAddAttachments() {
        $form =& $this->form;
        $user =& $_SESSION['user'];

        $num_att = 0;
        if (isset($_SESSION['attachments']))
            $num_att = count($_SESSION['attachments']);

        $form->addElement('header', null, 'Attachments');

        $tooltip = 'Paper::Attach a postscript, PDF, or other version of the
publication.';

        if (isset($_SESSION['paper']) && ($_SESSION['paper'] != 'none')) {
            $filename = basename($_SESSION['paper'], '.' . $user->login);
            $filename = str_replace('paper_', '', $filename);

            $pos = strpos($_SESSION['paper'], 'uploaded_files');
            $html = '<a href="../' . substr($_SESSION['paper'], $pos) . '">'
                . $filename . '</a>';

            $form->addGroup(
                array(
                    HTML_QuickForm::createElement(
                        'static', 'assigned_paper', null, $html),
                    HTML_QuickForm::createElement(
                        'submit', 'remove_paper', 'Remove Paper')
                    ),
                null,
                "<span class=\"Tips1\" title=\"$tooltip\">Current Paper</span>:",
                '&nbsp;', false);
        }
        else {
            $form->addElement(
                'file', 'uploadpaper',
                "<span class=\"Tips1\" title=\"$tooltip\">Paper</span>:",
                array('size' => 45));
        }

        $form->addElement('hidden', 'num_att', $num_att);

        $tooltip = 'Attachements::Used to attach additional files to the publication entry.';

        for ($i = 0; $i < $num_att; $i++) {
            unset($filename);

            $filename = basename($_SESSION['attachments'][$i], '.' . $user->login);
            $filename = str_replace('additional_', '', $filename);
            $att_type = $_SESSION['att_types'][$i];

            $pos = strpos($_SESSION['attachments'][$i], 'uploaded_files');
            $html = '<a href="../'
                . substr($_SESSION['attachments'][$i], $pos) . '">'
                . $filename . '</a>';

            if ($filename != '') {
                $form->addGroup(
                    array(
                        HTML_QuickForm::createElement(
                            'static', 'att_type' . $i, null, '['.$att_type.']'),
                        HTML_QuickForm::createElement(
                            'static', 'attachment' . $i, null, $html),
                        HTML_QuickForm::createElement(
                            'submit', 'remove_att' . $i, 'Remove Attachment')
                        ),
                    null,
                    "<span class=\"Tips1\" title=\"$tooltip\">Attachment "
                    . ($i + 1) . '</span>:',
                    '&nbsp;', false);
            }
        }

        $att_types = pdAttachmentTypesList::create($this->db);
        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'select', 'new_att_type', null, $att_types),
                HTML_QuickForm::createElement(
                    'file', 'new_att', null, array('size' => 35))
                ),
            'new_att_group',
            "<span class=\"Tips1\" title=\"$tooltip\">Attachment "
            . ($num_att + 1) . '</span>:',
            '&nbsp;', false);

        $form->addElement('submit', 'add_att', 'Add Attachment');
    }

    public function formAddWebLinks() {
        $form =& $this->form;

        $num_web_links = count($this->pub->web_links);

        $form->addElement('header', 'web_links_hdr', 'Web Links', null);

        $tooltip = 'Link::Used to link this publication to an outside source
such as a website or a publication that is not in the current database.';

        if (count($this->pub->web_links) > 0) {
            $c = 0;
            foreach (array_keys($this->pub->web_links) as $text) {
                $form->addGroup(
                    array(
                        HTML_QuickForm::createElement(
                            'text', 'curr_web_link_text' . $c, null,
                            array('size' => 25, 'maxlength' => 250)),
                        HTML_QuickForm::createElement(
                            'static', 'web_links_help', null, ':'),
                        HTML_QuickForm::createElement(
                            'text', 'curr_web_link_url' . $c, null,
                            array('size' => 25, 'maxlength' => 250)),
                        HTML_QuickForm::createElement(
                            'submit', 'remove_web_link' . $c, 'Remove')
                        ),
                    'curr_web_links_group',
                    "<span class=\"Tips1\" title=\"$tooltip\">Link "
                    . ($c + 1) . '</span>:',
                    '&nbsp;', false);
                $label = '';
                $c++;
            }
        }

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'text', 'new_web_link_text', null,
                    array('size' => 25, 'maxlength' => 250)),
                HTML_QuickForm::createElement(
                    'static', 'web_links_help', null, ':'),
                HTML_QuickForm::createElement(
                    'text', 'new_web_link_url', null,
                    array('size' => 25, 'maxlength' => 250))
                ),
            'new_web_links_group',
            "<span class=\"Tips1\" title=\"$tooltip\">New Link</span>:"
            . '<br/><span class="small">name&nbsp;:&nbsp;url</span>',
            '&nbsp;', false);

        $form->addElement('submit', 'add_web_link', 'Add Web Link');
        $form->addElement('hidden', 'num_web_links', $num_web_links);
    }

    public function formRelatedPubs() {
        $form =& $this->form;

        $num_related_pubs = count($this->pub->related_pubs);

        $form->addElement('header', 'related_pubs_hdr',
                          'Related Publication(s)', null);

        $tooltip = 'Pub Link::Creates a relationship between this publication
and another publication that already has an entry in the database.';

        if (count($this->pub->related_pubs) > 0) {
            $c = 0;
            foreach ($this->pub->related_pubs as $pub_id) {
                $intPub = new pdPublication();
                $result = $intPub->dbLoad($this->db, $pub_id);

                if (!$result) continue;

                $form->addGroup(
                    array(
                        HTML_QuickForm::createElement(
                            'static', 'curr_related_pub' . $c, null,
                            $intPub->title),
                        HTML_QuickForm::createElement(
                            'submit', 'remove_related_pub' . $c, 'Remove'),
                        HTML_QuickForm::createElement(
                            'hidden', 'related_pub_id' . $c, $intPub->pub_id)
                        ),
                    'curr_related_pubs_group',
                    "<span class=\"Tips1\" title=\"$tooltip\">Pub "
                    . ($c + 1) . '</span>:',
                    '&nbsp;', false);
                $label = '';
                $c++;
            }
        }

        $pub_list = pdPubList::create($this->db);
        if (!empty($pub_list) && (count($pub_list) > 0)) {
	        $options[''] = '--- select publication --';
	        foreach ($pub_list as $p) {
	    	        if (strlen($p->title) > 70)
	                $options[$p->pub_id] = substr($p->title, 0, 67) . '...';
	            else
	                $options[$p->pub_id] = $p->title;
	        }
	        $form->addElement(
                    'select', 'new_related_pub',
                    "<span class=\"Tips1\" title=\"$tooltip\">New Pub </span>:",
                    $options);
        }

        $form->addElement('submit', 'add_related_pubs',
                          'Add Related Publication');
        $form->addElement('hidden', 'num_related_pubs', $num_related_pubs);
    }

    public function renderForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;

        $defaults = array();

        if (count($this->pub->web_links) > 0) {
            $c = 0;
            foreach ($this->pub->web_links as $text => $url) {
                $defaults['curr_web_link_text' . $c] = $text;
                $defaults['curr_web_link_url' . $c] = $url;
                $c++;
            }
        }

        $form->setDefaults($defaults);

        if (isset($this->pub->pub_id))
            echo '<h3>Editing Publication Entry</h3>';
        else
            echo '<h3>Adding Publication Entry</h3>';

        echo $this->pub->getCitationHtml('', false), '&nbsp;',
            getPubIcons($this->db, $this->pub, 0x1), '<p/>',
            add_pub_base::similarPubsHtml($this->db);

        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            '<form{attributes}><table width="100%" border="0" cellpadding="3"
cellspacing="2" bgcolor="#CCCC99">{content}</table></form>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');

        $form->accept($renderer);
        $this->renderer =& $renderer;
        $this->javascript();
    }

    public function processForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;
        $user =& $_SESSION['user'];
        $values = $form->exportValues();

        $element =& $form->getElement('uploadpaper');
        if (!isset($element->message) && ($element->isUploadedFile())) {
            $basename = 'paper_' . $_FILES['uploadpaper']['name'] . '.'
                . $user->login;
            $element->moveUploadedFile(FS_PATH_UPLOAD, $basename);
            $_SESSION['paper'] = FS_PATH_UPLOAD . $basename;
        }

        $group =& $form->getElement('new_att_group');
        $elements =& $group->getElements();
        foreach ($elements as $element) {
            if (($element->getName() == 'new_att')
                && $element->isUploadedFile()) {
                $basename = 'additional_' . $_FILES['new_att']['name']
                    . '.' . $user->login;
                $element->moveUploadedFile(FS_PATH_UPLOAD, $basename);
                $_SESSION['attachments'][] =  FS_PATH_UPLOAD . $basename;
                $_SESSION['att_types'][] =  $values['new_att_type'];
            }
        }

        if (isset($values['remove_paper'])) {
            // check if this is a temporary file
            if (strpos($_SESSION['paper'], '.' . $user->login) !== false)
                unlink($_SESSION['paper']);

            $_SESSION['paper'] = 'none';
            header('Location: add_pub4.php');
            return;
        }

        for ($i = 0; $i < $values['num_att']; $i++) {
            if (isset($values['remove_att' . $i])) {
                // check if this is a temporary file
                if (strpos($_SESSION['attachments'][$i], '.' . $user->login) !== false)
                    unlink($_SESSION['attachments'][$i]);

                if (strpos($_SESSION['attachments'][$i], 'additional_') !== false)
                    $_SESSION['removed_atts'][] = $_SESSION['attachments'][$i];

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

        if (($values['new_web_link_text'] != '')
            && ($values['new_web_link_url'] != '')) {
            $this->pub->addWebLink($values['new_web_link_text'],
                             $values['new_web_link_url'] );
        }

        for ($i = 0; $i < $values['num_web_links']; $i++) {
            if (isset($values['remove_web_link' . $i])) {
                $this->pub->delWebLink($values['curr_web_link_text' . $i]);

                if (! $this->debug)
                    header('Location: add_pub4.php');
                return;
            }
        }

        if ($values['new_related_pub'] > 0) {
            $this->pub->relatedPubAdd($values['new_related_pub']);
        }

        for ($i = 0; $i < $values['num_related_pubs']; $i++) {
            if (isset($values['remove_related_pub' . $i])) {
                $this->pub->relatedPubRemove($values['related_pub_id' . $i]);

                if (! $this->debug)
                    header('Location: add_pub4.php');
                return;
            }
        }

        if ($this->debug) {
            return;
        }

        if (isset($values['add_att'])) {
            header('Location: add_pub4.php');
        }
        else if (isset($values['add_web_link'])) {
            header('Location: add_pub4.php');
        }
        else if (isset($values['add_related_pubs'])) {
            header('Location: add_pub4.php');
        }
        else if (isset($values['prev_step']))
            header('Location: add_pub3.php');
        else
            header('Location: add_pub_submit.php');
    }

    public function javascript() {
        $js_files = array('js/add_pub4.js', 'js/add_pub_cancel.js');

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        foreach ($js_files as $js_file) {
            assert('file_exists($js_file)');
            $content = file_get_contents($js_file);

            $this->js .= str_replace(array('{host}', '{self}',
                                           '{new_location}'),
                                     array($_SERVER['HTTP_HOST'],
                                           $_SERVER['PHP_SELF'],
                                           $url),
                                     $content);
        }
    }
}

$page = new add_pub4();
echo $page->toHtml();

?>
