<?php ;

/**
 * \file
 *
 * \brief Lists all the authors in the database.
 *
 * Makes each author a link to it's own seperate page. If a user is logged in,
 * he/she has the option of adding a new author, editing any of the authors and
 * deleting any of the authors.
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthorList.php';

/**
 * Renders the whole page.
 */
class list_author extends pdHtmlPage {
    function list_author() {
        global $logged_in;

        parent::pdHtmlPage('all_authors');
        $this->db =& dbCreate();

        // Performing SQL query
        $auth_list = new pdAuthorList($this->db);

        $this->contentPre .= "<h2><u>Authors<h2>";

        $table = new HTML_Table(array('width' => '100%',
                                            'border' => '0',
                                            'cellpadding' => '6',
                                            'cellspacing' => '0'));
        $table->setAutoGrow(true);

        if (count($auth_list->list) > 0) {
            foreach ($auth_list->list as $author_id => $name) {
                unset($cells);
                $cells[] = '<a href="view_author.php?author_id='
                    . $author_id . '">' . $name . '</a>';

                $cells[] = '<a href="view_author.php?author_id='
                    . $author_id . '">'
                    . '<img src="images/viewmag.png" title="view" alt="view" height="16" '
                    . 'width="16" border="0" align="middle" /></a>';

                if ($logged_in) {
                    $cells[] = '<a href="Admin/add_author.php?author_id='
                        . $author_id . '">'
                        . '<img src="images/pencil.png" title="edit" alt="edit" '
                        . 'height="16" width="16" border="0" align="middle" /></a>';
                    $cells[] = '<a href="Admin/delete_author.php?author_id='
                        . $author_id . '">'
                        . '<img src="images/kill.png" title="delete" alt="delete" '
                        . 'height="16" width="16" border="0" align="middle" /></a>';
                }

                $table->addRow($cells);
            }
        }
        else {
            $table->addRow(array('No Authors'));
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

        $this->table =& $table;
        $this->db->close();
    }
}

session_start();
$logged_in = check_login();
$page = new list_author();
echo $page->toHtml();

?>

