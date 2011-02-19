<?php

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

/** Requries the base class and classes to access the database. */
require_once '../includes/defines.php';
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
    public $cat_id;

    public function __construct() {
        parent::__construct('delete_category', 'Delete Category',
                           'Admin/delete_category.php');

        if ($this->loginError) return;

        $this->loadHttpVars();

        if (!isset($this->cat_id)) {
            echo 'No category id defined';
            $this->pageError = true;
            return;
        }
        else if (!is_numeric($this->cat_id)) {
            $this->pageError = true;
            return;
        }

        $category = new pdCategory();
        $result = $category->dbLoad($this->db, $this->cat_id);
        if (!$result) {
            $this->pageError = true;
            return;
        }

        $pub_list = pdPubList::create($this->db, array('cat_id' => $this->cat_id));

        if (isset($pub_list) && (count($pub_list) > 0)) {
            echo 'Cannot delete category <b>', $category->category, '</b>.<p/>', 
            	'The category is used by the following publications:', "\n", 
            	displayPubList($this->db, $pub_list);
            return;
        }

        $form =& $this->confirmForm('deleter');
        $form->addElement('hidden', 'cat_id', $this->cat_id);

        $renderer =& $form->defaultRenderer();
        $form->accept($renderer);

        if ($form->validate()) {
            $values = $form->exportValues();

            // This is where the actual deletion happens.
            $category->dbDelete($this->db);

            echo 'Category <b>', $category->category, '</b> removed from the database.';
        }
        else {
            echo '<h3>Confirm</h3>Delete category <b>', $category->category, '</b>?<p/>';

            $this->form =& $form;
            $this->renderer =& $renderer;
        }
    }
}

$page = new delete_category();
echo $page->toHtml();

?>