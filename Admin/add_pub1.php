<?php ;

// $Id: add_pub1.php,v 1.47 2007/10/31 19:29:47 loyola Exp $

/**
 * This page is the form for adding/editing a publication.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requires the base class and classes to access the database. */
require_once 'Admin/add_pub_base.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdPublication.php';
require_once 'includes/pdPubList.php';
require_once 'includes/pdExtraInfoList.php';
require_once 'includes/authorselect.php';
require_once 'includes/pdAttachmentTypesList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub1 extends add_pub_base {
    var $debug = 0;
    var $cat_venue_options;
    var $category_list;

    function add_pub1() {
        parent::add_pub_base();

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);

        if (isset($_SESSION['pub'])) {
            // according to session variables, we are already editing a
            // publication
            $this->pub =& $_SESSION['pub'];
        }
        else if ($this->pub_id != '') {
            // pub_id passed in with $_GET variable
            $this->db = dbCreate();
            $this->pub = new pdPublication();
            $result = $this->pub->dbLoad($this->db, $this->pub_id);
            if (!$result) {
                $this->pageError = true;
                return;
            }

            $_SESSION['pub'] =& $this->pub;
        }
        else {
            // create a new publication
            $this->pub = new pdPublication();
            $_SESSION['pub'] =& $this->pub;
        }

        if ($this->pub->pub_id != '')
            $this->page_title = 'Edit Publication';

        $form = new HTML_QuickForm('add_pub1');
        $form->addElement('header', null, 'Add Publication');

        // title
        $form->addElement('text', 'title',
                          $this->helpTooltip('Title', 'titleHelp') . ':',
                          array('size' => 60, 'maxlength' => 250));
        $form->addRule('title', 'please enter a title', 'required',
                       null, 'client');

        $form->addElement('textarea', 'abstract',
                          $this->helpTooltip('Abstract', 'abstractHelp')
                          . ':<br/><div class="small">HTML Enabled</div>',
                          array('cols' => 60, 'rows' => 10));

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'text', 'keywords', null,
                    array('size' => 60, 'maxlength' => 250)),
                HTML_QuickForm::createElement(
                    'static', 'kwgroup_help', null,
                    '<span class="small">separate using semi-colons (;)</span>')),

            'kwgroup', $this->helpTooltip('Keywords', 'keywordsHelp') . ':',
            '<br/>', false);

        $form->addElement('textarea', 'user',
                          $this->helpTooltip('User Info:', 'userInfoHelp'),
                          array('cols' => 60, 'rows' => 2));

        $buttons[] = HTML_QuickForm::createElement(
            'button', 'cancel', 'Cancel',
            array('onclick' => "cancelConfirm();"));
        $buttons[] = HTML_QuickForm::createElement(
            'reset', 'reset', 'Reset');
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'step2', '>> Step 2');
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'step3', '>> Step 3');
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'step4', '>> Step 4');

        if ($this->pub->pub_id != '')
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'finish', 'Finish');

        $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

        $this->db =& $this->db;
        $this->form =& $form;

        if ($form->validate())
            $this->processForm();
        else
            $this->renderForm();
    }

    function renderForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;

        $defaults = array('title'    => $this->pub->title,
                          'abstract' => $this->pub->abstract,
                          'keywords' => $this->pub->keywords,
                          'user'     => $this->pub->user);

        $this->form->setDefaults($defaults);

        if (isset($_SESSION['pub']) && ($_SESSION['pub']->title != '')) {
            $this->pub =& $_SESSION['pub'];

            if (isset($this->pub->pub_id))
                echo '<h3>Editing Publication Entry</h3>';
            else
                echo '<h3>Adding Publication Entry</h3>';

            echo $this->pub->getCitationHtml('..', false), '&nbsp;',
                $this->getPubIcons($this->pub, 0x1), '<p/>',
                add_pub_base::similarPubsHtml();
        }

        $renderer =& $this->form->defaultRenderer();

        $this->form->setRequiredNote(
            '<font color="#FF0000">*</font> shows the required fields.');

        $renderer->setFormTemplate(
            '<table width="100%" bgcolor="#CCCC99">'
            . '<form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');
        $renderer->setGroupTemplate(
            '<table><tr>{content}</tr></table>', 'name');

        $renderer->setElementTemplate(
            '<tr><td colspan="2">{label}</td></tr>',
            'categoryinfo');

        $this->form->accept($renderer);
        $this->renderer =& $renderer;
        $this->javascript();
    }

    function processForm() {
        assert('isset($_SESSION["pub"])');
        $form =& $this->form;

        $values = $form->exportValues();

        $this->pub->load($values);
        $_SESSION['state'] = 'pub_add';

        $result = $this->pub->duplicateTitleCheck($this->db);
        if (count($result) > 0)
            $_SESSION['similar_pubs'] = $result;

        if ($this->debug) {
            debugVar('values', $values);
            debugVar('pub', $this->pub);
            return;
        }

        if (isset($values['finish']))
            header('Location: add_pub_submit.php');
        else if (isset($values['step2']))
            header('Location: add_pub2.php');
        else if (isset($values['step3']))
            header('Location: add_pub3.php');
        else if (isset($values['step4']))
            header('Location: add_pub4.php');
    }

    function javascript() {
        $js_files = array(FS_PATH . '/Admin/js/add_pub1.js',
                          FS_PATH . '/Admin/js/add_pub_cancel.js');

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

$page = new add_pub1();
echo $page->toHtml();

?>
