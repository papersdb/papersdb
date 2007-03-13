<?php ;

// $Id: add_pub2.php,v 1.16 2007/03/13 22:06:11 aicmltec Exp $

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
require_once 'includes/authorselect.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub2 extends add_pub_base {
    var $debug = 0;
    var $author_id = null;

    function add_pub2() {
        session_start();
        $this->pub =& $_SESSION['pub'];

        parent::add_pub_base();

        if ($this->loginError) return;

        $form = new HTML_QuickForm('add_pub2');

        $user = $_SESSION['user'];
        $auth_list = new pdAuthorList($this->db);
        $all_authors = $auth_list->list;

        if (count($user->collaborators) > 0)
            foreach (array_keys($user->collaborators) as $author_id) {
                unset($all_authors[$author_id]);
            }

        // get the first 10 popular authors used by this user
        $user->popularAuthorsDbLoad($this->db);

        $most_used_authors = array();
        if (count($user->author_rank) > 0) {
            $most_used_author_ids
                = array_slice(array_keys($user->author_rank), 0, 10);

            foreach($most_used_author_ids as $author_id) {
              if (isset($all_authors[$author_id])) {
                $most_used_authors[$author_id] = $all_authors[$author_id];
                unset($all_authors[$author_id]);
              }
            }
            asort($most_used_authors);
        }

        $form->addElement('header', null, 'Select from Authors in Database');
        $form->addElement('authorselect', 'authors', '',
                          array('form_name' => $form->_attributes['name'],
                                'author_list' => $all_authors,
                                'favorite_authors' => $user->collaborators,
                                'most_used_authors' => $most_used_authors),
                          array('class' => 'pool',
                                'style' => 'width:150px;height:200px;'));

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'prev_step', '<< Previous Step');
        $buttons[] = HTML_QuickForm::createElement(
            'button', 'cancel', 'Cancel',
            array('onclick' => "location.href='" . $url . "';"));
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'add_new_author', 'Add Author not in DB');
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
    }

    function renderForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;

        $defaults = array();

        if (count($this->pub->authors) > 0) {
            foreach ($this->pub->authors as $author)
                $defaults['authors'][] = $author->author_id;
        }

        $form->setDefaults($defaults);

        echo '<h3>Adding Following Publication</h3>'
            . $this->pub->getCitationHtml('', false) . '<p/>'
            . add_pub_base::similarPubsHtml();

        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
            . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');

        $form->accept($renderer);
        $this->renderer =& $renderer;
    }

    function processForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;

        $values = $form->exportValues();

        if (isset($values['authors']) && (count($values['authors']) > 0)) {
            foreach ($values['authors'] as $index => $author) {
                $pos = strpos($author, ':');
                if ($pos !== false) {
                    $values['authors'][$index] = substr($author, $pos + 1);
                }
            }
            $this->pub->addAuthor($this->db, $values['authors']);
        }

        if ($this->debug) return;

        if (isset($values['add_new_author']))
            header('Location: add_author.php');
        else if (isset($values['prev_step']))
            header('Location: add_pub1.php');
        else if (isset($values['finish']))
            header('Location: add_pub_submit.php');
        else
            header('Location: add_pub3.php');
    }
}

$page = new add_pub2();
echo $page->toHtml();

?>
