<?php

/**
 * The user reaches this page only when he is adding a new author, and the
 * author name that was used is similar to one already in the database. Similar
 * means that the name has the same last name and first initial as another name
 * in the database.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once '../includes/defines.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthor.php';
require_once('HTML/QuickForm/Renderer/QuickHtml.php');

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class author_confirm extends pdHtmlPage {
    public function __construct() {
        parent::__construct('author_confirm', 'Author Confirm',
            'Admin/author_confirm.php');

        if ($this->loginError) return;

        if (!isset($_SESSION['new_author'])) {
            $this->pageError = true;
            return;
        }

        $new_author = $_SESSION['new_author'];

        if (isset($new_author->author_id)) {
            $this->pageError = true;
            debugVar('new_author', $new_author);
            return;
        }

        $this->form =& $this->confirmForm('author_confirm', null, 'Add');

        if ($this->form->validate()) {
            $values = $this->form->exportValues();

            $new_author->dbSave($this->db);

            if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
                assert('isset($_SESSION["pub"])');
                $pub =& $_SESSION['pub'];
                $pub->addAuthor($this->db, $new_author->author_id);
                header('Location: add_pub2.php');
                return;
            }

            echo 'Author <span class="emph">', $new_author->name, '</span> ', 
            	'succesfully added to the database.', '<p/>', 
            	'<a href="add_author.php">', 'Add another new author</a>';
        }
        else {
            $like_authors = pdAuthorList::create(
            	$this->db, $new_author->firstname, $new_author->lastname);

            assert('count($like_authors) > 0');

            echo 'Attempting to add new author: ', '<span class="emph">', 
            	$new_author->name, "</span><p/>\n", 
            	'The following authors, already in the database, have similar names:<ul>';

            foreach ($like_authors as $auth) {
                echo '<li>', $auth, '</li>';
            }
            echo '</ul>Do you really want to add this author?';

            $renderer =& $this->form->defaultRenderer();

            $renderer->setFormTemplate(
                '<table width="100%" border="0" cellpadding="3" cellspacing="2">'
                . '<form{attributes}>{content}</form></table>');
            $renderer->setHeaderTemplate(
                '<tr><td '
                . 'align="left" colspan="2"><b>{header}</b></td></tr>');

            $this->form->accept($renderer);
            $this->renderer =& $renderer;
        }
    }
}

$page = new author_confirm();
echo $page->toHtml();

?>