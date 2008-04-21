<?php

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
    public function __construct() {
        parent::__construct('all_categories');

        if ($this->loginError) return;

        $cat_list = pdCatList::create($this->db);

        echo '<h1>Publication Categories</h1>';

        foreach (array_keys($cat_list) as $cat_id) {
            unset($fields);
            unset($cells);

            $category = new pdCategory();
            $result = $category->dbLoad($this->db, $cat_id);
            assert('$result');

            $table = new HTML_Table(array('class' => 'publist'));
            $table->setAutoGrow(true);

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

            if ($this->access_level > 0)
                $cells[] = $this->getCategoryIcons($category);

            $table->addRow($cells);
            $table->updateColAttributes(0, array('class' => 'category'), NULL);
            $table->updateColAttributes(2, array('class' => 'icons'), NULL);
            echo $table->toHtml();
            unset($table);
        }
    }
}

$page = new list_categories();
echo $page->toHtml();

?>
