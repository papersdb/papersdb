<?php ;

// $Id: list_categories.php,v 1.12 2007/03/12 23:05:43 aicmltec Exp $

/**
 * This page displays all venues.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdCategory.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class list_categories extends pdHtmlPage {
    function list_categories() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('all_categories');

        if ($this->loginError) return;

        $cat_list = new pdCatList($this->db);

        $table = new HTML_Table(array('width' => '100%',
                                            'border' => '0',
                                            'cellpadding' => '6',
                                            'cellspacing' => '0'));
        $table->setAutoGrow(true);

        foreach (array_keys($cat_list->list) as $cat_id) {
            unset($fields);
            unset($cells);

            $category = new pdCategory();
            $result = $category->dbLoad($this->db, $cat_id);
            assert('$result');

            $cells[] = '<b>' . $category->category . '</b><br/>';

            if (count($category->info) > 0) {
                foreach ($category->info as $info_id => $name) {
                    $fields[] = $name;
                }
                $cells[] = 'Fields: ' . implode(', ', $fields);
            }
            else {
                $cells[] = '';
            }

            if ($this->access_level > 0) {
                $cells[] = $this->getCategoryIcons($category);
            }

            $table->addRow($cells);
        }

        // now assign table attributes including highlighting for even and odd
        // rows
        for ($i = 0; $i < $table->getRowCount(); $i++) {
            $table->updateCellAttributes($i, 0, array('class' => 'standard'));

            if ($i & 1) {
                $table->updateRowAttributes($i, array('class' => 'even'), true);
            }
            else {
                $table->updateRowAttributes($i, array('class' => 'odd'), true);
            }

            if ($this->access_level > 0) {
                $table->updateCellAttributes($i, 1, array('id' => 'emph',
                                                          'class' => 'small'));
                $table->updateCellAttributes($i, 2, array('id' => 'emph',
                                                          'class' => 'small'));
            }
        }

        $this->contentPre .= '<h1>Publication Categories</h1>';
        $this->table =& $table;
        $this->db->close();
    }
}

$page = new list_categories();
echo $page->toHtml();

?>
