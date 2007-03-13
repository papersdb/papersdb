<?php ;

/**
 * Lists all the authors in the database.
 *
 * Makes each author a link to it's own seperate page. If a user is logged in,
 * he/she has the option of adding a new author, editing any of the authors and
 * deleting any of the authors.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdAuthor.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class list_author extends pdHtmlPage {
    function list_author() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('all_authors');

        if ($this->loginError) return;

        // Performing SQL query
        $auth_list = new pdAuthorList($this->db);

        echo "<h1>Authors</h1>";

        $table = new HTML_Table(array('width' => '100%',
                                      'border' => '0',
                                      'cellpadding' => '6',
                                      'cellspacing' => '0'));
        $table->setAutoGrow(true);

        if (count($auth_list->list) > 0) {
            foreach ($auth_list->list as $author_id => $name) {
                $author = new pdAuthor();
                $author->dbLoad($this->db, $author_id, PD_AUTHOR_DB_LOAD_BASIC);

                $info = '<a href="view_author.php?author_id='
                    . $author_id . '">' . $name . '</a>';

                if ($author->title != '')
                    $info .= '<br/><span id="small">'
                        . $author->title . '</span>';

                if ($author->organization != '')
                    $info .= '<br/><span id="small">'
                        . $author->organization . '</span>';

                $table->addRow(array($info, $this->getAuthorIcons($author)));
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

            if ($this->access_level > 0) {
                $table->updateCellAttributes($i, 1, array('id' => 'emph',
                                                          'class' => 'small'));
                $table->updateCellAttributes($i, 2, array('id' => 'emph',
                                                          'class' => 'small'));
            }
        }

        $this->table =& $table;
    }
}

$page = new list_author();
echo $page->toHtml();

?>

