<?php ;

// $Id: delete_author.php,v 1.4 2006/06/11 20:42:26 aicmltec Exp $

/**
 * \file
 *
 * \brief Deletes an author from the database.
 *
 * This page first confirms that the user would like to delete the specified
 * author, and then makes the actual deletion. Before the author can removed
 * though, it must not be in any publications. This is checked, and displays
 * the titles of all the publications the author is in.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthor.php';

/**
 * Renders the whole page.
 */
class delete_author extends pdHtmlPage {
    var $author_id;

    function delete_author() {
        global $logged_in;

        parent::pdHtmlPage('delete_author');

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }

        if (!isset($_GET['author_id']) || ($_GET['author_id'] == '')) {
            $this->contentPre .= 'No author id defined';
            $this->pageError = true;
            return;
        }

        $db =& dbCreate();
        $this->author_id = intval($_GET['author_id']);
        $author = new pdAuthor();
        $author->dbLoad($db, $this->author_id);

        if (isset($author->pub_list) && (count($author->pub_list) > 0)) {
            $this->contentPre .= '<b>Deletion Failed</b><p/>'
                . 'This author is listed as author for the following '
                . 'publications:<p/>';

            foreach ($author->pub_list->list as $pub)
                $this->contentPre .= '<b>' . $pub->title . '</b><br/>';

            $this->contentPre
                .= '<p/>You must change or remove the author of the following '
                . 'publication(s) in order to delete this author.';

            $db->close();
            return;
        }

        // This is where the actual deletion happens.
        if (isset($confirm) && $confirm == "yes") {
            $author->dbDelete($db);

            $this->contentPre .= 'You have successfully removed the '
                . 'following category from the database: <b>'
                . $author->name . '</b>';

            $db->close();
            return;
        }

        $table = new HTML_Table(array('width' => '100%',
                                      'border' => '0',
                                      'cellpadding' => '6',
                                      'cellspacing' => '0'));

        $table->addRow(array('Delete the following author?'));
        $table->addRow(array($author->name));

        if (isset($author->title) && trim($author->title != ''))
            $table->addRow(array('Title:', $author->title));

        $table->addRow(array('Email:', $author->email));
        $table->addRow(array('Organization:', $author->organization));
        $cell = '';

        if (isset($author->webpage) && trim($author->webpage != ""))
            $cell = '<a href="' . $author->webpage . '">'
                . $author->webpage . '</a>';
        else
            $cell = "none";

        $table->addRow(array('Web page:', $cell));

        $this->contentPre .= '<h3>Delete Author</h3>';

        $this->table =& $table;
        $form =& $this->confirmForm('deleter',
                                    './delete_author.php?author_id='
                                    . $author->author_id . 'confirm=yes');
        $this->contentPost = $form->toHtml();
    }
}

$page = new delete_author();
echo $page->toHtml();

?>