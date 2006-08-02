<?php ;

// $Id: list_publication.php,v 1.19 2006/08/02 18:26:35 aicmltec Exp $

/**
 * \file
 *
 * \brief Lists all the publications in database.
 *
 * Makes each publication a link to it's own seperate page.  If a user is
 * logged in, he/she has the option of adding a new publication, editing any of
 * the publications and deleting any of the publications.
 *
 * Pretty much identical to list_author.php
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
 */
class list_publication extends pdHtmlPage {
    function list_publication() {
        global $logged_in;

        parent::pdHtmlPage('all_publications');

        $this->db =& dbCreate();

        if (isset($_GET['author_id'])) {
            // If there exists an author_id, only extract the publications for
            // that author
            //
            // This is used when viewing an author.
            $pub_list = new pdPubList($this->db, $_GET['author_id']);
        }
        else {
            // Otherwise just get all publications
            $pub_list = new pdPubList($this->db);
        }

        $this->contentPre = '<h2><b><u>Publications';

        if (isset($_GET['author_id'])) {
            $auth = new pdAuthor();
            $auth->dbLoad($this->db, $_GET['author_id'],
                          PD_AUTHOR_DB_LOAD_BASIC);

            $this->contentPre .= " by " . $auth->name;
        }

        $this->contentPre .= "</u></b></h2>\n";

        $this->table = new HTML_Table(array('width' => '100%',
                                            'border' => '0',
                                            'cellpadding' => '6',
                                            'cellspacing' => '0'));
        $table =& $this->table;
        $table->setAutoGrow(true);

        if (count($pub_list->list) > 0) {
            foreach ($pub_list->list as $pub) {
                unset($cells);
                $cells[] = "<a href='view_publication.php?pub_id="
                    . $pub->pub_id . "'>" . $pub->title . "</a>";
                $attr[] = '';
                if ($logged_in) {
                    $cells[] = '<a href="view_publication.php?pub_id='
                        . $pub->pub_id . '">'
                        . '<img src="images/viewmag.png" title="view" alt="view" height="16" '
                        . 'width="16" border="0" align="middle" /></a>';

                    $cells[] = '<a href="Admin/add_publication.php?pub_id='
                        . $pub->pub_id . '">'
                        . '<img src="images/pencil.png" title="edit" alt="edit" '
                        . 'height="16" width="16" border="0" align="middle" /></a>';
                    $cells[] = '<a href="Admin/delete_publication.php?pub_id='
                        . $pub->pub_id . '">'
                        . '<img src="images/kill.png" title="delete" alt="delete" '
                        . 'height="16" width="16" border="0" align="middle" /></a>';
                }

                $table->addRow($cells);
            }
        }
        else {
            $table->addRow(array('No Publications'));
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

        $this->db->close();
    }
}

session_start();
$logged_in = check_login();
$page = new list_publication();
echo $page->toHtml();

?>


