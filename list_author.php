<?php

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
    public $tab;

    public function __construct() {
        parent::__construct('all_authors');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);

        if (!isset($this->tab))
            $this->tab = 'A';
        else {
        	$tab = strtoupper($this->tab);
        	if ((strlen($tab) != 1) || (ord($tab) < ord('A'))
        	   || (ord($tab) > ord('Z'))) {
        		$this->pageError = true;
        		return;
        	}
        }

        $auth_list = pdAuthorList::create($this->db, null, $this->tab);

        echo $this->alphaSelMenu($this->tab, get_class($this) . '.php');

        echo "<h2>Authors</h2>";

        if (empty($auth_list) || (count($auth_list) == 0)) {
            echo 'No authors with last name starting with ', $this->tab, '<br/>';
            return;
        }

        foreach ($auth_list as $author_id => $name) {
            $author = new pdAuthor();
            $author->dbLoad($this->db, $author_id,
                            pdAuthor::DB_LOAD_BASIC
                            | pdAuthor::DB_LOAD_PUBS_MIN);

            $name = '<span class="emph"><a href="view_author.php?author_id='
                . $author_id . '">' . $name . '</a>&nbsp;';
            $icons = $this->getAuthorIcons($author) . '</span>';

            $info = array();
            if ($author->title != '')
                $info[] = '<span class="small">' . $author->title . '</span>';

            if ($author->organization != '')
                $info[] = '<span class="small">' . $author->organization
                    . '</span>';

			$info[] .= '<a href="list_publication.php?author_id='
				. $author_id
				. '&menu=0"><span class="small" style="color:#000;font-weight:normal;">'
	            . 'Publication entries in database: ' 
	            . $author->totalPublications . '</span>';

            $table = new HTML_Table(array('class' => 'publist'));
            $table->addRow(array($name . '<br/>' . implode('<br/>', $info), 
                                 $icons));
            $table->updateColAttributes(1, array('class' => 'icons'), NULL);
            echo $table->toHtml();
            unset($table);
        }
    }
}

$page = new list_author();
echo $page->toHtml();

?>

