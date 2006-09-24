<?php ;

// $Id: list_categories.php,v 1.7 2006/09/24 21:21:42 aicmltec Exp $

/**
 * This page displays all venues.
 *
 * @package PapersDB
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
class list_venues extends pdHtmlPage {
    function list_venues() {
        global $access_level;

        pubSessionInit();
        parent::pdHtmlPage('all_categories');
        $db =& dbCreate();

        $cat_list = new pdCatList($db);

        $table = new HTML_Table(array('width' => '100%',
                                            'border' => '0',
                                            'cellpadding' => '6',
                                            'cellspacing' => '0'));
        $table->setAutoGrow(true);

        foreach (array_keys($cat_list->list) as $cat_id) {
            unset($fields);
            unset($cells);

            $category = new pdCategory();
            $result = $category->dbLoad($db, $cat_id);
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

            if ($access_level > 0) {
                $cells[] = '<a href="Admin/add_category.php?cat_id='
                    . $category->cat_id . '">'
                    . '<img src="images/pencil.png" title="edit" alt="edit" '
                    . 'height="16" width="16" border="0" align="middle" /></a>';
                $cells[] = '<a href="Admin/delete_category.php?cat_id='
                    . $category->cat_id . '">'
                    . '<img src="images/kill.png" title="delete" alt="delete" '
                    . 'height="16" width="16" border="0" align="middle" /></a>';
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

            if ($access_level > 0) {
                $table->updateCellAttributes($i, 1, array('id' => 'emph',
                                                          'class' => 'small'));
                $table->updateCellAttributes($i, 2, array('id' => 'emph',
                                                          'class' => 'small'));
            }
        }

        $this->contentPre .= '<h2><b><u>Publication Categories</u></b></h2>';
        $this->table =& $table;
        $db->close();
    }
}

session_start();
$access_level = check_login();
$page = new list_venues();
echo $page->toHtml();

?>
