<?php ;

// $Id: add_pub2.php,v 1.5 2006/09/22 17:07:11 aicmltec Exp $

/**
 * \file
 *
 * \brief This is the form portion for adding or editing author information.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdAuthor.php';
require_once 'includes/authorselect.php';

/**
 * Renders the whole page.
 */
class add_pub2 extends pdHtmlPage {
    var $author_id = null;

    function add_pub2() {
        global $access_level;

        parent::pdHtmlPage('add_publication', 'Select Authors',
                           'Admin/add_pub2.php',
                           PD_NAV_MENU_LEVEL_ADMIN);

        if ($access_level < 1) {
            $this->loginError = true;
            return;
        }

        if ($_SESSION['state'] != 'pub_add') {
            header('Location: add_pub1.php');
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

        $form = new HTML_QuickForm('add_pub2');

        $user = $_SESSION['user'];
        $auth_list = new pdAuthorList($db);
        $all_authors = $auth_list->list;

        if (count($user->collaborators) > 0)
            foreach (array_keys($user->collaborators) as $author_id) {
                unset($all_authors[$author_id]);
            }

        // get the first 10 popular authors used by this user
        $user->popularAuthorsDbLoad($db);

        $most_used_authors = array();
        if (count($user->author_rank) > 0) {
            $most_used_author_ids
                = array_slice(array_keys($user->author_rank), 0, 10);

            foreach($most_used_author_ids as $author_id) {
                $most_used_authors[$author_id] = $all_authors[$author_id];
                unset($all_authors[$author_id]);
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

        if ($pub->pub_id != '')
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
        assert('isset($_SESSION["pub"])');

        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        $defaults = array();

        if (count($pub->authors) > 0) {
            foreach ($pub->authors as $author)
                $defaults['authors'][] = $author->author_id;
        }

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
    }

    function processForm() {
        assert('isset($_SESSION["pub"])');

        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        $values = $form->exportValues();

        if (count($values['authors']) > 0) {
            foreach ($values['authors'] as $index => $author) {
                $pos = strpos($author, ':');
                if ($pos !== false) {
                    $values['authors'][$index] = substr($author, $pos + 1);
                    $pub->addAuthor($db, $values['authors'][$index]);
                }
            }

            //$this->contentPre .= '<pre>' . print_r($values, true) . '</pre>';
        }

        if (isset($values['add_new_author']))
            header('Location: add_author.php');
        else if (isset($values['prev_step']))
            header('Location: add_pub1.php');
        else
            header('Location: add_pub3.php');
    }
}

session_start();
$access_level = check_login();
$page = new add_pub2();
echo $page->toHtml();


?>
