<?php ;

// $Id: delete_category.php,v 1.2 2006/06/12 04:32:15 aicmltec Exp $

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
    var $cat_id;

    function delete_category() {
        global $logged_in;

        parent::pdHtmlPage('delete_category');

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }

        if (!isset($_GET['cat_id']) || ($_GET['cat_id'] == '')) {
            $this->contentPre .= 'No category id defined';
            $this->pageError = true;
            return;
        }

        $db =& dbCreate();
        $this->cat_id = intval($_GET['cat_id']);

        $pub_list = new pdPubList($db, null, $this->cat_id);

        if (isset($pub_list->list) && (count($category->pub_list) > 0)) {
            $this->contentPre .= '<b>Deletion Failed</b><p/>'
                . 'This category is used by the following publications:<p/>';

            foreach ($pub_list->list as $pub)
                $this->contentPre .= '<b>' . $pub->title . '</b><br/>';

            $this->contentPre
                .= '<p/>To remove this category these publication(s) '
                . 'must be changed.';

            $db->close();
            return;
        }

        $category = new pdCategory();
        $category->dbLoad($db, $this->cat_id);

        // This is where the actual deletion happens.
        if (isset($confirm) && ($confirm == 'yes')) {
            $category->dbDelete($db);

            $this->contentPre .= 'You have successfully removed the '
                . 'following category from the database: <b>'
                . $category->name . '</b>';

            $db->close();
            return;
        }

        $table = new HTML_Table(array('width' => '100%',
                                      'border' => '0',
                                      'cellpadding' => '6',
                                      'cellspacing' => '0'));

        $table->addRow(array('Delete the following category?'));
        $table->addRow(array($category->category));

        if (isset($category->title) && trim($category->title != ''))
            $table->addRow(array('Title:', $category->title));

        $table->addRow(array('Email:', $category->email));
        $table->addRow(array('Organization:', $category->organization));
        $cell = '';

        if (isset($category->webpage) && trim($category->webpage != ""))
            $cell = '<a href="' . $category->webpage . '">'
                . $category->webpage . '</a>';
        else
            $cell = "none";

        $table->addRow(array('Web page:', $cell));

        $this->contentPre .= '<h3>Delete Author</h3>';

        $this->table =& $table;
        $form =& $this->confirmForm('deleter',
                                    './delete_category.php?cat_id='
                                    . $category->cat_id . 'confirm=yes');
        $this->contentPost = $form->toHtml();
    }
}

$page = new delete_category();
echo $page->toHtml();

?>