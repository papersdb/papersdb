<?php ;

// $Id: add_author.php,v 1.64 2007/10/31 19:29:47 loyola Exp $

/**
 * Creates a form for adding or editing author information.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access author information. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdAuthor.php';
require_once 'Admin/add_pub_base.php';

/**
 * Creates a form for adding or editing author information.
 *
 * @package PapersDB
 */
class add_author extends pdHtmlPage {
    var $debug = 0;
    var $author_id = null;
    var $numNewInterests = 0;
    var $firstname;
    var $lastname;
    var $interests;

    function add_author() {
        parent::__construct('add_author');
        $this->loadHttpVars();

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            $pub =& $_SESSION['pub'];

            if (isset($pub->pub_id))
                $this->page_title = 'Edit Publication';
            else
                $this->page_title = 'Add Publication';
        }
        else if ($this->author_id == null)
            $this->page_title = 'Add Author';
        else
            $this->page_title = 'Edit Author';

        if ($this->loginError) return;

        $author = new pdAuthor();

        if ($this->author_id != null) {
          $result = $author->dbLoad($this->db, $this->author_id,
                                    PD_AUTHOR_DB_LOAD_BASIC
                                    | PD_AUTHOR_DB_LOAD_INTERESTS);

            if (!$result) {
                $this->pageError = true;
                return;
            }
        }

        $form = new HTML_QuickForm('authorForm');

        $form->addElement('hidden', 'author_id', $this->author_id);

        if ($this->author_id == null)
            $form->addElement('header', null,
                              $this->helpTooltip('Add Author',
                                                 'addAuthorPageHelp',
                                                 'helpHeading'));
        else
            $form->addElement('header', null, 'Edit Author');

        $form->addElement('text', 'firstname', 'First Name:',
                          array('size' => 50, 'maxlength' => 250));
        $form->registerRule('invalid_punct', 'regex',
                            '/^[^()\/\*\^\?#!@$%+=,\"\'><~\[\]{}]+$/');
        $form->addRule('firstname', 'the first name cannot contain punctuation',
                       'invalid_punct', null, 'client');
        $form->addElement('text', 'lastname', 'Last Name:',
                          array('size' => 50, 'maxlength' => 250));

        $auth_list = new pdAuthorList($this->db);
        $form->addElement('select', 'authors_in_db', null, $auth_list->list,
                          array('style' => 'overflow: hidden; visibility: hidden; width: 1px; height: 0;'));

        $form->addElement('text', 'title',
                          $this->helpTooltip('Title', 'authTitleHelp') . ':',
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'email', 'email:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('email', 'invalid email address', 'email', null,
                       'client');
        $form->addElement('text', 'organization', 'Organization:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'webpage', 'Webpage:',
                          array('size' => 50, 'maxlength' => 250));

        $interests = new pdAuthInterests($this->db);

        $ref = '<br/><div class="small"><a href="javascript:dataKeep('
            . ($this->numNewInterests+1) .')">[Add Interest]</a></div>';

