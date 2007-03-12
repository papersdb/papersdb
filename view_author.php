<?php ;

// $Id: view_author.php,v 1.23 2007/03/12 23:05:43 aicmltec Exp $

/**
 * Given a author id number, this displays all the info about
 * the author.
 *
 * If the author has only a few publications it will display the title and link
 * to them. If the author has more then 6 then it will link to a seperate page
 * of a list of publications by that author.
 *
 * if a user is logged in, they have the option of editing or deleting the
 * author.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthor.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class view_author extends pdHtmlPage {
    function view_author() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('view_authors');

        if ($this->loginError) return;

        if (!isset($_GET['author_id'])) {
            $this->pageError = true;
            return;
        }

        $auth = new pdAuthor();
        $auth->dbLoad($this->db, $_GET['author_id'],
                      (PD_AUTHOR_DB_LOAD_PUBS_MIN
                       | PD_AUTHOR_DB_LOAD_INTERESTS));

        // check if this author id is valid
        if (!isset($auth->author_id)) {
            $this->pageError = true;
            return;
        }

        $this->contentPre .= '<h3>' . $auth->name;

        if ($this->access_level > 0) {
            $this->contentPre .= $this->getAuthorIcons($auth, 0x6);
        }

        $this->contentPre .= '</h3>';

        $this->table = $this->authTableCreate($auth);

        $this->db->close();
    }

    function authTableCreate(&$auth) {
        $table = new HTML_Table(array('width' => '600',
                                      'border' => '0',
                                      'cellpadding' => '6',
                                      'cellspacing' => '0'));
        $table->setAutoGrow(true);

        $table->addRow(array('Name:', $auth->name));

        if (isset($auth->title) && (trim($auth->title) != "")) {
            $table->addRow(array('Title:', $auth->title));
        }

        $table->addRow(array('Email:',
                             "<a href='mailto:" . $auth->email . "'>"
                             . $auth->email . "</a>"));
        $table->addRow(array('Organization:', $auth->organization));

        if (isset($auth->webpage) && (trim($auth->webpage) != ""))
            $webpage = "<a href=\"" . $auth->webpage . "\" target=\"_blank\">"
                . $auth->webpage . "</a>";
        else
            $webpage = "none";

        $table->addRow(array('Webpage:', $webpage));

        $interestStr = '';
        if (isset($auth->interest) && is_array($auth->interest)) {
            foreach ($auth->interest as $interest) {
                $interestStr .= $interest . '<br/>';
            }
        }
        $table->addRow(array('Interest(s):', $interestStr));

        if ($auth->totalPublications > 0) {
            if ($auth->totalPublications <= 6) {
                assert('is_array($auth->pub_list->list)');
                $headingCell = 'Publications:';

                foreach ($auth->pub_list->list as $pub) {
                    if (isset($pub->title) && ($pub->title != '')) {
                        $title = "<a href='view_publication.php?pub_id="
                            . $pub->pub_id . "'>". $pub->title . "</a>";
                        $table->addRow(array($headingCell, $title));
                    }
                    $headingCell = '';
                }
            }
            else {
                $table->addRow(
                    array('Publications:',
                          '<a href="./list_publication.php?'
                          . 'type=view&author_id=' . $auth->author_id
                          . '">View Publications by this author</a>'));
            }
        }
        else {
            $table->addRow(array('No publications by this author'),
                           array('colspan' => 2));
        }

        $table->updateColAttributes(0, array('id' => 'emph', 'width' => '25%'));

        return $table;
    }
}

$page = new view_author();
echo $page->toHtml();

?>
