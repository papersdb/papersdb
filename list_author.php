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
    var $tab;

    function list_author() {
        parent::pdHtmlPage('all_authors');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);

        if (!isset($this->tab))
            $this->tab = 'A';
        else if ((strlen($this->tab) != 1) || (ord($this->tab) < ord('A'))
                 || (ord($this->tab) > ord('Z'))) {
            $this->pageError = true;
            return;
        }

        $auth_list = new pdAuthorList($this->db, null, $this->tab);

        echo $this->alphaSelMenu($this->tab, get_class($this) . '.php');

        echo "<h2>Authors</h2>";

        if (!isset($auth_list->list) || (count($auth_list->list) == 0)) {
            echo 'No authors with last name starting with ' . $this->tab
                . '<br/>';
            return;
        }

        $table = new HTML_Table(array('width' => '100%',
                                      'border' => '0',
                                      'cellpadding' => '0',
                                      'cellspacing' => '0'));
        $table->setAutoGrow(true);

        foreach ($auth_list->list as $author_id => $name) {
            $author = new pdAuthor();
            $author->dbLoad($this->db, $author_id,
                            PD_AUTHOR_DB_LOAD_BASIC
                            | PD_AUTHOR_DB_LOAD_PUBS_MIN);

            $name = '<span id="emph"><a href="view_author.php?author_id='
                . $author_id . '">' . $name . '</a>&nbsp;'
                . $this->getAuthorIcons($author) . '</span>';

            $info = array();
            if ($author->title != '')
                $info[] = '<span id="small">' . $author->title . '</span>';

            if ($author->organization != '')
                $info[] = '<span id="small">' . $author->organization
                    . '</span>';

            $info[] = '<span id="small">Number of publication entries in '
                . 'database: ' . $author->totalPublications . '</span>';

            $table->addRow(array($name, implode('<br/>', $info)));
        }

        // now assign table attributes including highlighting for even and odd
        // rows
        for ($i = 0; $i < $table->getRowCount(); $i++) {
            if ($i & 1)
                $table->updateRowAttributes($i, array('class' => 'even'), true);
            else
                $table->updateRowAttributes($i, array('class' => 'odd'), true);
            $table->updateCellAttributes($i, 1, array('id' => 'publist'), true);
        }
        $table->updateColAttributes(0, array('class' => 'emph',
                                             'id' => 'publist'), true);

        $this->table =& $table;
    }
}

$page = new list_author();
echo $page->toHtml();

?>

