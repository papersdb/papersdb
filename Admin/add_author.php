<?php

/**
 * Creates a form for adding or editing author information.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access author information. */
require_once '../includes/defines.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdAuthor.php';
require_once 'includes/pdAuthorList.php';
require_once 'Admin/add_pub_base.php';
require_once 'HTML/QuickForm/advmultiselect.php';


/**
 * Creates a form for adding or editing author information.
 *
 * @package PapersDB
 */
class add_author extends pdHtmlPage {
    public $debug = 0;
    public $author_id = null;
    public $numNewInterests = 0;
    public $firstname;
    public $lastname;
    public $interests;
    private $all_interests;

    public function __construct() {
        parent::__construct('add_author');
        $this->loadHttpVars();
        $this->use_mootools = true;
        $this->all_interests = pdAuthInterests::createList($this->db);

        // before showing a loggin error, show the correct title for the page
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
                                    pdAuthor::DB_LOAD_BASIC
                                    | pdAuthor::DB_LOAD_INTERESTS);

            if (!$result) {
                $this->pageError = true;
                return;
            }
        }

        $form = new HTML_QuickForm('authorForm');

        $form->addElement('hidden', 'author_id', $this->author_id);

        if ($this->author_id == null) {
            $form->addElement(
                'header', 'add_author_hdr',
                '<span class="Tips1" title="Adding an Author::Input the
 author\'s first name, last name, email address and organization. Optionally,
 interests may be selected from the list given or new interest can be added to
 the database.
 &lt;p/&gt;
 Multiple interests can be selected by holding down the control
 key and then left-clicking on the text. If you do not see the
 appropriate interests you can add them using the &lt;b&gt;Add
 Interest&lt;/b&gt; link.
 &lt;p/&gt;
 Clicking the &lt;b&gt;Add Interest&lt;/b&gt; link will bring up a
 new field each it is pressed. Type the text of the new interest into the
 this field.">Add Author</span>');
        }
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

        $form->addElement('select', 'authors_in_db', null,
        	pdAuthorList::create($this->db),
            array('style' => 'overflow: hidden; visibility: hidden; width: 1px; height: 0;'));

        $tooltip = 'Title::The author\'s formal title. For example:
 &lt;ul&gt;
 &lt;li&gt;Professor&lt;/li&gt;
 &lt;li&gt;PostDoc&lt;/li&gt;
 &lt;li&gt;PhD Student&lt;/li&gt;
 &lt;li&gt;MSc Student&lt;/li&gt;
 &lt;li&gt;Colleague&lt;/li&gt;
 &lt;/ul&gt;';

        $form->addElement(
            'text', 'title',
            "<span class=\"Tips1\" title=\"$tooltip\">Title:</span>",
            array('size' => 50, 'maxlength' => 250,));
        $form->addElement('text', 'email', 'email:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('email', 'invalid email address', 'email', null,
                       'client');
        $form->addElement('text', 'organization', 'Organization:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'webpage', 'Webpage:',
                          array('size' => 50, 'maxlength' => 250));

        $ref = '<br/><div class="small"><a href="javascript:dataKeep('
            . ($this->numNewInterests+1) .')">[Add Interest]</a></div>';

        $ams = $form->addElement('advmultiselect', 'interests', null,
        	$this->all_interests, 
            array('size' => 15, 'class' => 'pool', 'style' =>  'width:200px;'));
            
        $ams->setLabel(array('Interests:' . $ref, 'Available', 'Selected'));       

        $ams->setButtonAttributes('add', array('value' => 'Add >>',
        	'class' => 'inputCommand'));
        $ams->setButtonAttributes('remove', array('value' => '<< Remove',
 			'class' => 'inputCommand'));    

        $template = <<<TEMPLATE_END
{javascript}
<table{class}>
  <thead>
    <tr>
      <!-- BEGIN label_2 --><tr><th align="center">{label_2}</th><!-- END label_2 -->
      <!-- BEGIN label_3 --><th align="center">{label_3}</th><!-- END label_3 -->
    <tr>
  </thead>
<tr>
  <td>{unselected}</td>
  <td>{selected}</td>
</tr>
<tr>
  <td>{add}</td>
  <td>{remove}</td>
</tr>
</table>
TEMPLATE_END;
        $ams->setElementTemplate($template);   

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

    public function renderForm($author) {
        $form =& $this->form;

        foreach (array_keys(get_class_vars(get_class($this))) as $member) {
            $defaults[$member] = $this->$member;
        }

        if ($author->author_id != '') {
            $defaults = array_merge($defaults, $author->asArray());

            // override interests
            if (count($author->interests) > 0) {
                $defaults['interests'] = array_keys($author->interests);
            }
        }

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            assert('isset($_SESSION["pub"])');
            $pub =& $_SESSION['pub'];

            if (isset($pub->pub_id))
                echo '<h3>Editing Publication Entry</h3>';
            else
                echo '<h3>Adding Publication Entry</h3>';

            echo $pub->getCitationHtml('..', false), '<p/>',
                add_pub_base::similarPubsHtml($this->db);
        }
        
        //debugVar('defaults', $defaults);
        $form->setDefaults($defaults);
        
        $renderer =& $form->defaultRenderer();        
        $form->accept($renderer);
        $this->renderer =& $renderer;
        $this->javascript();
    }

    public function processForm() {
        $form =& $this->form;
        $values =& $form->exportValues();

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

        if (isset($values['interests']) && (count($values['interests']) > 0)) {            
            foreach ($values['interests'] as $int_id) {
                $author->interests[$int_id] = $this->all_interests[$int_id];
            }
        }

        if (isset($values['newInterests'])
            && (count($values['newInterests']) > 0))
            $author->interests
                = array_merge($author->interests, $values['newInterests']);

        // check if an author with a similar name already exists
        if ($this->author_id == null) {
            $like_authors = pdAuthorList::create($this->db, $values['firstname'],
                                                 $values['lastname']);
            if (count($like_authors) > 0) {
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
            return;
        }
        header('Location: ../view_author.php?author_id=' . $author->author_id);
    }

    public function javascript() {
        $js_file = 'js/add_author.js';
        assert('file_exists($js_file)');

        $content = file_get_contents($js_file);

        $this->js .= str_replace(array('{host}', '{self}'),
                                 array($_SERVER['HTTP_HOST'],
                                       $_SERVER['PHP_SELF']),
                                 $content);

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            $js_file = 'js/add_pub_cancel.js';

            assert('file_exists($js_file)');
            $content = file_get_contents($js_file);

            $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
            $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

            $this->js .= str_replace(array('{host}', '{new_location}'),
                                     array($_SERVER['HTTP_HOST'], $url),
                                     $content);
        }
        
        $js_file = dirname(__FILE__) . '/../pear/HTML/QuickForm/qfamsHandler.js';
        assert('file_exists($js_file)');
        $this->js .=  file_get_contents($js_file);
    }
}

$page = new add_author();
echo $page->toHtml();

?>
