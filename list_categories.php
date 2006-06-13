<?php ;

// $Id: list_categories.php,v 1.1 2006/06/13 19:00:22 aicmltec Exp $

/**
 * \file
 *
 * \brief This page displays all venues.
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdCategory.php';

/**
 * Renders the whole page.
 */
class list_venues extends pdHtmlPage {
    function list_venues() {
        global $logged_in;

        parent::pdHtmlPage('all_categories');
        $db =& dbCreate();

        $cat_list = new pdCatList($db);

        $table = new HTML_Table(array('width' => '100%',
                                            'border' => '0',
                                            'cellpadding' => '6',
                                            'cellspacing' => '0'));
        $table->setAutoGrow(true);

        foreach ($cat_list->list as $c) {
            unset($fields);

            $category = new pdCategory();
            $result = $category->dbLoad($db, $c->cat_id);
            assert('$result');

            $cell1 = '<b>' . $category->category . '</b><br/>';

            if (count($category->info) > 0) {
                foreach ($category->info as $info) {
                    $fields[] = $info->name;
                }
                $cell1 .= 'Fields: ' . implode(', ', $fields);
            }

            if ($logged_in) {
                $cell2 = '<a href="Admin/add_category.php?cat_id='
                    . $category->cat_id . '">Edit</a><br/>'
                    . '<a href="Admin/delete_category.php?cat_id='
                    . $category->cat_id . '">Delete</a>';
            }
            else {
                $cell2 = '';
            }

            $table->addRow(array($cell1, $cell2));
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

            if ($logged_in) {
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

$page = new list_venues();
echo $page->toHtml();

?>
