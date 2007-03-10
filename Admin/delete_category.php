<?php ;

// $Id: delete_category.php,v 1.15 2007/03/10 01:23:05 aicmltec Exp $

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
        global $access_level;

        pubSessionInit();
        parent::pdHtmlPage('delete_category');

        if ($access_level <= 0) {
            $this->loginError = true;
            return;
        }

        if (isset($_GET['cat_id']) && ($_GET['cat_id'] != ''))
            $cat_id = intval($_GET['cat_id']);
        else if (isset($_POST['cat_id']) && ($_POST['cat_id'] != ''))
            $cat_id = intval($_POST['cat_id']);
        else {
            $this->contentPre .= 'No category id defined';
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
            $this->contentPre .= 'Cannot delete category <b>'
                . $category->category . '</b>.<p/>'
                . 'The category is used by the following '
                . 'publications:' . "\n"
                . '<ul>';

            $r = $db->fetchObject($q);
            while ($r) {
                $pub = new pdPublication();
                $pub->dbLoad($db, $r->pub_id);
                $this->contentPre
                    .= '<li>' . $pub->getCitationHtml() . '</li>';
                $r = $db->fetchObject($q);
            }
            $this->contentPre .= '</ul>';
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
                $this->contentPre .= '<b>Deletion Failed</b><p/>'
                    . 'This category is used by the following publications:<p/>';

                foreach ($pub_list->list as $pub)
                    $this->contentPre .= '<b>' . $pub->title . '</b><br/>';

                $this->contentPre
                    .= '<p/>To remove this category these publication(s) '
                    . 'must be changed.';
            }
            else {
                // This is where the actual deletion happens.
                $category->dbDelete($db);

                $this->contentPre .= 'Category <b>' . $category->category
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

            $this->contentPre .= '<h3>Confirm</h3>'
                . 'Delete category <b>' . $category->category . '</b>?<p/>';

            $this->form =& $form;
            $this->renderer =& $renderer;
        }

        $db->close();
    }
}

session_start();
$access_level = check_login();
$page = new delete_category();
echo $page->toHtml();

?>