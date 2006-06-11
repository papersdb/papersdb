<?php ;

// $Id: index.php,v 1.21 2006/06/11 20:42:26 aicmltec Exp $

/**
 * \file
 *
 * \brief Main page for PapersDB.
 *
 * Main page for public access, provides a login, and a function that selects
 * the most recent publications added.
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
 */
class indexPage extends pdHtmlPage {
    function indexPage() {
        global $logged_in;

        parent::pdHtmlPage('index');
        $this->db =& dbCreate();
        $pub_list = new pdPubList($this->db, null, -1, true);

        $this->contentPre = 'Recent Additions:<ul>';
        $stringlength = 0;
        foreach ($pub_list->list as $pub) {
            if ($stringlength > 300) break;

            if (strlen($pub->title) < 60)
                $stringlength += 60;
            else if (strlen($pub->title) <= 120)
                $stringlength += 120;
            else if (strlen($pub->title) > 120)
                $stringlength += 180;

            $this->contentPre .= '<li><a href="view_publication.php?pub_id='
                . $pub->pub_id . '">'
                . '<b>' . $pub->title . '</b></a></li>';
        }
        $this->contentPre .= '</ul>';
        $this->db->close();
    }
}

$page = new indexPage();
echo $page->toHtml();

?>