        $form->addElement('select', 'interests', 'Interests:' . $ref,
                          $interests->list,
                          array('multiple' => 'multiple', 'size' => 15));

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            $form->addElement('static', null, null,
                              '<span class="small">When done adding new authors press the "Next Step" button</span>');
        }

        for ($i = 0; $i < $this->numNewInterests; $i++) {
            $form->addElement('text', 'newInterests['.$i.']',
                              'Interest Name ' . ($i + 1) . ':',
                              array('size' => 50, 'maxlength' => 250));
        }

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
            $next_page = substr($_SERVER['PHP_SELF'], 0, $pos)
                . 'papersdb/Admin/add_pub2.php';
            $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

            $buttons[] = HTML_QuickForm::createElement(
                'button', 'prev_step', '<< Previous Step',
                array('onClick' => "location.href='"
                      . $next_page . "';"));
            $buttons[] = HTML_QuickForm::createElement(
                'button', 'cancel', 'Cancel',
                array('onclick' => "cancelConfirm();"));
            $buttons[] = HTML_QuickForm::createElement(
                'reset', 'reset', 'Reset');
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'add_another',
                'Submit and Add Another Author');
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'next_step', 'Next Step >>');

            if ($pub->pub_id != '')
                $buttons[] = HTML_QuickForm::createElement(
                    'submit', 'finish', 'Finish');

            $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

            add_pub_base::addPubDisableMenuItems();
        }
        else {
            $form->addRule('firstname', 'a first name is required', 'required',
                           null, 'client');
            $form->addRule('lastname', 'a last name is required', 'required',
                           null, 'client');


            if ($this->author_id == null)
                $button_label = 'Add Author';
            else
                $button_label = 'Submit';

            $form->addGroup(
                array(
                    HTML_QuickForm::createElement(
                        'reset', 'reset', 'Reset'),
                    HTML_QuickForm::createElement(
                        'submit', 'submit', $button_label)
                    ),
                null, null, '&nbsp;');
        }

        $form->addElement('hidden', 'numNewInterests', $this->numNewInterests);

        $this->form =& $form;

        if ($form->validate()) {
            $this->processForm();
        }
        else {
            $this->renderForm($author);
        }
    }

    function renderForm($author) {
        $form =& $this->form;

        foreach (array_keys(get_class_vars(get_class($this))) as $member) {
            $defaults[$member] = $this->$member;
        }

        $form->setDefaults($defaults);

        if ($author->author_id != '') {
          $form->setDefaults($author->asArray());

          if (count($author->interests) > 0)
            $form->setDefaults(
              array('interests' => array_keys($author->interests)));
        }

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            assert('isset($_SESSION["pub"])');
            $pub =& $_SESSION['pub'];

            if (isset($pub->pub_id))
                echo '<h3>Editing Publication Entry</h3>';
            else
                echo '<h3>Adding Publication Entry</h3>';

            echo $pub->getCitationHtml('..', false), '<p/>',
                add_pub_base::similarPubsHtml();
        }

        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            '<table width="100%" border="0" cellpadding="3" '
            . 'cellspacing="2" bgcolor="#CCCC99">'
            . '<form{attributes}>{content}</form></table>');
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

        // check if user pressed "Next Step >>" button and did not enter
        // a name
        if (isset($values['next_step']) && isset($_SESSION['state'])
            && ($_SESSION['state'] == 'pub_add')) {
            if ((!isset($values['firstname']) || ($values['firstname'] == ''))
                 && (!isset($values['lastname'])
                     || ($values['lastname'] == ''))) {
                header('Location: add_pub3.php');
                return;
            }
        }

        // if user has not entered a name then bring
        if (isset($values['add_another']) && isset($_SESSION['state'])
            && ($_SESSION['state'] == 'pub_add')) {
            if ((!isset($values['firstname']) || ($values['firstname'] == ''))
                 && (!isset($values['lastname'])
                     || ($values['lastname'] == ''))) {
                header('Location: add_author.php');
                return;
            }
        }

        $author = new pdAuthor();
        if ($this->author_id != null)
            $author->author_id = $this->author_id;

        $author->name = $values['lastname'] . ', ' . $values['firstname'];
        $author->firstname    = $values['firstname'];
        $author->lastname     = $values['lastname'];
        $author->title        = $values['title'];
        $author->email        = $values['email'];
        $author->organization = $values['organization'];
        $author->webpage      = $values['webpage'];
        $author->interests    = array();

        if (isset($values['interests']) && (count($values['interests']) > 0))
            $author->interests
                = array_merge($author->interests, $values['interests']);

        if (isset($values['newInterests'])
            && (count($values['newInterests']) > 0))
            $author->interests
                = array_merge($author->interests, $values['newInterests']);

        // check if an author with a similar name already exists
        if ($this->author_id == null) {
            $like_authors = new pdAuthorList($this->db, $values['firstname'],
                                             $values['lastname']);
            if (count($like_authors->list) > 0) {
                $_SESSION['new_author'] = $author;
                header('Location: author_confirm.php');
                return;
            }
        }

        $author->dbSave($this->db);

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            assert('isset($_SESSION["pub"])');
            $pub =& $_SESSION['pub'];
            $pub->addAuthor($this->db, $author->author_id);

            if ($this->debug) return;

            if (isset($values['finish']))
                header('Location: add_pub_submit.php');
            else if (isset($values['add_another']))
                header('Location: add_author.php');
            else
                header('Location: add_pub2.php');
        }
        else {
            if ($this->author_id == null)
              echo 'Author "', $values['firstname'], ' ', $values['lastname'], 
              	'" ', 'succesfully added to the database.', '<p/>', 
              	'<a href="', $_SERVER['PHP_SELF'], '">', 
              	'Add another new author</a>';
            else
              echo 'Changes to author "', $values['firstname'], ' ', 
              	$values['lastname'], '" ', 'submitted to the database.';
        }
    }

    function javascript() {
        $js_file = FS_PATH . '/Admin/js/add_author.js';
        assert('file_exists($js_file)');

        $content = file_get_contents($js_file);

        $this->js .= str_replace(array('{host}', '{self}'),
                                 array($_SERVER['HTTP_HOST'],
                                       $_SERVER['PHP_SELF']),
                                 $content);

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            $js_file = FS_PATH . '/Admin/js/add_pub_cancel.js';

            assert('file_exists($js_file)');
            $content = file_get_contents($js_file);

            $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
            $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

            $this->js .= str_replace(array('{host}', '{new_location}'),
                                     array($_SERVER['HTTP_HOST'], $url),
                                     $content);
        }
    }
}

$page = new add_author();
echo $page->toHtml();


?>
