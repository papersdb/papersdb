<?php ;

// $Id: delete_publication.php,v 1.17 2007/03/13 22:06:11 aicmltec Exp $

/**
 * Deletes a publication from the database.
 *
 * This page confirms that the user would like to delete the following
 * publication and then removes it from the database once confirmation has been
 * given.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class delete_publication extends pdHtmlPage {
    function delete_publication() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('delete_publication');

        if ($this->loginError) return;

        $pub_id = null;
        if (isset($_GET['pub_id']) && ($_GET['pub_id'] != ''))
            $pub_id = intval($_GET['pub_id']);

        $form =& $this->confirmForm('deleter');
        $form->addElement('hidden', 'pub_id', $pub_id);

        if ($form->validate()) {
            $values = $form->exportValues();

            $db = dbCreate();
            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $values['pub_id']);
            if (!$result) {
                $this->pageError = true;
                return;
            }


            $title = $pub->title;
            $pub->dbDelete($db);

            echo 'You have successfully removed the following '
                . 'publication from the database: <p/><b>' . $title . '</b>';
        }
        else {
            if ($pub_id == null) {
                echo 'No pub id defined';
                $this->pageError = true;
                return;
            }

            $db = dbCreate();
            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $pub_id);
            if (!$result) {
                $this->pageError = true;
                return;
            }

            $renderer =& $form->defaultRenderer();
            $form->accept($renderer);

            echo '<h3>Delete Publication</h3>'
                . 'Delete the following paper?<p/>'
                . $pub->getCitationHtml();

            $this->form =& $form;
            $this->renderer =& $renderer;
        }
    }
}

$page = new delete_publication();
echo $page->toHtml();

?>