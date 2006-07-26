<?php ;

// $Id: delete_category.php,v 1.7 2006/07/26 20:56:39 aicmltec Exp $

/**
 * \file
 *
 * \brief Deletes a category from the database.
 *
 * Much like delete_author.php, this page confirms that the user would like to
 * delete the category. Then makes sure no current publications are using that
 * category, if some are, it lists them. If no publications are using that
 * category, then it is removed from the database.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdCategory.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
 */
class delete_category extends pdHtmlPage {
    function delete_category() {
        global $logged_in;

        parent::pdHtmlPage('delete_category');

        if (!$logged_in) {
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

        $db =& dbCreate();
        $form =& $this->confirmForm('deleter');
        $form->addElement('hidden', 'cat_id', $cat_id);

        $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
        $form->accept($renderer);

        if ($form->validate()) {
            $values = $form->exportValues();

            $category = new pdCategory();
            $result = $category->dbLoad($db, $values['cat_id']);
            if (!$result) {
                $this->pageError = true;
                $db->close();
                return;
            }

            $pub_list = new pdPubList($db, null, $this->cat_id);

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
$logged_in = check_login();
$page = new delete_category();
echo $page->toHtml();

?>