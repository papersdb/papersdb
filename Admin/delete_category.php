<?php ;

// $Id: delete_category.php,v 1.18 2007/03/13 14:03:32 loyola Exp $

/**
 * Deletes a category from the database.
 *
 * Much like delete_author.php, this page confirms that the user would like to
 * delete the category. Then makes sure no current publications are using that
 * category, if some are, it lists them. If no publications are using that
 * category, then it is removed from the database.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdCategory.php';
require_once 'includes/pdPubList.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class delete_category extends pdHtmlPage {
    function delete_category() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('delete_category');

        if ($this->loginError) return;

        if (isset($_GET['cat_id']) && ($_GET['cat_id'] != ''))
            $cat_id = intval($_GET['cat_id']);
        else if (isset($_POST['cat_id']) && ($_POST['cat_id'] != ''))
            $cat_id = intval($_POST['cat_id']);
        else {
            echo 'No category id defined';
            $this->pageError = true;
            return;
        }

        $db = dbCreate();

        $category = new pdCategory();
        $result = $category->dbLoad($db, $cat_id);
        if (!$result) {
            $this->pageError = true;
            $db->close();
            return;
        }

        $q = $db->select('pub_cat', 'pub_id',
                         array('cat_id' => $cat_id),
                         "delete_category::delete_category");

        if ($db->numRows($q) > 0) {
            echo 'Cannot delete category <b>'
                . $category->category . '</b>.<p/>'
                . 'The category is used by the following '
                . 'publications:' . "\n"
                . '<ul>';

            $r = $db->fetchObject($q);
            while ($r) {
                $pub = new pdPublication();
                $pub->dbLoad($db, $r->pub_id);
                echo '<li>' . $pub->getCitationHtml() . '</li>';
                $r = $db->fetchObject($q);
            }
            echo '</ul>';
            $db->close();
            return;
        }

        $form =& $this->confirmForm('deleter');
        $form->addElement('hidden', 'cat_id', $cat_id);

        $renderer =& $form->defaultRenderer();
        $form->accept($renderer);

        if ($form->validate()) {
            $values = $form->exportValues();

            $pub_list = new pdPubList($db, array('cat_id' => $this->cat_id));

            if (isset($pub_list->list) && (count($category->pub_list) > 0)) {
                echo '<b>Deletion Failed</b><p/>'
                    . 'This category is used by the following publications:<p/>';

                foreach ($pub_list->list as $pub)
                    echo '<b>' . $pub->title . '</b><br/>';

                echo '<p/>To remove this category these publication(s) '
                    . 'must be changed.';
            }
            else {
                // This is where the actual deletion happens.
                $category->dbDelete($db);

                echo 'Category <b>' . $category->category
                    . '</b> removed from the database.';
            }
        }
        else {
            $category = new pdCategory();
            $result = $category->dbLoad($db, $cat_id);
            if (!$result) {
                $this->pageError = true;
                $db->close();
                return;
            }

            echo '<h3>Confirm</h3>'
                . 'Delete category <b>' . $category->category . '</b>?<p/>';

            $this->form =& $form;
            $this->renderer =& $renderer;
        }

        $db->close();
    }
}

$page = new delete_category();
echo $page->toHtml();

?>