<?php ;

// $Id: add_author.php,v 1.52 2007/04/05 18:33:08 aicmltec Exp $

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
 * This is just a stub, see javascript author_check() for the real code
 */
function author_check() {
    return true;
}

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
        parent::pdHtmlPage('add_author');
        $this->loadHttpVars();

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add'))
            $this->page_title = 'Add Publication';
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
        $form->addRule('firstname', 'a first name is required', 'required',
                       null, 'client');
        $form->registerRule('invalid_punct', 'regex',
                            '/^[^()\/\*\^\?#!@$%+=,\"\'><~\[\]{}]+$/');
        $form->addRule('firstname', 'the first name cannot contain punctuation',
                       'invalid_punct', null, 'client');
        $form->addElement('text', 'lastname', 'Last Name:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('lastname', 'a last name is required', 'required', null,
                       'client');

        $auth_list = new pdAuthorList($this->db);
        $form->addElement('select', 'authors_in_db', null, $auth_list->list,
                          array('style' => 'overflow: hidden; visibility: hidden; width: 1px; height: 0;'));

        $form->registerRule('author_check', 'callback', 'author_check');
        // author_check() is actually implemented in javascript

        if ($this->author_id == null) {
          // only add this rule if adding a new author
          $form->addRule(array('firstname', 'lastname'),
                         'First and last name: '
                         . 'A similar author already exists in the database',
                         'author_check', true, 'client');
        }

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
                array('onclick' => "location.href='" . $url . "';"));
            $buttons[] = HTML_QuickForm::createElement(
                'reset', 'reset', 'Reset');
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'add_another',
                'Submit and Add Antother Author');
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'next_step', 'Next Step >>');

            $pub =& $_SESSION['pub'];

            if ($pub->pub_id != '')
                $buttons[] = HTML_QuickForm::createElement(
                    'submit', 'finish', 'Finish');

            $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

            add_pub_base::addPubDisableMenuItems();
        }
        else {
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

            echo '<h3>Adding Following Publication</h3>'
                . $pub->getCitationHtml('..', false) . '<p/>'
                . add_pub_base::similarPubsHtml();
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

        // check if an author with a similar name already exists
        if ($this->author_id == null) {
            $like_authors = new pdAuthorList($this->db, $values['firstname'],
                                             $values['lastname']);
            if (count($like_authors->list) > 0) {
                echo 'The following authors have similar names:<ul>';
                foreach ($like_authors->list as $auth) {
                    echo '<li>' . $auth . '</li>';
                }
                echo '</ul>New author not submitted.';
                return;
            }
        }

        $author = new pdAuthor();
        if ($this->author_id != null)
            $author->author_id = $this->author_id;

        $author->name = $values['lastname'] . ', ' . $values['firstname'];
        $author->title = $values['title'];
        $author->email     = $values['email'];
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
              echo 'Author "' . $values['firstname'] . ' '
                . $values['lastname'] . '" '
                . 'succesfully added to the database.'
                . '<p/>'
                . '<a href="' . $_SERVER['PHP_SELF'] . '">'
                . 'Add another new author</a>';
            else
              echo 'Changes to author "'
                . $values['firstname'] . ' ' . $values['lastname'] . '" '
                . 'submitted to the database.';
        }
    }

    function javascript() {
        $js_file = FS_PATH . '/Admin/js/add_author.js';
        assert('file_exists($js_file)');
        $this->js = file_get_contents($js_file);

        $this->js = str_replace(array('{host}', '{self}'),
                                array($_SERVER['HTTP_HOST'],
                                      $_SERVER['PHP_SELF']),
                                $this->js);

    }
}

$page = new add_author();
echo $page->toHtml();


?>
